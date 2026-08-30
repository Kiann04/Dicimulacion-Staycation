<?php

namespace App\Exceptions;

use App\Support\Money;

/**
 * Raised when a change would alter what a booking costs after money has already
 * been verified as received.
 *
 * Rewriting the price of a paid booking would silently create an unrecorded
 * balance or an unrecorded refund, so the change is refused and the decision is
 * handed to a human. A payment-adjustment workflow can be designed later.
 */
class PaymentAdjustmentRequired extends BookingRuleViolation
{
    public static function priceWouldChange(Money $currentTotal, Money $proposedTotal): self
    {
        return new self(
            'This booking has a verified payment. Payment adjustment or refund handling '
            ."is required before changing its price (currently {$currentTotal->toDecimalString()}, "
            ."proposed {$proposedTotal->toDecimalString()})."
        );
    }
}
