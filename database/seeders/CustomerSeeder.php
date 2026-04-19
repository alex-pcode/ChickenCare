<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $premiumUsers = User::where('tier', 'premium')->get();

        foreach ($premiumUsers as $user) {
            $activeCount = fake()->numberBetween(5, 8);
            $inactiveCount = fake()->numberBetween(1, 2);

            Customer::factory()->count($activeCount)->create([
                'user_id' => $user->id,
            ]);

            Customer::factory()->inactive()->count($inactiveCount)->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
