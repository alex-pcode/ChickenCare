<?php

namespace Database\Seeders;

use App\Models\FeedInventory;
use App\Models\User;
use Illuminate\Database\Seeder;

class FeedInventorySeeder extends Seeder
{
    public function run(): void
    {
        $premiumUsers = User::where('tier', 'premium')->get();

        foreach ($premiumUsers as $user) {
            $count = fake()->numberBetween(5, 8);

            FeedInventory::factory()->count($count - 2)->create([
                'user_id' => $user->id,
            ]);

            FeedInventory::factory()->depleted()->create([
                'user_id' => $user->id,
            ]);

            FeedInventory::factory()->active()->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
