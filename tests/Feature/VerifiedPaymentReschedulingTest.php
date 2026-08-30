<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Exceptions\PaymentAdjustmentRequired;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use App\Services\BookingInventoryService;
use App\Services\BookingPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;

/**
 * Once money has been verified as received, a booking's price is frozen.
 *
 * Repricing a paid booking would silently invent an unrecorded balance owed by
 * the customer, or an unrecorded refund owed to them. Neither may happen as a
 * side effect of moving dates.
 */
class VerifiedPaymentReschedulingTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    private User $admin;

    private Staycation $staycation;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->admin = User::factory()->admin()->create();
        $this->staycation = Staycation::factory()->create(['house_price' => 2000]);
        $this->actingAs($this->admin);
    }

    /**
     * A five-night stay at 2000/night for two guests: 10,000.00.
     */
    private function booking(?PaymentStatus $verifyAs = null): Booking
    {
        $booking = Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(30), $this->day(35))
            ->create([
                'guest_number' => 2,
                'total_price' => 10000,
                'amount_paid' => 0,
                'payment_status' => PaymentStatus::Pending->value,
            ]);

        if ($verifyAs !== null) {
            app(BookingPaymentService::class)->verifyPayment($booking, $verifyAs);
            $booking->refresh();
        }

        return $booking;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function reschedule(Booking $booking, array $overrides = []): TestResponse
    {
        return $this->put(route('admin.bookings.update', $booking), array_merge([
            'staycation_id' => $this->staycation->getKey(),
            'name' => $booking->name,
            'phone' => '09123456789',
            'guest_number' => 2,
            'start_date' => $this->day(50),
            'end_date' => $this->day(55),
        ], $overrides));
    }

    // ------------------------------------------------- unverified may reprice

    public function test_an_unpaid_booking_may_be_rescheduled_and_repriced(): void
    {
        $booking = $this->booking();
        app(BookingPaymentService::class)->verifyPayment($booking, PaymentStatus::Unpaid);

        // Three nights instead of five.
        $this->reschedule($booking, ['end_date' => $this->day(53)])->assertSessionHas('success');

        $this->assertSame('6000.00', $booking->refresh()->total_price);
    }

    public function test_a_pending_booking_may_be_rescheduled_and_repriced(): void
    {
        $booking = $this->booking();

        $this->reschedule($booking, ['end_date' => $this->day(53)])->assertSessionHas('success');

        $this->assertSame('6000.00', $booking->refresh()->total_price);
    }

    // ------------------------------------------------- verified is frozen

    public function test_a_half_paid_booking_may_move_to_dates_costing_the_same(): void
    {
        $booking = $this->booking(PaymentStatus::HalfPaid);

        $this->reschedule($booking)->assertSessionHas('success');

        $booking->refresh();

        $this->assertSame($this->day(50), $booking->start_date->toDateString());
        $this->assertSame('10000.00', $booking->total_price);
        $this->assertSame('5000.00', $booking->amount_paid);
    }

    public function test_a_half_paid_booking_may_not_move_to_dates_costing_less(): void
    {
        $booking = $this->booking(PaymentStatus::HalfPaid);

        $this->reschedule($booking, ['end_date' => $this->day(53)])->assertSessionHas('error');

        $this->assertFinancialsUnchanged($booking, '10000.00', '5000.00', PaymentStatus::HalfPaid);
    }

    public function test_a_paid_booking_may_move_to_dates_costing_the_same(): void
    {
        $booking = $this->booking(PaymentStatus::Paid);

        $this->reschedule($booking)->assertSessionHas('success');

        $booking->refresh();

        $this->assertSame($this->day(50), $booking->start_date->toDateString());
        $this->assertSame('10000.00', $booking->total_price);
        $this->assertSame('10000.00', $booking->amount_paid);
    }

    public function test_a_paid_booking_may_not_move_to_dates_costing_less(): void
    {
        $booking = $this->booking(PaymentStatus::Paid);

        $this->reschedule($booking, ['end_date' => $this->day(53)])->assertSessionHas('error');

        $this->assertFinancialsUnchanged($booking, '10000.00', '10000.00', PaymentStatus::Paid);
    }

    public function test_a_paid_booking_may_not_move_to_dates_costing_more(): void
    {
        $booking = $this->booking(PaymentStatus::Paid);

        $this->reschedule($booking, ['end_date' => $this->day(57)])->assertSessionHas('error');

        $this->assertFinancialsUnchanged($booking, '10000.00', '10000.00', PaymentStatus::Paid);
    }

    public function test_a_paid_booking_may_not_be_repriced_by_adding_a_guest(): void
    {
        $booking = $this->booking(PaymentStatus::Paid);

        // A seventh guest would add the extra-guest fee.
        $this->reschedule($booking, ['guest_number' => 7])->assertSessionHas('error');

        $this->assertFinancialsUnchanged($booking, '10000.00', '10000.00', PaymentStatus::Paid);
        $this->assertSame(2, $booking->refresh()->guest_number);
    }

    public function test_a_rejected_reschedule_leaves_the_dates_alone_too(): void
    {
        $booking = $this->booking(PaymentStatus::Paid);

        $this->reschedule($booking, ['end_date' => $this->day(53)])->assertSessionHas('error');

        $this->assertSame($this->day(30), $booking->refresh()->start_date->toDateString());
    }

    public function test_the_refusal_explains_that_a_payment_adjustment_is_needed(): void
    {
        $booking = $this->booking(PaymentStatus::Paid);

        $response = $this->reschedule($booking, ['end_date' => $this->day(53)]);

        $this->assertStringContainsString(
            'verified payment',
            (string) session('error')
        );
        $response->assertSessionHas('error');
    }

    public function test_the_service_raises_a_payment_adjustment_error(): void
    {
        $booking = $this->booking(PaymentStatus::Paid);

        $this->expectException(PaymentAdjustmentRequired::class);

        app(BookingInventoryService::class)->rescheduleBooking(
            $booking,
            $this->staycation->getKey(),
            2,
            $this->dayAsCarbon(50),
            $this->dayAsCarbon(53),
        );
    }

    /**
     * An inventory operation may never write payment fields, even if a caller
     * tries to smuggle them through the details array.
     */
    public function test_financial_fields_cannot_be_smuggled_through_a_reschedule(): void
    {
        $booking = $this->booking();

        app(BookingInventoryService::class)->rescheduleBooking(
            $booking,
            $this->staycation->getKey(),
            2,
            $this->dayAsCarbon(50),
            $this->dayAsCarbon(55),
            [
                'name' => 'Legitimate change',
                'payment_status' => PaymentStatus::Paid->value,
                'amount_paid' => '99999.00',
                'status' => 'confirmed',
            ],
        );

        $booking->refresh();

        $this->assertSame('Legitimate change', $booking->name);
        $this->assertSame(PaymentStatus::Pending->value, $booking->payment_status);
        $this->assertSame('0.00', $booking->amount_paid);
        $this->assertSame('pending', $booking->status);
    }

    private function assertFinancialsUnchanged(
        Booking $booking,
        string $total,
        string $paid,
        PaymentStatus $status,
    ): void {
        $booking->refresh();

        $this->assertSame($total, $booking->total_price);
        $this->assertSame($paid, $booking->amount_paid);
        $this->assertSame($status->value, $booking->payment_status);
    }
}
