<?php

namespace App\Services\Booking\Exceptions;

class GuestCapacityExceededException extends BookingException
{
    public function errorCode(): string
    {
        return 'guest_capacity_exceeded';
    }
}
