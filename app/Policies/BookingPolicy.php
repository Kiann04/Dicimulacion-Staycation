<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /** Any signed-in user may list bookings; the query is scoped to their own. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $this->owns($user, $booking) || $user->isStaffOrAdmin();
    }

    public function create(User $user): bool
    {
        return ! $user->isStaffOrAdmin();
    }

    /**
     * A customer may withdraw their own booking while it is still pending or
     * approved. Admins may void a booking in any state.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->owns($user, $booking)
            && ($booking->bookingStatus()?->isCancellableByCustomer() ?? false);
    }

    /** Approve, decline, edit or delete: administrators only. */
    public function manage(User $user, Booking $booking): bool
    {
        return $user->isAdmin();
    }

    /**
     * Payment proofs contain the customer's financial details. Only the customer
     * who uploaded one and the back office may retrieve it.
     */
    public function viewPaymentProof(User $user, Booking $booking): bool
    {
        return $this->owns($user, $booking) || $user->isStaffOrAdmin();
    }

    private function owns(User $user, Booking $booking): bool
    {
        return $booking->user_id !== null && $booking->user_id === $user->getKey();
    }
}
