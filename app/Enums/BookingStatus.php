<?php

namespace App\Enums;

enum BookingStatus: string
{
    /** Legacy default written by the original create_bookings_table migration. */
    case Waiting = 'waiting';

    /** Submitted by a customer, awaiting admin review. */
    case Pending = 'pending';

    /** Admin approved the request; awaiting payment verification. */
    case Approved = 'approved';

    /** Payment verified. The stay is locked in. */
    case Confirmed = 'confirmed';

    /** The stay has already taken place. */
    case Completed = 'completed';

    /** Admin rejected the request. */
    case Declined = 'declined';

    /** Withdrawn by the customer or voided by an admin. */
    case Cancelled = 'cancelled';

    /**
     * Statuses that reserve the calendar and therefore block overlapping bookings.
     *
     * @return array<int, string>
     */
    public static function blockingValues(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            [self::Waiting, self::Pending, self::Approved, self::Confirmed, self::Completed],
        );
    }

    /**
     * Statuses that release the calendar. Cancelled and declined bookings are kept
     * for history but must never make a date range look unavailable.
     *
     * @return array<int, string>
     */
    public static function releasingValues(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            [self::Declined, self::Cancelled],
        );
    }

    public function blocksAvailability(): bool
    {
        return in_array($this->value, self::blockingValues(), true);
    }

    /** Statuses a customer is still allowed to cancel themselves. */
    public function isCancellableByCustomer(): bool
    {
        return in_array($this, [self::Waiting, self::Pending, self::Approved], true);
    }
}
