<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->admin = User::factory()->admin()->create();
    }

    private function booking(array $attributes = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'total_price' => 10000,
            'amount_paid' => 0,
        ], $attributes));
    }

    public function test_marking_a_booking_paid_settles_the_full_amount(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.updatePayment', $booking), [
                'payment_status' => PaymentStatus::Paid->value,
            ])
            ->assertRedirect();

        $booking->refresh();

        $this->assertSame(PaymentStatus::Paid->value, $booking->payment_status);
        $this->assertSame(BookingStatus::Confirmed->value, $booking->status);
        $this->assertSame('10000.00', $booking->amount_paid);
        $this->assertSame(0.0, $booking->remaining_balance);
    }

    public function test_marking_a_booking_half_paid_settles_half_the_amount(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.updatePayment', $booking), [
                'payment_status' => PaymentStatus::HalfPaid->value,
            ])
            ->assertRedirect();

        $booking->refresh();

        $this->assertSame(PaymentStatus::HalfPaid->value, $booking->payment_status);
        $this->assertSame(BookingStatus::Confirmed->value, $booking->status);
        $this->assertSame('5000.00', $booking->amount_paid);
        $this->assertSame(5000.0, $booking->remaining_balance);
    }

    public function test_a_mixed_case_payment_status_is_normalised(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.updatePayment', $booking), [
                'payment_status' => 'Half Paid',
            ])
            ->assertRedirect();

        $this->assertSame(PaymentStatus::HalfPaid->value, $booking->refresh()->payment_status);
    }

    public function test_an_arbitrary_payment_status_is_rejected(): void
    {
        $booking = $this->booking(['payment_status' => PaymentStatus::Unpaid->value]);

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.updatePayment', $booking), [
                'payment_status' => 'refunded_maybe',
            ])
            ->assertSessionHasErrors('payment_status');

        $this->assertSame(PaymentStatus::Unpaid->value, $booking->refresh()->payment_status);
    }

    public function test_a_half_paid_booking_can_be_marked_fully_paid(): void
    {
        $booking = Booking::factory()->halfPaid()->create(['total_price' => 10000]);
        $booking->update(['amount_paid' => 5000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.markFullyPaid', $booking))
            ->assertRedirect();

        $booking->refresh();

        $this->assertSame(PaymentStatus::Paid->value, $booking->payment_status);
        $this->assertSame('10000.00', $booking->amount_paid);
        $this->assertSame(0.0, $booking->remaining_balance);
    }

    public function test_an_unpaid_booking_cannot_be_marked_fully_paid(): void
    {
        $booking = $this->booking(['payment_status' => PaymentStatus::Unpaid->value]);

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.markFullyPaid', $booking))
            ->assertSessionHas('error');

        $this->assertSame(PaymentStatus::Unpaid->value, $booking->refresh()->payment_status);
    }

    public function test_approving_a_booking_leaves_it_awaiting_payment(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.approve', $booking))
            ->assertRedirect();

        $booking->refresh();

        $this->assertSame(BookingStatus::Approved->value, $booking->status);
        $this->assertSame(PaymentStatus::Pending->value, $booking->payment_status);
    }

    public function test_declining_a_booking_releases_its_dates(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.decline', $booking))
            ->assertRedirect();

        $booking->refresh();

        $this->assertSame(BookingStatus::Declined->value, $booking->status);
        $this->assertFalse($booking->bookingStatus()->blocksAvailability());
    }

    public function test_marking_a_booking_paid_from_the_filter_screen_settles_the_amount(): void
    {
        $booking = Booking::factory()->halfPaid()->create(['total_price' => 10000]);

        $this->actingAs($this->admin)
            ->post(route('admin.bookings.markAsPaid', $booking))
            ->assertOk()
            ->assertJson(['success' => true]);

        $booking->refresh();

        $this->assertSame(PaymentStatus::Paid->value, $booking->payment_status);
        $this->assertSame(BookingStatus::Confirmed->value, $booking->status);
        $this->assertSame('10000.00', $booking->amount_paid);
    }

    public function test_a_customer_cannot_change_payment_status(): void
    {
        $booking = $this->booking(['payment_status' => PaymentStatus::Unpaid->value]);
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->post(route('admin.bookings.updatePayment', $booking), [
                'payment_status' => PaymentStatus::Paid->value,
            ])
            ->assertForbidden();

        $this->assertSame(PaymentStatus::Unpaid->value, $booking->refresh()->payment_status);
    }

    public function test_staff_cannot_change_payment_status(): void
    {
        $booking = $this->booking(['payment_status' => PaymentStatus::Unpaid->value]);
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->post(route('admin.bookings.updatePayment', $booking), [
                'payment_status' => PaymentStatus::Paid->value,
            ])
            ->assertForbidden();

        $this->assertSame(PaymentStatus::Unpaid->value, $booking->refresh()->payment_status);
    }
}
