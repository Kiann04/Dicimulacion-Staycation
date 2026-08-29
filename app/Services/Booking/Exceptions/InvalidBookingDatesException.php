<?php

namespace App\Services\Booking\Exceptions;

class InvalidBookingDatesException extends BookingException
{
    public function errorCode(): string
    {
        return 'invalid_dates';
    }
}
