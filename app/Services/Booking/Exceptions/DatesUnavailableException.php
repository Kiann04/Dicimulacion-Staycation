<?php

namespace App\Services\Booking\Exceptions;

class DatesUnavailableException extends BookingException
{
    /**
     * @param  array<int, array{type: string, start_date: string, end_date: string, reason?: string|null}>  $conflicts
     */
    public function __construct(
        string $message,
        private readonly array $conflicts = [],
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'dates_unavailable';
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return ['conflicts' => $this->conflicts];
    }
}
