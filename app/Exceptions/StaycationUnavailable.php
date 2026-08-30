<?php

namespace App\Exceptions;

/**
 * Raised when a date range cannot be taken for a staycation.
 *
 * The message is written for the customer or administrator who triggered it and
 * is safe to surface directly in a flash message.
 */
class StaycationUnavailable extends BookingRuleViolation
{
    public static function notOpenForBooking(string $houseName): self
    {
        return new self("{$houseName} is not currently open for booking.");
    }

    public static function datesTaken(string $houseName): self
    {
        return new self("The selected dates are no longer available for {$houseName}.");
    }

    public static function datesBlocked(string $houseName): self
    {
        return new self("The selected dates are blocked for {$houseName}.");
    }

    public static function blockedRangeHasBookings(string $houseName): self
    {
        return new self(
            "Those dates cannot be blocked: {$houseName} already has an active booking within that range."
        );
    }
}
