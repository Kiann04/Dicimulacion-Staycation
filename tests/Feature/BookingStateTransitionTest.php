<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidBookingTransition;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;

class BookingStateTransitionTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    private BookingPaymentService $payments;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->payments = app(BookingPaymentService::class);
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function booking(array $attributes = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'total_price' => 10000,
            'amount_paid' => 0,
            'status' => BookingStatus::Pending->value,
            'payment_status' => PaymentStatus::Pending->value,
        ], $attributes));
    }

    // ---------------------------------------------------------------- amounts

    public function test_verifying_a_full_payment_records_the_whole_total(): void
    {
        $booking = $this->booking();

        $this->payments->verifyPayment($booking, PaymentStatus::Paid);

        $this->assertSame('10000.00', $booking->refresh()->amount_paid);
        $this->assertSame(BookingStatus::Confirmed->value, $booking->status);
    }

    public function test_verifying_a_half_payment_records_exactly_half(): void
    {
        $booking = $this->booking();

        $this->payments->verifyPayment($booking, PaymentStatus::HalfPaid);

        $this->assertSame('5000.00', $booking->refresh()->amount_paid);
    }

    public function test_a_half_payment_of_an_odd_centavo_total_rounds_half_up(): void
    {
        $booking = $this->booking(['total_price' => '10000.01']);

        $this->payments->verifyPayment($booking, PaymentStatus::HalfPaid);

        $this->assertSame('5000.01', $booking->refresh()->amount_paid);
    }

    public function test_an_unverified_status_never_carries_a_verified_amount(): void
    {
        $booking = $this->booking(['payment_status' => PaymentStatus::Unpaid->value]);

        $this->payments->verifyPayment($booking, PaymentStatus::Pending);

        $this->assertSame('0.00', $booking->refresh()->amount_paid);
    }

    public function test_a_settled_booking_owes_nothing(): void
    {
        $booking = $this->booking();

        $this->payments->verifyPayment($booking, PaymentStatus::Paid);

        $this->assertSame('0.00', $booking->refresh()->remainingBalance()->toDecimalString());
    }

    public function test_a_half_settled_booking_owes_the_remainder(): void
    {
        $booking = $this->booking();

        $this->payments->verifyPayment($booking, PaymentStatus::HalfPaid);

        $this->assertSame('5000.00', $booking->refresh()->remainingBalance()->toDecimalString());
    }

    // ------------------------------------------------- illegal payment moves

    public function test_a_paid_booking_cannot_be_reverted_to_unpaid(): void
    {
        $booking = $this->booking();
        $this->payments->verifyPayment($booking, PaymentStatus::Paid);

        $this->expectException(InvalidBookingTransition::class);

        try {
            $this->payments->verifyPayment($booking, PaymentStatus::Unpaid);
        } finally {
            $this->assertSame(PaymentStatus::Paid->value, $booking->refresh()->payment_status);
            $this->assertSame('10000.00', $booking->amount_paid);
        }
    }

    public function test_a_paid_booking_cannot_be_reverted_to_pending(): void
    {
        $booking = $this->booking();
        $this->payments->verifyPayment($booking, PaymentStatus::Paid);

        $this->expectException(InvalidBookingTransition::class);

        $this->payments->verifyPayment($booking, PaymentStatus::Pending);
    }

    public function test_a_half_paid_booking_cannot_be_reverted_to_unpaid(): void
    {
        $booking = $this->booking();
        $this->payments->verifyPayment($booking, PaymentStatus::HalfPaid);

        $this->expectException(InvalidBookingTransition::class);

        try {
            $this->payments->verifyPayment($booking, PaymentStatus::Unpaid);
        } finally {
            $this->assertSame('5000.00', $booking->refresh()->amount_paid);
        }
    }

    public function test_a_failed_payment_cannot_jump_straight_to_paid(): void
    {
        $booking = $this->booking(['payment_status' => PaymentStatus::Failed->value]);

        $this->expectException(InvalidBookingTransition::class);

        try {
            $this->payments->verifyPayment($booking, PaymentStatus::Paid);
        } finally {
            $this->assertSame('0.00', $booking->refresh()->amount_paid);
        }
    }

    public function test_only_a_half_paid_booking_can_settle_its_remaining_balance(): void
    {
        $booking = $this->booking(['payment_status' => PaymentStatus::Unpaid->value]);

        $this->expectException(InvalidBookingTransition::class);

        $this->payments->settleRemainingBalance($booking);
    }

    public function test_a_half_paid_booking_settles_its_remaining_balance(): void
    {
        $booking = $this->booking();
        $this->payments->verifyPayment($booking, PaymentStatus::HalfPaid);

        $this->payments->settleRemainingBalance($booking);

        $this->assertSame(PaymentStatus::Paid->value, $booking->refresh()->payment_status);
        $this->assertSame('10000.00', $booking->amount_paid);
    }

    // -------------------------------------------------- payment vs. booking

    public function test_marking_a_booking_unpaid_does_not_cancel_it(): void
    {
        $booking = $this->booking(['status' => BookingStatus::Approved->value]);

        $this->payments->verifyPayment($booking, PaymentStatus::Unpaid);

        $booking->refresh();

        $this->assertSame(PaymentStatus::Unpaid->value, $booking->payment_status);
        $this->assertSame(BookingStatus::Approved->value, $booking->status);
        $this->assertTrue($booking->bookingStatus()->blocksAvailability());
    }

    public function test_cancellation_is_its_own_explicit_move(): void
    {
        $booking = $this->booking(['status' => BookingStatus::Approved->value]);

        $this->payments->cancel($booking);

        $this->assertSame(BookingStatus::Cancelled->value, $booking->refresh()->status);
    }

    // -------------------------------------------------- illegal booking moves

    public function test_a_cancelled_booking_cannot_be_approved(): void
    {
        $booking = $this->booking(['status' => BookingStatus::Cancelled->value]);

        $this->expectException(InvalidBookingTransition::class);

        try {
            $this->payments->approve($booking);
        } finally {
            $this->assertSame(BookingStatus::Cancelled->value, $booking->refresh()->status);
        }
    }

    public function test_a_declined_booking_cannot_be_quietly_approved(): void
    {
        $booking = $this->booking(['status' => BookingStatus::Declined->value]);

        $this->expectException(InvalidBookingTransition::class);

        try {
            $this->payments->approve($booking);
        } finally {
            $this->assertSame(BookingStatus::Declined->value, $booking->refresh()->status);
        }
    }

    public function test_a_completed_booking_cannot_be_reopened(): void
    {
        $booking = $this->booking(['status' => BookingStatus::Completed->value]);

        $this->expectException(InvalidBookingTransition::class);

        $this->payments->approve($booking);
    }

    public function test_approving_a_pending_booking_leaves_it_awaiting_payment(): void
    {
        $booking = $this->booking();

        $this->payments->approve($booking);

        $booking->refresh();

        $this->assertSame(BookingStatus::Approved->value, $booking->status);
        $this->assertSame(PaymentStatus::Pending->value, $booking->payment_status);
    }

    public function test_approving_never_unwinds_a_verified_payment(): void
    {
        $booking = $this->booking();
        $this->payments->verifyPayment($booking, PaymentStatus::HalfPaid);

        // Confirmed -> Approved is not a legal booking move.
        $this->expectException(InvalidBookingTransition::class);

        try {
            $this->payments->approve($booking);
        } finally {
            $this->assertSame(PaymentStatus::HalfPaid->value, $booking->refresh()->payment_status);
            $this->assertSame('5000.00', $booking->amount_paid);
        }
    }

    public function test_declining_a_pending_booking_releases_its_dates(): void
    {
        $booking = $this->booking();

        $this->payments->decline($booking);

        $booking->refresh();

        $this->assertSame(BookingStatus::Declined->value, $booking->status);
        $this->assertSame(PaymentStatus::Failed->value, $booking->payment_status);
        $this->assertFalse($booking->bookingStatus()->blocksAvailability());
    }

    public function test_a_confirmed_booking_cannot_be_declined(): void
    {
        $booking = $this->booking();
        $this->payments->verifyPayment($booking, PaymentStatus::Paid);

        $this->expectException(InvalidBookingTransition::class);

        $this->payments->decline($booking);
    }

    public function test_cancelling_a_paid_booking_keeps_the_verified_amount_on_record(): void
    {
        $booking = $this->booking();
        $this->payments->verifyPayment($booking, PaymentStatus::Paid);

        $this->payments->cancel($booking);

        $booking->refresh();

        $this->assertSame(BookingStatus::Cancelled->value, $booking->status);
        $this->assertSame('10000.00', $booking->amount_paid);
        $this->assertSame(PaymentStatus::Paid->value, $booking->payment_status);
    }

    // ------------------------------------------------------- serialization

    /**
     * A transition must be judged against the row in the database, not against
     * whatever the caller happened to be holding. Two administrators acting at
     * once each hold their own stale instance; if the service trusted those,
     * both would pass a "current status" check that only one may pass.
     */
    public function test_a_transition_is_judged_against_the_database_not_the_passed_instance(): void
    {
        $booking = $this->booking();

        // A second reference to the same booking, as a concurrent request would
        // hold. It settles the payment first.
        $concurrent = Booking::find($booking->getKey());
        $this->payments->verifyPayment($concurrent, PaymentStatus::Paid);

        // $booking is now stale: it still believes the payment is pending, which
        // would allow pending -> unpaid. The locked row must refuse it.
        $this->assertSame(PaymentStatus::Pending->value, $booking->getOriginal('payment_status'));

        $this->expectException(InvalidBookingTransition::class);

        try {
            $this->payments->verifyPayment($booking, PaymentStatus::Unpaid);
        } finally {
            $this->assertSame(PaymentStatus::Paid->value, $booking->fresh()->payment_status);
        }
    }

    public function test_a_stale_instance_cannot_settle_a_balance_twice(): void
    {
        $booking = $this->booking();
        $this->payments->verifyPayment($booking, PaymentStatus::HalfPaid);

        $concurrent = Booking::find($booking->getKey());
        $this->payments->settleRemainingBalance($concurrent);

        // The stale instance still reads half_paid; the locked row does not.
        $this->expectException(InvalidBookingTransition::class);

        try {
            $this->payments->settleRemainingBalance($booking);
        } finally {
            $this->assertSame('10000.00', $booking->fresh()->amount_paid);
        }
    }

    public function test_a_stale_instance_cannot_decline_a_booking_someone_else_confirmed(): void
    {
        $booking = $this->booking();

        $concurrent = Booking::find($booking->getKey());
        $this->payments->verifyPayment($concurrent, PaymentStatus::Paid);

        $this->expectException(InvalidBookingTransition::class);

        try {
            $this->payments->decline($booking);
        } finally {
            $this->assertSame(BookingStatus::Confirmed->value, $booking->fresh()->status);
        }
    }

    /**
     * The caller's instance is refreshed from the locked row, so a controller
     * cannot render a response describing state the transition superseded.
     */
    public function test_the_callers_instance_is_brought_up_to_date(): void
    {
        $booking = $this->booking();

        $this->payments->verifyPayment($booking, PaymentStatus::Paid);

        // No refresh() call here on purpose.
        $this->assertSame(PaymentStatus::Paid->value, $booking->payment_status);
        $this->assertSame(BookingStatus::Confirmed->value, $booking->status);
        $this->assertSame('10000.00', $booking->amount_paid);
    }

    public function test_a_transition_can_be_driven_by_id_alone(): void
    {
        $booking = $this->booking();

        $this->payments->verifyPayment($booking->getKey(), PaymentStatus::HalfPaid);

        $this->assertSame('5000.00', $booking->fresh()->amount_paid);
    }

    // ------------------------------------------------------------ endpoints

    public function test_the_admin_endpoint_refuses_an_illegal_payment_move(): void
    {
        $booking = $this->booking();
        $this->payments->verifyPayment($booking, PaymentStatus::Paid);

        $this->post(route('admin.bookings.updatePayment', $booking), [
            'payment_status' => PaymentStatus::Unpaid->value,
        ])->assertSessionHas('error');

        $this->assertSame(PaymentStatus::Paid->value, $booking->refresh()->payment_status);
    }

    public function test_the_admin_endpoint_refuses_to_approve_a_cancelled_booking(): void
    {
        $booking = $this->booking(['status' => BookingStatus::Cancelled->value]);

        $this->post(route('admin.bookings.approve', $booking))->assertSessionHas('error');

        $this->assertSame(BookingStatus::Cancelled->value, $booking->refresh()->status);
    }

    public function test_a_legacy_uppercase_payment_status_is_understood(): void
    {
        $booking = $this->booking(['payment_status' => 'Pending']);

        $this->payments->verifyPayment($booking, PaymentStatus::Paid);

        $this->assertSame(PaymentStatus::Paid->value, $booking->refresh()->payment_status);
    }
}
