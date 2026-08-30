<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;

/**
 * Marking finished stays as completed used to happen while rendering the admin
 * dashboard, so opening a read-only page rewrote booking rows. It is now an
 * explicit scheduled command.
 */
class BookingCompletionTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    private Staycation $staycation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staycation = Staycation::factory()->create();
    }

    private function pastBooking(BookingStatus $status): Booking
    {
        return Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(-20), $this->day(-15))
            ->create(['status' => $status->value]);
    }

    // ------------------------------------------------- dashboard reads only

    public function test_opening_the_dashboard_does_not_change_any_booking(): void
    {
        $confirmed = $this->pastBooking(BookingStatus::Confirmed);
        $approved = $this->pastBooking(BookingStatus::Approved);
        $pending = $this->pastBooking(BookingStatus::Pending);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->assertSame(BookingStatus::Confirmed->value, $confirmed->refresh()->status);
        $this->assertSame(BookingStatus::Approved->value, $approved->refresh()->status);
        $this->assertSame(BookingStatus::Pending->value, $pending->refresh()->status);
    }

    public function test_opening_the_dashboard_does_not_touch_updated_at(): void
    {
        $booking = $this->pastBooking(BookingStatus::Confirmed);
        $before = $booking->updated_at;

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->assertEquals($before, $booking->refresh()->updated_at);
    }

    // ------------------------------------------------------- the command

    public function test_the_command_completes_finished_stays(): void
    {
        $confirmed = $this->pastBooking(BookingStatus::Confirmed);
        $approved = $this->pastBooking(BookingStatus::Approved);

        $this->artisan('bookings:complete-past')->assertSuccessful();

        $this->assertSame(BookingStatus::Completed->value, $confirmed->refresh()->status);
        $this->assertSame(BookingStatus::Completed->value, $approved->refresh()->status);
    }

    public function test_the_command_leaves_stays_that_have_not_finished(): void
    {
        $future = Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(10), $this->day(15))
            ->create(['status' => BookingStatus::Confirmed->value]);

        $this->artisan('bookings:complete-past')->assertSuccessful();

        $this->assertSame(BookingStatus::Confirmed->value, $future->refresh()->status);
    }

    public function test_the_command_does_not_resurrect_declined_or_cancelled_stays(): void
    {
        $declined = $this->pastBooking(BookingStatus::Declined);
        $cancelled = $this->pastBooking(BookingStatus::Cancelled);

        $this->artisan('bookings:complete-past')->assertSuccessful();

        $this->assertSame(BookingStatus::Declined->value, $declined->refresh()->status);
        $this->assertSame(BookingStatus::Cancelled->value, $cancelled->refresh()->status);
    }

    public function test_the_command_leaves_a_stay_still_awaiting_a_decision(): void
    {
        $pending = $this->pastBooking(BookingStatus::Pending);

        $this->artisan('bookings:complete-past')->assertSuccessful();

        $this->assertSame(BookingStatus::Pending->value, $pending->refresh()->status);
    }

    public function test_the_command_never_touches_payment_state(): void
    {
        $booking = $this->pastBooking(BookingStatus::Confirmed);
        $booking->forceFill([
            'payment_status' => PaymentStatus::Paid->value,
            'amount_paid' => '6000.00',
        ])->save();

        $this->artisan('bookings:complete-past')->assertSuccessful();

        $booking->refresh();

        $this->assertSame(BookingStatus::Completed->value, $booking->status);
        $this->assertSame(PaymentStatus::Paid->value, $booking->payment_status);
        $this->assertSame('6000.00', $booking->amount_paid);
    }

    /**
     * The sweep runs from cron with nobody signed in, which is why the audit
     * actor column had to accept null.
     */
    public function test_the_command_records_a_system_actor_in_the_audit_log(): void
    {
        $this->pastBooking(BookingStatus::Confirmed);

        $this->artisan('bookings:complete-past')->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => null,
            'action' => 'Booking Completed',
        ]);
    }

    public function test_the_command_is_idempotent(): void
    {
        $booking = $this->pastBooking(BookingStatus::Confirmed);

        $this->artisan('bookings:complete-past')->assertSuccessful();
        $this->artisan('bookings:complete-past')->assertSuccessful();

        $this->assertSame(BookingStatus::Completed->value, $booking->refresh()->status);
        $this->assertDatabaseCount('audit_logs', 1);
    }
}
