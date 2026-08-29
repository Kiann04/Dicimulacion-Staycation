<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Staycation;
use App\Services\Booking\BookingAvailabilityService;
use App\Services\Booking\DateRange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The overlap matrix.
 *
 * A stay occupies the half-open interval [start_date, end_date): the guest sleeps
 * every night from check-in up to, but not including, check-out. Every case below
 * is expressed against one existing booking on the 10th-15th so the boundary
 * behaviour is unambiguous.
 */
class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private BookingAvailabilityService $availability;

    private Staycation $staycation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availability = app(BookingAvailabilityService::class);
        $this->staycation = Staycation::factory()->create();
    }

    private function existingBooking(string $status = 'confirmed'): Booking
    {
        return Booking::factory()
            ->for($this->staycation)
            ->forDates('2026-10-10', '2026-10-15')
            ->create(['status' => $status]);
    }

    private function isAvailable(string $start, string $end): bool
    {
        return $this->availability->isAvailable(
            $this->staycation,
            DateRange::fromInput($start, $end),
        );
    }

    public function test_exact_collision_is_rejected(): void
    {
        $this->existingBooking();

        $this->assertFalse($this->isAvailable('2026-10-10', '2026-10-15'));
    }

    public function test_partial_overlap_at_the_beginning_is_rejected(): void
    {
        // New stay ends inside the existing one: 8th-12th vs 10th-15th.
        $this->existingBooking();

        $this->assertFalse($this->isAvailable('2026-10-08', '2026-10-12'));
    }

    public function test_partial_overlap_at_the_end_is_rejected(): void
    {
        // New stay begins inside the existing one: 13th-18th vs 10th-15th.
        $this->existingBooking();

        $this->assertFalse($this->isAvailable('2026-10-13', '2026-10-18'));
    }

    public function test_a_new_booking_nested_inside_an_existing_one_is_rejected(): void
    {
        // 11th-14th sits entirely within 10th-15th.
        $this->existingBooking();

        $this->assertFalse($this->isAvailable('2026-10-11', '2026-10-14'));
    }

    public function test_an_existing_booking_nested_inside_a_new_one_is_rejected(): void
    {
        // 5th-20th completely swallows 10th-15th. Naive "is the start inside an
        // existing range" checks miss this case entirely.
        $this->existingBooking();

        $this->assertFalse($this->isAvailable('2026-10-05', '2026-10-20'));
    }

    public function test_a_stay_starting_on_the_existing_checkout_day_is_allowed(): void
    {
        // One guest checks out on the 15th, the next checks in on the 15th.
        $this->existingBooking();

        $this->assertTrue($this->isAvailable('2026-10-15', '2026-10-18'));
    }

    public function test_a_stay_ending_on_the_existing_checkin_day_is_allowed(): void
    {
        $this->existingBooking();

        $this->assertTrue($this->isAvailable('2026-10-05', '2026-10-10'));
    }

    public function test_a_range_entirely_before_or_after_is_allowed(): void
    {
        $this->existingBooking();

        $this->assertTrue($this->isAvailable('2026-10-01', '2026-10-05'));
        $this->assertTrue($this->isAvailable('2026-10-20', '2026-10-25'));
    }

    public function test_a_cancelled_booking_does_not_block_availability(): void
    {
        $this->existingBooking(BookingStatus::Cancelled->value);

        $this->assertTrue($this->isAvailable('2026-10-10', '2026-10-15'));
    }

    public function test_a_declined_booking_does_not_block_availability(): void
    {
        $this->existingBooking(BookingStatus::Declined->value);

        $this->assertTrue($this->isAvailable('2026-10-10', '2026-10-15'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function blockingStatusProvider(): array
    {
        return [
            'waiting' => [BookingStatus::Waiting->value],
            'pending' => [BookingStatus::Pending->value],
            'approved' => [BookingStatus::Approved->value],
            'confirmed' => [BookingStatus::Confirmed->value],
            'completed' => [BookingStatus::Completed->value],
        ];
    }

    /**
     * @dataProvider blockingStatusProvider
     */
    public function test_blocking_statuses_reserve_the_calendar(string $status): void
    {
        $this->existingBooking($status);

        $this->assertFalse($this->isAvailable('2026-10-11', '2026-10-14'));
    }

    public function test_blocked_dates_make_a_range_unavailable(): void
    {
        BlockedDate::factory()
            ->for($this->staycation)
            ->forDates('2026-11-01', '2026-11-05')
            ->create(['reason' => 'Maintenance']);

        $this->assertFalse($this->isAvailable('2026-11-02', '2026-11-04'));
        $this->assertFalse($this->isAvailable('2026-10-30', '2026-11-02'));
    }

    public function test_blocked_dates_use_the_same_half_open_boundaries_as_bookings(): void
    {
        BlockedDate::factory()
            ->for($this->staycation)
            ->forDates('2026-11-01', '2026-11-05')
            ->create();

        $this->assertTrue($this->isAvailable('2026-11-05', '2026-11-08'));
        $this->assertTrue($this->isAvailable('2026-10-28', '2026-11-01'));
    }

    public function test_conflicts_describe_why_a_range_was_refused(): void
    {
        $this->existingBooking();

        BlockedDate::factory()
            ->for($this->staycation)
            ->forDates('2026-10-12', '2026-10-13')
            ->create(['reason' => 'Deep cleaning']);

        $conflicts = $this->availability->conflicts(
            $this->staycation,
            DateRange::fromInput('2026-10-11', '2026-10-14'),
        );

        $this->assertCount(2, $conflicts);
        $this->assertSame(['booking', 'blocked_date'], array_column($conflicts, 'type'));
        $this->assertSame('Deep cleaning', $conflicts[1]['reason']);
    }

    public function test_availability_is_scoped_to_a_single_staycation(): void
    {
        $this->existingBooking();

        $other = Staycation::factory()->create();

        $this->assertTrue(
            $this->availability->isAvailable($other, DateRange::fromInput('2026-10-10', '2026-10-15'))
        );
    }

    public function test_a_booking_can_be_excluded_from_its_own_availability_check(): void
    {
        $booking = $this->existingBooking();

        $this->assertTrue(
            $this->availability->isAvailable(
                $this->staycation,
                DateRange::fromInput('2026-10-10', '2026-10-15'),
                $booking->getKey(),
            )
        );
    }

    public function test_alternatives_exclude_staycations_that_are_taken_or_unavailable(): void
    {
        $this->existingBooking();

        $free = Staycation::factory()->create();
        Staycation::factory()->unavailable()->create();

        $blocked = Staycation::factory()->create();
        BlockedDate::factory()->for($blocked)->forDates('2026-10-09', '2026-10-16')->create();

        $alternatives = $this->availability->alternatives(
            DateRange::fromInput('2026-10-10', '2026-10-15'),
            $this->staycation->getKey(),
        );

        $this->assertSame([$free->getKey()], $alternatives->pluck('id')->all());
    }
}
