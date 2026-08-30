<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Staycation;
use App\Services\BookingAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;

class BookingAvailabilityTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    private BookingAvailabilityService $availability;

    private Staycation $staycation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availability = app(BookingAvailabilityService::class);
        $this->staycation = Staycation::factory()->create();
    }

    private function existingBooking(string $status = 'pending'): Booking
    {
        return Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(9), $this->day(14))
            ->create(['status' => $status]);
    }

    private function isAvailable(string $start, string $end): bool
    {
        return $this->availability->isAvailable(
            $this->staycation->getKey(),
            CarbonImmutable::parse($start),
            CarbonImmutable::parse($end),
        );
    }

    /**
     * Ranges against an existing booking of day 9 -> day 14, as day offsets so
     * the provider stays static.
     *
     * @return array<string, array{0: int, 1: int}>
     */
    public static function overlappingRangesProvider(): array
    {
        return [
            'contained within' => [11, 13],
            'overlaps the start' => [7, 11],
            'overlaps the end' => [13, 17],
            'surrounds entirely' => [7, 19],
            'exactly the same' => [9, 14],
        ];
    }

    /**
     * @dataProvider overlappingRangesProvider
     */
    public function test_it_rejects_ranges_that_overlap_an_existing_booking(int $start, int $end): void
    {
        $this->existingBooking();

        $this->assertFalse($this->isAvailable($this->day($start), $this->day($end)));
    }

    public function test_it_allows_a_stay_starting_on_the_previous_guests_checkout_day(): void
    {
        $this->existingBooking();

        $this->assertTrue($this->isAvailable($this->day(14), $this->day(19)));
    }

    public function test_it_allows_a_stay_ending_on_the_next_guests_arrival_day(): void
    {
        $this->existingBooking();

        $this->assertTrue($this->isAvailable($this->day(4), $this->day(9)));
    }

    public function test_declined_bookings_release_their_dates(): void
    {
        $this->existingBooking(BookingStatus::Declined->value);

        $this->assertTrue($this->isAvailable($this->day(11), $this->day(13)));
    }

    public function test_cancelled_bookings_release_their_dates(): void
    {
        $this->existingBooking(BookingStatus::Cancelled->value);

        $this->assertTrue($this->isAvailable($this->day(11), $this->day(13)));
    }

    public function test_soft_deleted_bookings_release_their_dates(): void
    {
        $this->existingBooking()->delete();

        $this->assertTrue($this->isAvailable($this->day(11), $this->day(13)));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function holdingStatusProvider(): array
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
     * @dataProvider holdingStatusProvider
     */
    public function test_active_statuses_hold_their_dates(string $status): void
    {
        $this->existingBooking($status);

        $this->assertFalse($this->isAvailable($this->day(11), $this->day(13)));
    }

    /**
     * The `status` column is NOT NULL with a default, so a null status is not
     * reachable through the schema; the query still guards against it in case an
     * older dump or a manual edit produced one.
     *
     * @return array<string, array{0: string}>
     */
    public static function unrecognisedStatusProvider(): array
    {
        return [
            'empty string' => [''],
            'legacy value' => ['awaiting_confirmation'],
            'typo' => ['aproved'],
            'unexpected casing of an unknown value' => ['ON HOLD'],
        ];
    }

    /**
     * Availability is decided by excluding the statuses that explicitly release
     * dates, so anything unrecognised keeps holding its inventory rather than
     * silently freeing a room that may well still be occupied.
     *
     * @dataProvider unrecognisedStatusProvider
     */
    public function test_an_unrecognised_status_still_holds_its_dates(string $status): void
    {
        $booking = $this->existingBooking();
        $booking->forceFill(['status' => $status])->save();

        $this->assertFalse($this->isAvailable($this->day(11), $this->day(13)));
    }

    public function test_a_released_status_is_matched_case_insensitively_only_when_it_is_known(): void
    {
        $booking = $this->existingBooking();

        // Exactly the released value frees the dates.
        $booking->forceFill(['status' => 'cancelled'])->save();
        $this->assertTrue($this->isAvailable($this->day(11), $this->day(13)));

        // Something that merely looks like it does not.
        $booking->forceFill(['status' => 'cancelled_by_system'])->save();
        $this->assertFalse($this->isAvailable($this->day(11), $this->day(13)));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function collationHazardProvider(): array
    {
        return [
            'uppercase' => ['DECLINED'],
            'title case' => ['Cancelled'],
            'mixed case' => ['CanCelLed'],
            'trailing space' => ['declined '],
            'leading space' => [' cancelled'],
            'suffixed' => ['cancelledx'],
        ];
    }

    /**
     * utf8mb4_unicode_ci is case-insensitive and PAD SPACE, so on MySQL each of
     * these would compare equal to a canonical released status and quietly free
     * a room that is still occupied. Only the exact lowercase value may release.
     *
     * @dataProvider collationHazardProvider
     */
    public function test_a_status_that_only_resembles_a_released_one_still_holds(string $status): void
    {
        $booking = $this->existingBooking();
        $booking->forceFill(['status' => $status])->save();

        $this->assertFalse($this->isAvailable($this->day(11), $this->day(13)));
    }

    /**
     * SQLite compares TEXT byte-for-byte by default, so this suite cannot
     * reproduce a case-insensitive collation. What it can assert is that the
     * SQL actually sent to MySQL forces a binary comparison.
     */
    public function test_the_mysql_query_compares_the_status_byte_for_byte(): void
    {
        $sql = Booking::on('mysql')->holdingDates()->toSql();

        $this->assertStringContainsString('CAST(`status` AS BINARY) not in', $sql);
    }

    public function test_the_sqlite_query_relies_on_its_own_binary_collation(): void
    {
        $sql = Booking::query()->holdingDates()->toSql();

        $this->assertStringNotContainsString('CAST', $sql);
        $this->assertStringContainsString('"status" not in', $sql);
    }

    public function test_an_unrecognised_status_is_reported_as_holding_its_dates(): void
    {
        $this->assertFalse(BookingStatus::valueReleasesDates(null));
        $this->assertFalse(BookingStatus::valueReleasesDates('mystery'));
        $this->assertTrue(BookingStatus::valueReleasesDates('cancelled'));
        $this->assertTrue(BookingStatus::valueReleasesDates('DECLINED'));
    }

    public function test_an_unrecognised_status_still_appears_on_the_calendar(): void
    {
        $booking = $this->existingBooking();
        $booking->forceFill(['status' => 'legacy_unknown'])->save();

        $events = collect($this->availability->calendarEvents($this->staycation->getKey()));

        $this->assertNotNull($events->firstWhere('title', 'Booked'));
    }

    public function test_a_blocked_range_makes_its_nights_unavailable(): void
    {
        BlockedDate::factory()->for($this->staycation)->create([
            'start_date' => $this->day(40),
            'end_date' => $this->day(42),
        ]);

        $this->assertFalse($this->isAvailable($this->day(41), $this->day(44)));
    }

    public function test_a_single_day_block_removes_that_night(): void
    {
        BlockedDate::factory()->for($this->staycation)->create([
            'start_date' => $this->day(40),
            'end_date' => $this->day(40),
        ]);

        $this->assertFalse($this->isAvailable($this->day(40), $this->day(41)));
    }

    public function test_a_block_ending_the_day_before_arrival_does_not_conflict(): void
    {
        BlockedDate::factory()->for($this->staycation)->create([
            'start_date' => $this->day(38),
            'end_date' => $this->day(39),
        ]);

        $this->assertTrue($this->isAvailable($this->day(40), $this->day(42)));
    }

    public function test_a_block_starting_on_the_checkout_day_does_not_conflict(): void
    {
        BlockedDate::factory()->for($this->staycation)->create([
            'start_date' => $this->day(42),
            'end_date' => $this->day(44),
        ]);

        $this->assertTrue($this->isAvailable($this->day(40), $this->day(42)));
    }

    public function test_bookings_on_other_staycations_do_not_conflict(): void
    {
        Booking::factory()
            ->for(Staycation::factory()->create())
            ->forDates($this->day(9), $this->day(14))
            ->create();

        $this->assertTrue($this->isAvailable($this->day(11), $this->day(13)));
    }

    public function test_an_edited_booking_does_not_conflict_with_itself(): void
    {
        $booking = $this->existingBooking();

        $this->assertTrue($this->availability->isAvailable(
            $this->staycation->getKey(),
            CarbonImmutable::parse($this->day(10)),
            CarbonImmutable::parse($this->day(15)),
            ignoreBookingId: $booking->getKey(),
        ));
    }

    public function test_alternative_staycations_exclude_conflicting_properties(): void
    {
        $free = Staycation::factory()->create();
        $taken = Staycation::factory()->create();
        $blocked = Staycation::factory()->create();
        Staycation::factory()->unavailable()->create();

        Booking::factory()->for($taken)->forDates($this->day(9), $this->day(14))->create();
        BlockedDate::factory()->for($blocked)->create([
            'start_date' => $this->day(10),
            'end_date' => $this->day(12),
        ]);

        $alternatives = $this->availability->alternativeStaycations(
            $this->staycation->getKey(),
            CarbonImmutable::parse($this->day(11)),
            CarbonImmutable::parse($this->day(13)),
        );

        $this->assertEqualsCanonicalizing([$free->getKey()], $alternatives->pluck('id')->all());
    }

    public function test_calendar_events_use_the_checkout_exclusive_convention_for_bookings(): void
    {
        $this->existingBooking();

        $events = collect($this->availability->calendarEvents($this->staycation->getKey()));
        $booked = $events->firstWhere('title', 'Booked');

        $this->assertSame($this->day(9), $booked['start']);
        $this->assertSame($this->day(14), $booked['end']);
    }

    public function test_calendar_events_render_blocked_ranges_inclusively(): void
    {
        BlockedDate::factory()->for($this->staycation)->create([
            'start_date' => $this->day(40),
            'end_date' => $this->day(40),
            'reason' => 'Deep clean',
        ]);

        $events = collect($this->availability->calendarEvents($this->staycation->getKey()));
        $blocked = $events->firstWhere('title', 'Deep clean');

        $this->assertSame($this->day(40), $blocked['start']);
        $this->assertSame($this->day(41), $blocked['end']);
    }
}
