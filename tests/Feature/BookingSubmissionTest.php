<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use App\Services\PaymentProofService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;
use Throwable;

class BookingSubmissionTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    private Staycation $staycation;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(PaymentProofService::DISK);

        $this->staycation = Staycation::factory()->create(['house_price' => 2000]);
        $this->customer = User::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'guest_number' => 2,
            'startDate' => $this->day(9),
            'endDate' => $this->day(14),
            'payment_type' => 'half',
            'payment_method' => 'gcash',
            'payment_proof' => UploadedFile::fake()->create('proof.jpg', 64, 'image/jpeg'),
            'phone' => '09123456789',
            'transaction_number' => 'TXN-1',
            'message' => 'Late check-in please',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submit(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->customer)
            ->post(route('booking.submit', $this->staycation), $this->payload($overrides));
    }

    public function test_a_guest_cannot_submit_a_booking(): void
    {
        $this->post(route('booking.submit', $this->staycation), $this->payload())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_customer_can_submit_a_booking(): void
    {
        $this->submit()->assertRedirect(route('BookingHistory.index'));

        $booking = Booking::sole();

        $this->assertSame($this->customer->getKey(), $booking->user_id);
        $this->assertSame($this->staycation->getKey(), $booking->staycation_id);
        $this->assertSame(BookingStatus::Pending->value, $booking->status);

        // A customer uploading a proof verifies nothing: the booking waits for an
        // administrator, and no money is recorded as received yet.
        $this->assertSame(PaymentStatus::Pending->value, $booking->payment_status);
        $this->assertSame('0.00', $booking->amount_paid);
        $this->assertSame('5000.00', $booking->declared_amount);
        $this->assertSame('half', $booking->payment_type);
        $this->assertSame($this->day(9), $booking->start_date->toDateString());
        $this->assertSame($this->day(14), $booking->end_date->toDateString());
    }

    public function test_the_booking_is_attributed_to_the_signed_in_user_not_the_request(): void
    {
        $this->submit(['name' => 'Someone Else', 'user_id' => 999])->assertRedirect();

        $booking = Booking::sole();

        $this->assertSame($this->customer->getKey(), $booking->user_id);
        $this->assertSame($this->customer->name, $booking->name);
        $this->assertSame($this->customer->email, $booking->email);
    }

    public function test_it_rejects_a_range_that_overlaps_an_existing_booking(): void
    {
        Booking::factory()->for($this->staycation)->forDates($this->day(9), $this->day(14))->create();

        $this->submit(['startDate' => $this->day(11), 'endDate' => $this->day(13)])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_it_rejects_a_range_that_a_previous_preview_would_have_approved(): void
    {
        $this->actingAs($this->customer)->post(route('booking.preview', $this->staycation), [
            'name' => 'Preview Customer',
            'phone' => '09123456789',
            'guest_number' => 2,
            'startDate' => $this->day(9),
            'endDate' => $this->day(14),
        ])->assertOk();

        Booking::factory()->for($this->staycation)->forDates($this->day(9), $this->day(14))->create();

        $this->submit()->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_it_rejects_a_range_covering_a_blocked_date(): void
    {
        BlockedDate::factory()->for($this->staycation)->create([
            'start_date' => $this->day(10),
            'end_date' => $this->day(11),
        ]);

        $this->submit()->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_it_allows_a_range_starting_on_a_previous_checkout_day(): void
    {
        Booking::factory()->for($this->staycation)->forDates($this->day(4), $this->day(9))->create();

        $this->submit()->assertRedirect(route('BookingHistory.index'));

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_it_rejects_an_end_date_that_is_not_after_the_start_date(): void
    {
        $this->submit(['endDate' => $this->day(9)])->assertSessionHasErrors('endDate');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_it_rejects_more_guests_than_the_maximum_capacity(): void
    {
        $this->submit(['guest_number' => 9])->assertSessionHasErrors('guest_number');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_it_rejects_a_payment_type_outside_the_allowed_set(): void
    {
        $this->submit(['payment_type' => 'quarter'])->assertSessionHasErrors('payment_type');

        $this->assertDatabaseCount('bookings', 0);
    }

    // ------------------------------------------------- unavailable property

    public function test_it_rejects_a_submission_for_a_property_that_is_not_open(): void
    {
        $this->staycation->update(['house_availability' => 'unavailable']);

        $this->submit()->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_the_preview_rejects_a_property_that_is_not_open(): void
    {
        $this->staycation->update(['house_availability' => 'unavailable']);

        $this->actingAs($this->customer)
            ->post(route('booking.preview', $this->staycation), [
                'name' => 'Preview Customer',
                'phone' => '09123456789',
                'guest_number' => 2,
                'startDate' => $this->day(9),
                'endDate' => $this->day(14),
            ])
            ->assertRedirect()
            ->assertSessionHas('message');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_property_closed_after_the_preview_still_rejects_the_submission(): void
    {
        $this->actingAs($this->customer)
            ->post(route('booking.preview', $this->staycation), [
                'name' => 'Preview Customer',
                'phone' => '09123456789',
                'guest_number' => 2,
                'startDate' => $this->day(9),
                'endDate' => $this->day(14),
            ])
            ->assertOk();

        $this->staycation->update(['house_availability' => 'unavailable']);

        $this->submit()->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
    }

    // -------------------------------------------------------- past arrivals

    public function test_it_rejects_an_arrival_date_in_the_past(): void
    {
        $this->submit([
            'startDate' => $this->day(-1),
            'endDate' => $this->day(4),
        ])->assertSessionHasErrors('startDate');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_it_accepts_an_arrival_date_of_today(): void
    {
        $this->submit([
            'startDate' => $this->day(0),
            'endDate' => $this->day(3),
        ])->assertRedirect(route('BookingHistory.index'));

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_the_preview_rejects_an_arrival_date_in_the_past(): void
    {
        $this->actingAs($this->customer)
            ->post(route('booking.preview', $this->staycation), [
                'name' => 'Preview Customer',
                'phone' => '09123456789',
                'guest_number' => 2,
                'startDate' => $this->day(-1),
                'endDate' => $this->day(4),
            ])
            ->assertSessionHasErrors('startDate');
    }

    // ------------------------------------------------------- proof lifecycle

    public function test_a_submission_is_refused_when_the_proof_cannot_be_stored(): void
    {
        Storage::shouldReceive('disk')
            ->andThrow(new \RuntimeException('disk offline'));

        $this->submit()->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_proof_is_cleaned_up_when_persistence_throws(): void
    {
        $this->withoutExceptionHandling();

        // Force the write inside the transaction to fail after the proof is on
        // disk, which is the case an availability check cannot catch.
        Booking::creating(fn () => throw new RuntimeException('database exploded'));

        $thrown = null;

        try {
            $this->submit();
        } catch (Throwable $exception) {
            $thrown = $exception;
        } finally {
            Booking::flushEventListeners();
        }

        $this->assertInstanceOf(RuntimeException::class, $thrown);
        $this->assertSame('database exploded', $thrown->getMessage());
        $this->assertDatabaseCount('bookings', 0);
        $this->assertEmpty(Storage::disk(PaymentProofService::DISK)->files(PaymentProofService::DIRECTORY));
    }

    public function test_a_rejected_submission_does_not_leave_its_payment_proof_behind(): void
    {
        Booking::factory()->for($this->staycation)->forDates($this->day(9), $this->day(14))->create();

        $this->submit()->assertSessionHas('error');

        $this->assertEmpty(Storage::disk(PaymentProofService::DISK)->files(PaymentProofService::DIRECTORY));
    }
}
