<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BookingNotArchivable;
use App\Exceptions\InvalidBookingTransition;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Staycation;
use App\Models\User;
use App\Services\BookingArchiveService;
use App\Services\BookingInventoryService;
use App\Services\BookingPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;

/**
 * Every decision that depends on mutable booking state must be made against the
 * locked database row, never against whatever the caller was holding.
 *
 * Each "stale" test here builds a genuinely out-of-date model instance — the
 * shape a second concurrent request has — and proves the service refuses it.
 */
class LockedStateDecisionTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    private BookingPaymentService $payments;

    private BookingArchiveService $archive;

    private Staycation $staycation;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->payments = app(BookingPaymentService::class);
        $this->archive = app(BookingArchiveService::class);
        $this->staycation = Staycation::factory()->create(['house_price' => 2000]);
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function booking(array $attributes = []): Booking
    {
        return Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(30), $this->day(35))
            ->create(array_merge([
                'guest_number' => 2,
                'total_price' => 10000,
                'amount_paid' => 0,
                'status' => BookingStatus::Pending->value,
                'payment_status' => PaymentStatus::Pending->value,
            ], $attributes));
    }

    private function halfPaid(BookingStatus $status): Booking
    {
        $booking = $this->booking();
        $this->payments->verifyPayment($booking, PaymentStatus::HalfPaid);

        // Placed directly so a terminal state can be reached without going
        // through a transition that would itself refuse it.
        $booking->forceFill(['status' => $status->value])->save();

        return $booking->refresh();
    }

    // ------------------------------- P1-1 settlement must not reopen

    public function test_a_half_paid_active_booking_settles(): void
    {
        $booking = $this->halfPaid(BookingStatus::Confirmed);

        $this->payments->settleRemainingBalance($booking);

        $this->assertSame(PaymentStatus::Paid->value, $booking->refresh()->payment_status);
        $this->assertSame('10000.00', $booking->amount_paid);
    }

    /**
     * @return array<string, array{0: BookingStatus}>
     */
    public static function terminalStatusProvider(): array
    {
        return [
            'cancelled' => [BookingStatus::Cancelled],
            'declined' => [BookingStatus::Declined],
            'completed' => [BookingStatus::Completed],
        ];
    }

    /**
     * @dataProvider terminalStatusProvider
     */
    public function test_settlement_never_reopens_a_terminal_booking(BookingStatus $status): void
    {
        $booking = $this->halfPaid($status);

        $this->expectException(InvalidBookingTransition::class);

        try {
            $this->payments->settleRemainingBalance($booking);
        } finally {
            $booking->refresh();
            $this->assertSame($status->value, $booking->status);
            $this->assertSame(PaymentStatus::HalfPaid->value, $booking->payment_status);
            $this->assertSame('5000.00', $booking->amount_paid);
        }
    }

    public function test_an_already_paid_booking_cannot_be_settled_again(): void
    {
        $booking = $this->booking();
        $this->payments->verifyPayment($booking, PaymentStatus::Paid);

        $this->expectException(InvalidBookingTransition::class);

        $this->payments->settleRemainingBalance($booking);
    }

    public function test_settlement_is_judged_on_the_locked_row_not_a_stale_instance(): void
    {
        $booking = $this->halfPaid(BookingStatus::Confirmed);

        // The caller's instance still reads confirmed; the row has since been
        // cancelled by someone else.
        Booking::whereKey($booking->getKey())->update(['status' => BookingStatus::Cancelled->value]);
        $this->assertSame(BookingStatus::Confirmed->value, $booking->status);

        $this->expectException(InvalidBookingTransition::class);

        try {
            $this->payments->settleRemainingBalance($booking);
        } finally {
            $this->assertSame('5000.00', $booking->fresh()->amount_paid);
        }
    }

    // --------------------------------- P1-2 archive eligibility under lock

    public function test_an_unverified_booking_can_be_archived(): void
    {
        $booking = $this->booking();

        $this->archive->archiveAndDelete($booking);

        $this->assertDatabaseMissing('bookings', ['id' => $booking->getKey()]);
        $this->assertDatabaseCount('booking_history', 1);
    }

    /**
     * @return array<string, array{0: PaymentStatus}>
     */
    public static function verifiedPaymentProvider(): array
    {
        return [
            'half paid' => [PaymentStatus::HalfPaid],
            'paid' => [PaymentStatus::Paid],
        ];
    }

    /**
     * @dataProvider verifiedPaymentProvider
     */
    public function test_a_verified_booking_is_never_archived(PaymentStatus $status): void
    {
        $booking = $this->booking();
        $this->payments->verifyPayment($booking, $status);

        $this->expectException(BookingNotArchivable::class);

        try {
            $this->archive->archiveAndDelete($booking);
        } finally {
            $this->assertDatabaseHas('bookings', ['id' => $booking->getKey()]);
            $this->assertDatabaseCount('booking_history', 0);
        }
    }

    /**
     * The controller's model says the booking is unverified; the row says
     * otherwise by the time the service locks it.
     */
    public function test_archive_eligibility_is_judged_on_the_locked_row(): void
    {
        $booking = $this->booking();

        Booking::whereKey($booking->getKey())->update([
            'payment_status' => PaymentStatus::Paid->value,
            'amount_paid' => '10000.00',
        ]);

        $this->assertSame(PaymentStatus::Pending->value, $booking->payment_status);

        $this->expectException(BookingNotArchivable::class);

        try {
            $this->archive->archiveAndDelete($booking);
        } finally {
            $this->assertDatabaseHas('bookings', ['id' => $booking->getKey()]);
            $this->assertDatabaseCount('booking_history', 0);
        }
    }

    public function test_the_endpoint_reports_a_refused_archive_rather_than_failing(): void
    {
        $booking = $this->booking();
        $this->payments->verifyPayment($booking, PaymentStatus::Paid);

        $this->delete(route('admin.bookings.delete', $booking))->assertSessionHas('error');

        $this->assertDatabaseHas('bookings', ['id' => $booking->getKey()]);
    }

    // ------------------------------- P1-3 completion rechecks under lock

    public function test_a_finished_stay_is_completed(): void
    {
        $booking = $this->booking([
            'status' => BookingStatus::Confirmed->value,
            'start_date' => $this->day(-20),
            'end_date' => $this->day(-15),
        ]);

        $this->payments->complete($booking);

        $this->assertSame(BookingStatus::Completed->value, $booking->refresh()->status);
    }

    public function test_a_stay_that_has_not_finished_is_never_completed(): void
    {
        $booking = $this->booking([
            'status' => BookingStatus::Confirmed->value,
            'start_date' => $this->day(10),
            'end_date' => $this->day(15),
        ]);

        $this->expectException(InvalidBookingTransition::class);

        try {
            $this->payments->complete($booking);
        } finally {
            $this->assertSame(BookingStatus::Confirmed->value, $booking->refresh()->status);
        }
    }

    public function test_a_stay_ending_today_is_not_yet_complete(): void
    {
        $booking = $this->booking([
            'status' => BookingStatus::Confirmed->value,
            'start_date' => $this->day(-3),
            'end_date' => $this->day(0),
        ]);

        $this->expectException(InvalidBookingTransition::class);

        $this->payments->complete($booking);
    }

    /**
     * The scheduler nominates a candidate whose end_date was past; an
     * administrator moves it into the future before the lock is taken.
     */
    public function test_the_scheduler_cannot_complete_a_booking_rescheduled_into_the_future(): void
    {
        $booking = $this->booking([
            'status' => BookingStatus::Confirmed->value,
            'start_date' => $this->day(-20),
            'end_date' => $this->day(-15),
        ]);

        $candidateIds = Booking::query()
            ->where('end_date', '<', $this->day(0))
            ->whereIn('status', [BookingStatus::Approved->value, BookingStatus::Confirmed->value])
            ->pluck('id');

        $this->assertContains($booking->getKey(), $candidateIds->all());

        // The admin edit that lands between selection and lock.
        Booking::whereKey($booking->getKey())->update([
            'start_date' => $this->day(40),
            'end_date' => $this->day(45),
        ]);

        $this->expectException(InvalidBookingTransition::class);

        try {
            $this->payments->complete($booking->getKey());
        } finally {
            $this->assertSame(BookingStatus::Confirmed->value, $booking->fresh()->status);
        }
    }

    // ------------------------------- terminal rescheduling policy

    /**
     * @dataProvider terminalStatusProvider
     */
    public function test_a_terminal_booking_is_never_rescheduled(BookingStatus $status): void
    {
        $booking = $this->booking(['status' => $status->value]);

        $this->expectException(InvalidBookingTransition::class);

        try {
            app(BookingInventoryService::class)->rescheduleBooking(
                $booking,
                $this->staycation->getKey(),
                2,
                $this->dayAsCarbon(50),
                $this->dayAsCarbon(55),
            );
        } finally {
            $this->assertSame($this->day(30), $booking->fresh()->start_date->toDateString());
        }
    }

    public function test_the_admin_endpoint_reports_a_refused_terminal_reschedule(): void
    {
        $booking = $this->booking(['status' => BookingStatus::Cancelled->value]);

        $this->put(route('admin.bookings.update', $booking), [
            'staycation_id' => $this->staycation->getKey(),
            'name' => $booking->name,
            'phone' => '09123456789',
            'guest_number' => 2,
            'start_date' => $this->day(50),
            'end_date' => $this->day(55),
        ])->assertSessionHas('error');

        $this->assertSame($this->day(30), $booking->fresh()->start_date->toDateString());
    }

    // ------------------------------- unknown legacy status fails closed

    /**
     * @return array<string, array{0: string}>
     */
    public static function unknownStatusProvider(): array
    {
        return [
            'empty' => [''],
            'legacy value' => ['awaiting_confirmation'],
            'typo' => ['aproved'],
            'noise' => ['ON HOLD'],
        ];
    }

    /**
     * @dataProvider unknownStatusProvider
     */
    public function test_an_unknown_status_cannot_be_transitioned(string $status): void
    {
        $booking = $this->booking();
        $booking->forceFill(['status' => $status])->save();

        foreach (['approve', 'decline', 'cancel', 'complete'] as $action) {
            try {
                $this->payments->{$action}($booking->getKey());
                $this->fail("A booking with status [{$status}] should not have been {$action}d.");
            } catch (InvalidBookingTransition $exception) {
                $this->assertStringContainsString('unrecognised status', $exception->getMessage());
            }
        }

        $this->assertSame($status, $booking->fresh()->status);
    }

    public function test_an_unknown_status_cannot_be_confirmed_by_verifying_payment(): void
    {
        $booking = $this->booking();
        $booking->forceFill(['status' => 'legacy_unknown'])->save();

        $this->expectException(InvalidBookingTransition::class);

        try {
            $this->payments->verifyPayment($booking->getKey(), PaymentStatus::Paid);
        } finally {
            $booking->refresh();
            $this->assertSame('legacy_unknown', $booking->status);
            $this->assertSame('0.00', $booking->amount_paid);
        }
    }

    public function test_an_unknown_status_cannot_be_rescheduled(): void
    {
        $booking = $this->booking();
        $booking->forceFill(['status' => 'legacy_unknown'])->save();

        $this->expectException(InvalidBookingTransition::class);

        app(BookingInventoryService::class)->rescheduleBooking(
            $booking,
            $this->staycation->getKey(),
            2,
            $this->dayAsCarbon(50),
            $this->dayAsCarbon(55),
        );
    }

    public function test_an_unknown_status_is_still_archivable_when_no_money_was_verified(): void
    {
        // Archiving is not a lifecycle transition, so a corrupt status does not
        // trap the record; the payment guard is what matters here.
        $booking = $this->booking();
        $booking->forceFill(['status' => 'legacy_unknown'])->save();

        $this->archive->archiveAndDelete($booking);

        $this->assertDatabaseMissing('bookings', ['id' => $booking->getKey()]);
        $this->assertSame($booking->getKey(), BookingHistory::sole()->booking_id);
    }
}
