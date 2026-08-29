<?php

namespace App\Enums;

enum PaymentRecordStatus: string
{
    /** Proof uploaded, not yet reviewed by an admin. */
    case Pending = 'pending';

    /** An admin confirmed the money arrived. Only verified rows count toward the total. */
    case Verified = 'verified';

    /** An admin rejected the proof. */
    case Rejected = 'rejected';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
