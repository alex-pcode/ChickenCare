<?php

namespace App\Policies;

use App\Models\FlockBatch;
use App\Models\User;

class FlockBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPremium();
    }

    public function view(User $user, FlockBatch $flockBatch): bool
    {
        return $user->id === $flockBatch->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isPremium();
    }

    public function update(User $user, FlockBatch $flockBatch): bool
    {
        return $user->id === $flockBatch->user_id;
    }

    public function delete(User $user, FlockBatch $flockBatch): bool
    {
        return $user->id === $flockBatch->user_id;
    }
}
