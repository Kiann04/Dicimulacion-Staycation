<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaffOrAdmin();
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->isStaffOrAdmin()) {
            return true;
        }

        return $payment->booking?->user_id === $user->getKey();
    }

    /** Confirming money arrived is an administrator decision. */
    public function verify(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }

    public function reject(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }
}
