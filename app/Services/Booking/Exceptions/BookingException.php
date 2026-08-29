<?php

namespace App\Services\Booking\Exceptions;

use Exception;

/**
 * Base class for booking rule violations. Every subclass carries an HTTP status
 * and a stable machine-readable code so the API and the Blade controllers can
 * present the same failure consistently.
 */
abstract class BookingException extends Exception
{
    public function status(): int
    {
        return 422;
    }

    abstract public function errorCode(): string;

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [];
    }
}
