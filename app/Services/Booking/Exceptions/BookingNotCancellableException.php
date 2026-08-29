<?php

namespace App\Services\Booking\Exceptions;

class BookingNotCancellableException extends BookingException
{
    public function errorCode(): string
    {
        return 'booking_not_cancellable';
    }
}
