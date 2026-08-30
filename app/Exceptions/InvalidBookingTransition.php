<?php

namespace App\Exceptions;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;

/**
 * Raised when a booking or payment status change is not a legal move.
 *
 * A BookingRuleViolation, so the controllers that already turn business-rule
 * refusals into a flash message handle an illegal transition the same way
 * rather than letting it surface as a 500.
 */
class InvalidBookingTransition extends BookingRuleViolation
{
    public static function forPayment(?PaymentStatus $from, PaymentStatus $to): self
    {
        $current = $from?->value ?? 'unknown';

        return new self(
            "A booking with payment status \"{$current}\" cannot be changed to \"{$to->value}\"."
        );
    }

    /**
     * A booking whose stored status resolves to nothing we recognise.
     *
     * Failing closed rather than treating it as a blank slate: a normal admin
     * transition must not be the thing that decides what a corrupt or
     * pre-existing legacy value meant.
     */
    public static function forUnknownBookingStatus(?string $rawStatus): self
    {
        $shown = $rawStatus === null ? 'null' : "\"{$rawStatus}\"";

        return new self(
            "This booking has an unrecognised status ({$shown}) and cannot be changed until "
            .'the record has been audited and corrected.'
        );
    }

    /**
     * A booking whose lifecycle has finished and must not be reopened.
     */
    public static function forTerminalBooking(BookingStatus $status, string $attemptedAction): self
    {
        return new self(
            "This booking is {$status->value} and cannot be {$attemptedAction}."
        );
    }

    public static function forBooking(?BookingStatus $from, BookingStatus $to): self
    {
        $current = $from?->value ?? 'unknown';

        return new self(
            "A booking with status \"{$current}\" cannot be changed to \"{$to->value}\"."
        );
    }
}
