<?php

namespace App\Policies;

use App\Models\BatchEvent;
use App\Models\FlockBatch;
use App\Models\User;

class BatchEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPremium();
    }

    public function view(User $user, BatchEvent $batchEvent): bool
    {
        return $user->id === $batchEvent->user_id;
    }

    public function create(User $user, FlockBatch $flockBatch): bool
    {
        return $user->id === $flockBatch->user_id;
    }

    public function update(User $user, BatchEvent $batchEvent): bool
    {
        return $user->id === $batchEvent->user_id
            && $user->id === $batchEvent->flockBatch->user_id;
    }

    public function delete(User $user, BatchEvent $batchEvent): bool
    {
        return $user->id === $batchEvent->user_id
            && $user->id === $batchEvent->flockBatch->user_id;
    }
}
