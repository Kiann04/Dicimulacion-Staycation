<?php

namespace App\Services\Payment\Exceptions;

use App\Services\Booking\Exceptions\BookingException;

class InvalidPaymentTransitionException extends BookingException
{
    public function errorCode(): string
    {
        return 'invalid_payment_transition';
    }
}
