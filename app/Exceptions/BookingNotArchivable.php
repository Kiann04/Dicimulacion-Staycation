<?php

namespace App\Exceptions;

use App\Enums\PaymentStatus;

/**
 * Raised when a booking may not be archived and hard-deleted.
 */
class BookingNotArchivable extends BookingRuleViolation
{
    public static function hasVerifiedPayment(PaymentStatus $status): self
    {
        $shown = str_replace('_', ' ', $status->value);

        return new self(
            "This booking has a verified payment ({$shown}) and cannot be deleted. "
            .'Refund handling is required before its record can be removed.'
        );
    }
}
