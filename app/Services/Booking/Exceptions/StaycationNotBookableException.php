<?php

namespace App\Services\Booking\Exceptions;

class StaycationNotBookableException extends BookingException
{
    public function errorCode(): string
    {
        return 'staycation_not_bookable';
    }
}
