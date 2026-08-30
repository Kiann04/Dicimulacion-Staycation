<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Staycation;
use App\Models\User;
use App\Services\BookingArchiveService;
use App\Services\BookingPaymentService;
use App\Services\PaymentProofService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;
use Throwable;

class BookingArchiveTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    private User $admin;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake(PaymentProofService::DISK);

        $this->admin = User::factory()->admin()->create();
        $this->booking = Booking::factory()
            ->for(Staycation::factory()->create())
            ->forDates($this->day(30), $this->day(35))
            ->create([
                'payment_status' => PaymentStatus::Pending->value,
                'payment_proof' => PaymentProofService::DIRECTORY.'/proof.jpg',
            ]);

        $this->actingAs($this->admin);
    }

    // ------------------------------------------------------------- atomicity

    public function test_archiving_moves_the_booking_and_removes_the_original(): void
    {
        $this->delete(route('admin.bookings.delete', $this->booking))->assertRedirect();

        $this->assertDatabaseMissing('bookings', ['id' => $this->booking->getKey()]);
        $this->assertDatabaseHas('booking_history', [
            'booking_id' => $this->booking->getKey(),
            'user_id' => $this->booking->user_id,
        ]);
    }

    public function test_the_archive_keeps_the_payment_proof_for_the_refund_trail(): void
    {
        $this->delete(route('admin.bookings.delete', $this->booking));

        $this->assertSame(
            $this->booking->payment_proof,
            BookingHistory::sole()->payment_proof
        );
    }

    public function test_a_failed_archive_write_leaves_the_booking_live(): void
    {
        BookingHistory::creating(fn () => throw new \RuntimeException('archive table offline'));

        $thrown = null;

        try {
            app(BookingArchiveService::class)->archiveAndDelete($this->booking);
        } catch (Throwable $exception) {
            $thrown = $exception;
        } finally {
            BookingHistory::flushEventListeners();
        }

        $this->assertNotNull($thrown);
        $this->assertDatabaseHas('bookings', ['id' => $this->booking->getKey()]);
        $this->assertDatabaseCount('booking_history', 0);
    }

    public function test_a_failed_deletion_rolls_the_archive_row_back(): void
    {
        Booking::deleting(fn () => throw new \RuntimeException('delete blocked'));

        $thrown = null;

        try {
            app(BookingArchiveService::class)->archiveAndDelete($this->booking);
        } catch (Throwable $exception) {
            $thrown = $exception;
        } finally {
            Booking::flushEventListeners();
        }

        $this->assertNotNull($thrown);
        $this->assertDatabaseHas('bookings', ['id' => $this->booking->getKey()]);
        $this->assertDatabaseCount('booking_history', 0);
    }

    public function test_a_repeated_archive_request_cannot_create_a_second_record(): void
    {
        app(BookingArchiveService::class)->archiveAndDelete($this->booking);

        // The second call finds nothing left to archive, which is what serializes
        // two concurrent requests once the first has committed.
        try {
            app(BookingArchiveService::class)->archiveAndDelete($this->booking->getKey());
            $this->fail('Archiving the same booking twice should not be possible.');
        } catch (ModelNotFoundException) {
            // expected
        }

        $this->assertDatabaseCount('booking_history', 1);
    }

    public function test_a_repeated_http_request_returns_not_found_rather_than_duplicating(): void
    {
        $this->delete(route('admin.bookings.delete', $this->booking))->assertRedirect();

        $this->delete(route('admin.bookings.delete', $this->booking))->assertNotFound();

        $this->assertDatabaseCount('booking_history', 1);
    }

    public function test_a_booking_with_a_verified_payment_is_never_archived(): void
    {
        app(BookingPaymentService::class)->verifyPayment($this->booking, PaymentStatus::Paid);

        $this->delete(route('admin.bookings.delete', $this->booking))->assertSessionHas('error');

        $this->assertDatabaseHas('bookings', ['id' => $this->booking->getKey()]);
        $this->assertDatabaseCount('booking_history', 0);
    }

    // --------------------------------------------- archived proof authorization

    public function test_an_administrator_may_read_an_archived_proof(): void
    {
        Storage::disk(PaymentProofService::DISK)
            ->put(PaymentProofService::DIRECTORY.'/proof.jpg', 'bytes');

        $this->delete(route('admin.bookings.delete', $this->booking));

        $this->get(route('booking_history.payment_proof', BookingHistory::sole()))->assertOk();
    }

    public function test_the_owner_may_still_read_their_archived_proof(): void
    {
        Storage::disk(PaymentProofService::DISK)
            ->put(PaymentProofService::DIRECTORY.'/proof.jpg', 'bytes');

        $owner = $this->booking->user;

        $this->delete(route('admin.bookings.delete', $this->booking));

        $this->actingAs($owner)
            ->get(route('booking_history.payment_proof', BookingHistory::sole()))
            ->assertOk();
    }

    public function test_ordinary_staff_may_not_read_an_archived_proof(): void
    {
        $this->delete(route('admin.bookings.delete', $this->booking));

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('booking_history.payment_proof', BookingHistory::sole()))
            ->assertForbidden();
    }

    public function test_an_unrelated_customer_may_not_read_an_archived_proof(): void
    {
        $this->delete(route('admin.bookings.delete', $this->booking));

        $this->actingAs(User::factory()->create())
            ->get(route('booking_history.payment_proof', BookingHistory::sole()))
            ->assertForbidden();
    }

    public function test_a_guest_may_not_read_an_archived_proof(): void
    {
        // Built directly rather than through the admin endpoint, so this request
        // is genuinely unauthenticated rather than inheriting the admin session.
        $history = BookingHistory::create([
            'booking_id' => $this->booking->getKey(),
            'user_id' => $this->booking->user_id,
            'name' => $this->booking->name,
            'staycation_id' => $this->booking->staycation_id,
            'payment_proof' => PaymentProofService::DIRECTORY.'/proof.jpg',
        ]);

        $this->app['auth']->forgetGuards();

        $this->get(route('booking_history.payment_proof', $history))
            ->assertRedirect(route('login'));
    }
}
