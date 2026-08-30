<?php

namespace Tests\Feature;

use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;

class BlockedDateTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    private User $admin;

    private Staycation $staycation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->staycation = Staycation::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function block(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->admin)->post(route('admin.blocked_dates.store'), array_merge([
            'staycation_id' => $this->staycation->getKey(),
            'start_date' => $this->day(40),
            'end_date' => $this->day(42),
            'reason' => 'Maintenance',
        ], $overrides));
    }

    public function test_an_administrator_can_block_a_free_range(): void
    {
        $this->block()->assertSessionHas('success');

        $this->assertDatabaseCount('blocked_dates', 1);
    }

    public function test_it_refuses_to_block_a_range_holding_an_active_booking(): void
    {
        Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(41), $this->day(44))
            ->create();

        $this->block()->assertSessionHas('error');

        $this->assertDatabaseCount('blocked_dates', 0);
    }

    public function test_it_refuses_when_a_booking_merely_touches_the_range(): void
    {
        // A stay of day 38 -> 41 holds the nights 38, 39 and 40; day 40 is inside
        // the block, so the block must be refused.
        Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(38), $this->day(41))
            ->create();

        $this->block()->assertSessionHas('error');

        $this->assertDatabaseCount('blocked_dates', 0);
    }

    public function test_a_booking_that_checks_out_on_the_first_blocked_day_does_not_conflict(): void
    {
        // Checkout on day 40 means the last night held is day 39.
        Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(37), $this->day(40))
            ->create();

        $this->block()->assertSessionHas('success');

        $this->assertDatabaseCount('blocked_dates', 1);
    }

    public function test_a_booking_arriving_the_day_after_the_block_does_not_conflict(): void
    {
        Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(43), $this->day(46))
            ->create();

        $this->block()->assertSessionHas('success');
    }

    public function test_a_declined_booking_does_not_prevent_a_block(): void
    {
        Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(41), $this->day(44))
            ->create(['status' => 'declined']);

        $this->block()->assertSessionHas('success');
    }

    public function test_a_booking_on_another_staycation_does_not_prevent_a_block(): void
    {
        Booking::factory()
            ->for(Staycation::factory()->create())
            ->forDates($this->day(41), $this->day(44))
            ->create();

        $this->block()->assertSessionHas('success');
    }

    public function test_a_booking_with_an_unrecognised_status_still_prevents_a_block(): void
    {
        $booking = Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(41), $this->day(44))
            ->create();

        $booking->forceFill(['status' => 'legacy_unknown_value'])->save();

        $this->block()->assertSessionHas('error');

        $this->assertDatabaseCount('blocked_dates', 0);
    }

    public function test_it_rejects_an_end_before_the_start(): void
    {
        $this->block([
            'start_date' => $this->day(42),
            'end_date' => $this->day(40),
        ])->assertSessionHasErrors('end_date');
    }

    public function test_it_rejects_an_unknown_staycation(): void
    {
        $this->block(['staycation_id' => 99999])->assertSessionHasErrors('staycation_id');
    }

    public function test_a_customer_cannot_block_dates(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.blocked_dates.store'), [
                'staycation_id' => $this->staycation->getKey(),
                'start_date' => $this->day(40),
                'end_date' => $this->day(42),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('blocked_dates', 0);
    }

    public function test_a_blocked_range_then_prevents_a_customer_booking(): void
    {
        $this->block()->assertSessionHas('success');

        $this->assertDatabaseCount('blocked_dates', 1);
        $this->assertTrue(
            BlockedDate::query()->where('staycation_id', $this->staycation->getKey())->exists()
        );
    }
}
