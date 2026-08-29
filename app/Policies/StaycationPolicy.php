<?php

namespace App\Policies;

use App\Models\Staycation;
use App\Models\User;

class StaycationPolicy
{
    /** The catalogue is public; these apply to authenticated callers only. */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Staycation $staycation): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Staycation $staycation): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Staycation $staycation): bool
    {
        return $user->isAdmin();
    }

    /** Blocking dates takes a staycation off the calendar: admins only. */
    public function manageAvailability(User $user, Staycation $staycation): bool
    {
        return $user->isAdmin();
    }
}
