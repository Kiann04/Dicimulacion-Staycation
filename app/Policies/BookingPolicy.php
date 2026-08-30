<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Administrators may act on any booking.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->isStaff() || $this->owns($user, $booking);
    }

    /**
     * A customer may cancel their own booking while it is still pending.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $this->owns($user, $booking)
            && BookingStatus::fromLoose($booking->status) === BookingStatus::Pending;
    }

    /**
     * Payment proofs are sensitive customer financial documents.
     *
     * Only the administrator (via `before()`) and the customer who submitted it
     * may read one. Ordinary staff see bookings but not proofs, until there is
     * an explicit permission model that grants it.
     */
    public function viewPaymentProof(User $user, Booking $booking): bool
    {
        return $this->owns($user, $booking);
    }

    /**
     * Changing payment state is an administrator action only, so this is
     * reached by neither admins (short-circuited above) nor anyone allowed.
     */
    public function updatePayment(User $user, Booking $booking): bool
    {
        return false;
    }

    private function owns(User $user, Booking $booking): bool
    {
        return $booking->user_id !== null && $user->getKey() === $booking->user_id;
    }
}
