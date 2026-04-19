<?php

namespace App\Policies;

use App\Models\FlockEvent;
use App\Models\FlockProfile;
use App\Models\User;

class FlockEventPolicy
{
    public function create(User $user, FlockProfile $flockProfile): bool
    {
        return $user->id === $flockProfile->user_id;
    }

    public function update(User $user, FlockEvent $flockEvent): bool
    {
        return $user->id === $flockEvent->flockProfile->user_id;
    }

    public function delete(User $user, FlockEvent $flockEvent): bool
    {
        return $user->id === $flockEvent->flockProfile->user_id;
    }
}
