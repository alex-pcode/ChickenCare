<?php

namespace App\Policies;

use App\Models\FlockProfile;
use App\Models\User;

class FlockProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPremium();
    }

    public function view(User $user, FlockProfile $flockProfile): bool
    {
        return $user->id === $flockProfile->user_id;
    }

    public function create(User $user): bool
    {
        return ! $user->flockProfile;
    }

    public function update(User $user, FlockProfile $flockProfile): bool
    {
        return $user->id === $flockProfile->user_id;
    }

    public function delete(User $user, FlockProfile $flockProfile): bool
    {
        return false;
    }
}
