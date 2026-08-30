<?php

namespace App\Services;

use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Staycation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The single authority on whether a staycation can be booked for a date range.
 *
 * Date convention
 * ---------------
 * A booking occupies the nights [start_date, end_date - 1]; end_date is the
 * checkout day and stays free, so 10 -> 15 and 15 -> 20 do not conflict.
 *
 * A blocked date range is an inclusive range of blocked days, so a block of
 * 10 -> 10 removes the night of the 10th.
 *
 * Comparisons use only `<` and `>=` against bare date strings. Eloquent always
 * writes a `00:00:00` time component, which MySQL truncates in a DATE column but
 * SQLite keeps verbatim; those two operators give the same answer either way,
 * whereas `>` against a bare date would wrongly match same-day rows on SQLite.
 *
 * Serialization
 * -------------
 * Overlap checks alone cannot stop two simultaneous requests from both finding
 * a free range, because there is no row to lock when the range is empty.
 * `lockStaycation()` locks the staycation's own primary-key row, which every
 * inventory-changing operation takes first, making that row the per-property
 * mutex. See BookingInventoryService.
 */
class BookingAvailabilityService
{
    /**
     * Take the per-property write lock and return the staycation.
     *
     * Must be called inside a transaction. Every operation that changes what is
     * bookable for a staycation takes this lock before reading availability.
     */
    public function lockStaycation(int $staycationId): Staycation
    {
        return Staycation::query()->lockForUpdate()->findOrFail($staycationId);
    }

    public function isOpenForBooking(Staycation $staycation): bool
    {
        return $staycation->house_availability === 'available';
    }

    /**
     * Whether the range is free of conflicting bookings and blocked dates.
     */
    public function isAvailable(
        int $staycationId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $ignoreBookingId = null,
    ): bool {
        return ! $this->hasBookingConflict($staycationId, $startDate, $endDate, $ignoreBookingId)
            && ! $this->hasBlockedDateConflict($staycationId, $startDate, $endDate);
    }

    /**
     * Whether an existing booking that still holds its dates overlaps the range.
     */
    public function hasBookingConflict(
        int $staycationId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $ignoreBookingId = null,
    ): bool {
        return $this->conflictingBookingsQuery($staycationId, $startDate, $endDate, $ignoreBookingId)->exists();
    }

    /**
     * Whether an administrator has blocked any night within the range.
     */
    public function hasBlockedDateConflict(
        int $staycationId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $ignoreBlockedDateId = null,
    ): bool {
        return $this->conflictingBlockedDatesQuery($staycationId, $startDate, $endDate, $ignoreBlockedDateId)->exists();
    }

    /**
     * @return Builder<Booking>
     */
    public function conflictingBookingsQuery(
        int $staycationId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $ignoreBookingId = null,
    ): Builder {
        return Booking::query()
            ->where('staycation_id', $staycationId)
            ->holdingDates()
            ->when($ignoreBookingId, fn (Builder $query) => $query->whereKeyNot($ignoreBookingId))
            ->where('start_date', '<', $endDate->toDateString())
            // end_date > startDate, written so the checkout day stays bookable.
            ->where('end_date', '>=', $startDate->addDay()->toDateString());
    }

    /**
     * @return Builder<BlockedDate>
     */
    public function conflictingBlockedDatesQuery(
        int $staycationId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $ignoreBlockedDateId = null,
    ): Builder {
        return BlockedDate::query()
            ->where('staycation_id', $staycationId)
            ->when($ignoreBlockedDateId, fn (Builder $query) => $query->whereKeyNot($ignoreBlockedDateId))
            ->where('start_date', '<', $endDate->toDateString())
            ->where('end_date', '>=', $startDate->toDateString());
    }

    /**
     * Bookings that hold any night inside an inclusive blocked-date range.
     *
     * @return Builder<Booking>
     */
    public function bookingsWithinBlockedRange(
        int $staycationId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): Builder {
        return Booking::query()
            ->where('staycation_id', $staycationId)
            ->holdingDates()
            ->where('start_date', '<', $endDate->addDay()->toDateString())
            ->where('end_date', '>=', $startDate->addDay()->toDateString());
    }

    /**
     * Staycations other than the given one that are free for the range.
     *
     * @return Collection<int, Staycation>
     */
    public function alternativeStaycations(
        int $excludingStaycationId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): Collection {
        return Staycation::query()
            ->whereKeyNot($excludingStaycationId)
            ->where('house_availability', 'available')
            ->whereDoesntHave('bookings', function (Builder $query) use ($startDate, $endDate): void {
                $query->holdingDates()
                    ->where('start_date', '<', $endDate->toDateString())
                    ->where('end_date', '>=', $startDate->addDay()->toDateString());
            })
            ->whereDoesntHave('blockedDates', function (Builder $query) use ($startDate, $endDate): void {
                $query->where('start_date', '<', $endDate->toDateString())
                    ->where('end_date', '>=', $startDate->toDateString());
            })
            ->get();
    }

    /**
     * FullCalendar background events for a staycation's occupied nights.
     *
     * FullCalendar treats `end` as exclusive, which matches a booking's
     * checkout day but not an inclusive blocked-date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public function calendarEvents(int $staycationId): array
    {
        $bookingEvents = Booking::query()
            ->where('staycation_id', $staycationId)
            ->holdingDates()
            ->get()
            ->map(fn (Booking $booking): array => [
                'title' => 'Booked',
                'start' => CarbonImmutable::parse($booking->start_date)->toDateString(),
                'end' => CarbonImmutable::parse($booking->end_date)->toDateString(),
                'color' => '#f87171',
                'display' => 'background',
                'allDay' => true,
                'className' => 'booked-date',
            ]);

        $blockedEvents = BlockedDate::query()
            ->where('staycation_id', $staycationId)
            ->get()
            ->map(fn (BlockedDate $blocked): array => [
                'title' => $blocked->reason ?: 'Blocked',
                'start' => CarbonImmutable::parse($blocked->start_date)->toDateString(),
                'end' => CarbonImmutable::parse($blocked->end_date)->addDay()->toDateString(),
                'color' => '#6b7280',
                'display' => 'background',
                'allDay' => true,
                'className' => 'blocked-date',
            ]);

        return $bookingEvents->toBase()->merge($blockedEvents->toBase())->values()->all();
    }
}
