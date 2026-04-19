<?php

namespace App\Policies;

use App\Models\EggEntry;
use App\Models\User;

class EggEntryPolicy
{
    public function view(User $user, EggEntry $entry): bool
    {
        return $user->id === $entry->user_id;
    }

    public function update(User $user, EggEntry $entry): bool
    {
        return $user->id === $entry->user_id;
    }

    public function delete(User $user, EggEntry $entry): bool
    {
        return $user->id === $entry->user_id;
    }
}
