<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use App\Services\BookingPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;

/**
 * An administrator editing a booking must satisfy the same domain rules a
 * customer creating one does.
 */
class AdminBookingUpdateTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    private User $admin;

    private Staycation $staycation;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->staycation = Staycation::factory()->create(['house_price' => 2000]);
        $this->booking = Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(30), $this->day(35))
            ->create(['guest_number' => 2, 'total_price' => 10000]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function update(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->admin)->put(
            route('admin.bookings.update', $this->booking),
            array_merge([
                'staycation_id' => $this->staycation->getKey(),
                'name' => 'Updated Guest',
                'phone' => '09123456789',
                'guest_number' => 2,
                'start_date' => $this->day(50),
                'end_date' => $this->day(55),
            ], $overrides)
        );
    }

    public function test_a_valid_update_moves_the_booking(): void
    {
        $this->update()->assertSessionHas('success');

        $this->booking->refresh();

        $this->assertSame($this->day(50), $this->booking->start_date->toDateString());
        $this->assertSame($this->day(55), $this->booking->end_date->toDateString());
        $this->assertSame('Updated Guest', $this->booking->name);
    }

    public function test_it_reprices_the_booking_from_the_server(): void
    {
        // 3 nights x 2000, plus one guest beyond the included six.
        $this->update([
            'start_date' => $this->day(50),
            'end_date' => $this->day(53),
            'guest_number' => 7,
            'total_price' => 1,
        ])->assertSessionHas('success');

        $this->booking->refresh();

        $this->assertSame('6500.00', $this->booking->total_price);
        $this->assertEquals(2000, $this->booking->price_per_day);
    }

    public function test_it_rejects_a_zero_night_stay(): void
    {
        $this->update([
            'start_date' => $this->day(50),
            'end_date' => $this->day(50),
        ])->assertSessionHasErrors('end_date');

        $this->assertSame($this->day(30), $this->booking->refresh()->start_date->toDateString());
    }

    public function test_it_rejects_a_departure_before_the_arrival(): void
    {
        $this->update([
            'start_date' => $this->day(55),
            'end_date' => $this->day(50),
        ])->assertSessionHasErrors('end_date');
    }

    public function test_it_rejects_more_guests_than_the_maximum_capacity(): void
    {
        $this->update(['guest_number' => 9])->assertSessionHasErrors('guest_number');

        $this->assertSame(2, $this->booking->refresh()->guest_number);
    }

    public function test_it_rejects_fewer_than_one_guest(): void
    {
        $this->update(['guest_number' => 0])->assertSessionHasErrors('guest_number');
    }

    public function test_it_rejects_an_unknown_staycation(): void
    {
        $this->update(['staycation_id' => 99999])->assertSessionHasErrors('staycation_id');
    }

    public function test_it_rejects_a_staycation_that_is_not_open_for_booking(): void
    {
        $closed = Staycation::factory()->unavailable()->create();

        $this->update(['staycation_id' => $closed->getKey()])->assertSessionHas('error');

        $this->assertSame($this->staycation->getKey(), $this->booking->refresh()->staycation_id);
    }

    public function test_it_rejects_dates_that_overlap_another_booking(): void
    {
        Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(50), $this->day(55))
            ->create();

        $this->update()->assertSessionHas('error');

        $this->assertSame($this->day(30), $this->booking->refresh()->start_date->toDateString());
    }

    public function test_it_rejects_dates_that_overlap_a_blocked_range(): void
    {
        BlockedDate::factory()->for($this->staycation)->create([
            'start_date' => $this->day(51),
            'end_date' => $this->day(52),
        ]);

        $this->update()->assertSessionHas('error');

        $this->assertSame($this->day(30), $this->booking->refresh()->start_date->toDateString());
    }

    public function test_a_booking_does_not_conflict_with_its_own_current_dates(): void
    {
        $this->update([
            'start_date' => $this->day(31),
            'end_date' => $this->day(36),
        ])->assertSessionHas('success');

        $this->assertSame($this->day(31), $this->booking->refresh()->start_date->toDateString());
    }

    public function test_a_declined_booking_does_not_stand_in_the_way(): void
    {
        Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(50), $this->day(55))
            ->create(['status' => 'declined']);

        $this->update()->assertSessionHas('success');
    }

    public function test_an_administrator_may_still_correct_a_past_booking(): void
    {
        $this->update([
            'start_date' => $this->day(-20),
            'end_date' => $this->day(-15),
        ])->assertSessionHas('success');

        $this->assertSame($this->day(-20), $this->booking->refresh()->start_date->toDateString());
    }

    // ------------------------------------------------------------- deletion

    public function test_a_booking_awaiting_verification_can_still_be_deleted(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('admin.bookings.delete', $this->booking))
            ->assertRedirect();

        $this->assertDatabaseMissing('bookings', ['id' => $this->booking->getKey()]);
        $this->assertDatabaseHas('booking_history', ['booking_id' => $this->booking->getKey()]);
    }

    public function test_a_booking_with_a_verified_payment_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin);

        app(BookingPaymentService::class)->verifyPayment($this->booking, PaymentStatus::Paid);

        $this->delete(route('admin.bookings.delete', $this->booking))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('bookings', ['id' => $this->booking->getKey()]);
    }

    public function test_a_customer_cannot_update_a_booking(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.bookings.update', $this->booking), [
                'staycation_id' => $this->staycation->getKey(),
                'name' => 'Hijacked',
                'phone' => '09123456789',
                'guest_number' => 2,
                'start_date' => $this->day(50),
                'end_date' => $this->day(55),
            ])
            ->assertForbidden();

        $this->assertNotSame('Hijacked', $this->booking->refresh()->name);
    }
}
