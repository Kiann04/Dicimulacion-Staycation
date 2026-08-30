<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Staycation;
use App\Models\User;
use App\Services\BookingPaymentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;

class BookingAuthorizationTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    public function test_the_public_booking_form_renders_its_review_summary(): void
    {
        $staycation = Staycation::factory()->create();
        $booking = Booking::factory()->for($staycation)->create();

        Review::factory()->create([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->getKey(),
            'staycation_id' => $staycation->getKey(),
            'rating' => 4,
            'comment' => 'Lovely stay',
        ]);

        $response = $this->get(route('booking.form', $staycation));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('totalReviews'));
        $this->assertSame(4.0, $response->viewData('averageRating'));
        $this->assertSame(1, $response->viewData('starCounts')[4]);
        $this->assertSame(0, $response->viewData('starCounts')[5]);
    }

    public function test_a_guest_cannot_open_the_booking_history(): void
    {
        $this->get(route('BookingHistory.index'))->assertRedirect(route('login'));
    }

    public function test_the_booking_history_only_lists_the_signed_in_customers_bookings(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();

        $own = Booking::factory()->for($customer)->create();
        $theirs = Booking::factory()->for($other)->create();

        $response = $this->actingAs($customer)->get(route('BookingHistory.index'));

        $response->assertOk();

        $listed = $response->viewData('bookings')->pluck('id')->all();

        $this->assertSame([$own->getKey()], $listed);
        $this->assertNotContains($theirs->getKey(), $listed);
    }

    public function test_a_customer_can_cancel_their_own_pending_booking(): void
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()->for($customer)->status(BookingStatus::Pending)->create();

        $this->actingAs($customer)
            ->delete(route('BookingHistory.cancel', $booking))
            ->assertRedirect(route('BookingHistory.index'));

        $this->assertSoftDeleted($booking);
    }

    public function test_a_customer_cannot_cancel_another_customers_booking(): void
    {
        $booking = Booking::factory()->status(BookingStatus::Pending)->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('BookingHistory.cancel', $booking))
            ->assertForbidden();

        $this->assertNotSoftDeleted($booking);
    }

    public function test_a_customer_cannot_cancel_a_booking_that_is_already_confirmed(): void
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()->for($customer)->status(BookingStatus::Confirmed)->create();

        $this->actingAs($customer)
            ->delete(route('BookingHistory.cancel', $booking))
            ->assertForbidden();

        $this->assertNotSoftDeleted($booking);
    }

    public function test_an_administrator_can_cancel_any_booking(): void
    {
        $booking = Booking::factory()->status(BookingStatus::Pending)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('BookingHistory.cancel', $booking))
            ->assertRedirect();

        $this->assertSoftDeleted($booking);
    }

    // ------------------------- cancellation decided on the locked row

    /**
     * The customer's page was rendered while the booking was still pending. By
     * the time they click cancel an administrator has confirmed and taken
     * payment, so the booking is no longer theirs to cancel.
     */
    public function test_a_stale_pending_view_cannot_cancel_a_now_confirmed_booking(): void
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()->for($customer)->status(BookingStatus::Pending)->create();

        // The admin action that lands between the page render and the click.
        Booking::whereKey($booking->getKey())->update([
            'status' => BookingStatus::Confirmed->value,
            'payment_status' => 'paid',
        ]);

        $this->actingAs($customer)
            ->delete(route('BookingHistory.cancel', $booking))
            ->assertForbidden();

        $this->assertNotSoftDeleted($booking);
        $this->assertSame(BookingStatus::Confirmed->value, $booking->fresh()->status);
    }

    public function test_the_service_refuses_a_cancellation_the_locked_row_disallows(): void
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()->for($customer)->status(BookingStatus::Pending)->create();

        Booking::whereKey($booking->getKey())->update(['status' => BookingStatus::Completed->value]);

        $this->expectException(AuthorizationException::class);

        try {
            app(BookingPaymentService::class)->cancel($booking, $customer);
        } finally {
            $this->assertSame(BookingStatus::Completed->value, $booking->fresh()->status);
        }
    }

    public function test_a_cancelled_booking_cannot_be_cancelled_again_by_its_owner(): void
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()->for($customer)->status(BookingStatus::Cancelled)->create();

        $this->actingAs($customer)
            ->delete(route('BookingHistory.cancel', $booking))
            ->assertForbidden();
    }

    public function test_a_completed_booking_cannot_be_cancelled_by_its_owner(): void
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()->for($customer)->status(BookingStatus::Completed)->create();

        $this->actingAs($customer)
            ->delete(route('BookingHistory.cancel', $booking))
            ->assertForbidden();
    }

    public function test_staff_cannot_cancel_a_customers_booking(): void
    {
        $booking = Booking::factory()->status(BookingStatus::Pending)->create();

        $this->actingAs(User::factory()->staff()->create())
            ->delete(route('BookingHistory.cancel', $booking))
            ->assertForbidden();

        $this->assertNotSoftDeleted($booking);
    }

    public function test_a_guest_cannot_cancel_a_booking(): void
    {
        $booking = Booking::factory()->status(BookingStatus::Pending)->create();

        $this->delete(route('BookingHistory.cancel', $booking))
            ->assertRedirect(route('login'));

        $this->assertNotSoftDeleted($booking);
    }

    public function test_a_cancelled_booking_no_longer_holds_its_dates(): void
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()
            ->for($customer)
            ->status(BookingStatus::Pending)
            ->forDates($this->day(70), $this->day(75))
            ->create();

        $this->actingAs($customer)->delete(route('BookingHistory.cancel', $booking));

        $this->assertSame(
            BookingStatus::Cancelled->value,
            Booking::withTrashed()->find($booking->getKey())->status
        );
    }
}
