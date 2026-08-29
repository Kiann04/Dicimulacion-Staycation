<?php

namespace App\Enums;

enum PaymentType: string
{
    case Deposit = 'deposit';
    case Balance = 'balance';
    case Full = 'full';
    case Refund = 'refund';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
