<?php

namespace Database\Seeders;

use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Database\Seeder;

class FlockBatchSeeder extends Seeder
{
    public function run(): void
    {
        $premiumUsers = User::where('tier', 'premium')
            ->orWhere('is_admin', true)
            ->get();

        foreach ($premiumUsers as $user) {
            FlockBatch::factory()
                ->count(fake()->numberBetween(3, 5))
                ->for($user)
                ->active()
                ->create();

            FlockBatch::factory()
                ->for($user)
                ->archived()
                ->create();
        }
    }
}
