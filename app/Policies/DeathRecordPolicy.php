<?php

namespace App\Policies;

use App\Models\DeathRecord;
use App\Models\FlockBatch;
use App\Models\User;

class DeathRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPremium();
    }

    public function view(User $user, DeathRecord $deathRecord): bool
    {
        return $user->id === $deathRecord->user_id;
    }

    public function create(User $user, FlockBatch $flockBatch): bool
    {
        return $user->id === $flockBatch->user_id;
    }

    public function update(User $user, DeathRecord $deathRecord): bool
    {
        return $user->id === $deathRecord->user_id
            && $user->id === $deathRecord->flockBatch->user_id;
    }

    public function delete(User $user, DeathRecord $deathRecord): bool
    {
        return $user->id === $deathRecord->user_id
            && $user->id === $deathRecord->flockBatch->user_id;
    }
}
