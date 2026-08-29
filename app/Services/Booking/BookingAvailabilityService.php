<?php

namespace App\Services\Booking;

use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Staycation;
use App\Services\Booking\Exceptions\DatesUnavailableException;

/**
 * The single source of truth for "can this staycation be booked for these dates".
 *
 * Overlap is evaluated with half-open intervals — a stay occupies
 * [start_date, end_date). Two ranges collide when
 *
 *     existing.start < new.end  AND  existing.end > new.start
 *
 * which correctly rejects exact collisions, partial overlaps at either end, and
 * nesting in both directions, while allowing back-to-back stays where one guest
 * checks out on the day the next checks in.
 */
class BookingAvailabilityService
{
    /**
     * Every reason the range cannot be booked. An empty array means available.
     *
     * @return array<int, array{type: string, start_date: string, end_date: string, reason: string|null}>
     */
    public function conflicts(Staycation $staycation, DateRange $range, ?int $ignoreBookingId = null): array
    {
        $bookings = Booking::query()
            ->where('staycation_id', $staycation->getKey())
            ->blockingAvailability()
            ->overlapping($range->startString(), $range->endString())
            ->when($ignoreBookingId !== null, fn ($query) => $query->whereKeyNot($ignoreBookingId))
            ->get(['id', 'start_date', 'end_date']);

        $blocked = BlockedDate::query()
            ->where('staycation_id', $staycation->getKey())
            ->overlapping($range->startString(), $range->endString())
            ->get(['id', 'start_date', 'end_date', 'reason']);

        $conflicts = [];

        foreach ($bookings as $booking) {
            $conflicts[] = [
                'type' => 'booking',
                'start_date' => $booking->start_date->toDateString(),
                'end_date' => $booking->end_date->toDateString(),
                'reason' => null,
            ];
        }

        foreach ($blocked as $block) {
            $conflicts[] = [
                'type' => 'blocked_date',
                'start_date' => $block->start_date->toDateString(),
                'end_date' => $block->end_date->toDateString(),
                'reason' => $block->reason,
            ];
        }

        return $conflicts;
    }

    public function isAvailable(Staycation $staycation, DateRange $range, ?int $ignoreBookingId = null): bool
    {
        return $this->conflicts($staycation, $range, $ignoreBookingId) === [];
    }

    /**
     * @throws DatesUnavailableException
     */
    public function assertAvailable(Staycation $staycation, DateRange $range, ?int $ignoreBookingId = null): void
    {
        $conflicts = $this->conflicts($staycation, $range, $ignoreBookingId);

        if ($conflicts !== []) {
            throw new DatesUnavailableException(
                "The selected dates are not available for {$staycation->house_name}.",
                $conflicts,
            );
        }
    }

    /**
     * Staycations that can host the given range, excluding one id. Used to offer
     * alternatives when the customer's first choice is taken.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Staycation>
     */
    public function alternatives(DateRange $range, ?int $excludeStaycationId = null)
    {
        return Staycation::query()
            ->available()
            ->when($excludeStaycationId !== null, fn ($query) => $query->whereKeyNot($excludeStaycationId))
            ->whereDoesntHave('bookings', function ($query) use ($range) {
                $query->blockingAvailability()
                    ->overlapping($range->startString(), $range->endString());
            })
            ->whereDoesntHave('blockedDates', function ($query) use ($range) {
                $query->overlapping($range->startString(), $range->endString());
            })
            ->get();
    }

    /**
     * Occupied ranges for a staycation, for rendering a calendar. Returns the
     * blocking bookings and blocked dates that intersect the given window.
     *
     * @return array<int, array{type: string, start_date: string, end_date: string, reason: string|null}>
     */
    public function occupiedRanges(Staycation $staycation, DateRange $window): array
    {
        return $this->conflicts($staycation, $window);
    }
}
