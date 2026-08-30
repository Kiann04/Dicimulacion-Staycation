<?php

namespace App\Policies;

use App\Models\BookingHistory;
use App\Models\User;

/**
 * Archived bookings carry the same payment proof the live booking did, so they
 * carry the same access rule: the administrator, and the customer whose booking
 * it was. Archiving a booking must not quietly widen or narrow who may read the
 * customer's financial documents.
 */
class BookingHistoryPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewPaymentProof(User $user, BookingHistory $bookingHistory): bool
    {
        return $bookingHistory->user_id !== null
            && $user->getKey() === $bookingHistory->user_id;
    }
}
