<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $premiumUsers = User::where('tier', 'premium')->get();

        foreach ($premiumUsers as $user) {
            $count = fake()->numberBetween(20, 30);

            Expense::factory()->count($count)->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
