<?php

namespace App\Enums;

enum PaymentStatus: string
{
    /** No money received yet. */
    case Unpaid = 'unpaid';

    /** Awaiting admin verification of an uploaded proof. */
    case Pending = 'pending';

    /** A deposit has been verified; a balance remains. */
    case HalfPaid = 'half_paid';

    /** The booking total has been settled in full. */
    case Paid = 'paid';

    /** Payment was rejected or the booking was declined. */
    case Failed = 'failed';

    /** Money was returned to the customer. */
    case Refunded = 'refunded';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Statuses an admin may set directly from the booking screens. Terminal
     * bookkeeping states (refunded) are driven by the payment ledger instead.
     *
     * @return array<int, string>
     */
    public static function adminAssignableValues(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            [self::Unpaid, self::Pending, self::HalfPaid, self::Paid, self::Failed],
        );
    }

    public function isSettled(): bool
    {
        return $this === self::Paid;
    }
}
