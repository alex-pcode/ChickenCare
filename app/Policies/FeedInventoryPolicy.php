<?php

namespace App\Policies;

use App\Models\FeedInventory;
use App\Models\User;

class FeedInventoryPolicy
{
    public function view(User $user, FeedInventory $feedInventory): bool
    {
        return $user->id === $feedInventory->user_id;
    }

    public function update(User $user, FeedInventory $feedInventory): bool
    {
        return $user->id === $feedInventory->user_id;
    }

    public function delete(User $user, FeedInventory $feedInventory): bool
    {
        return $user->id === $feedInventory->user_id;
    }
}
