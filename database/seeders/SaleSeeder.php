<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $premiumUsers = User::where('tier', 'premium')->get();

        foreach ($premiumUsers as $user) {
            $count = fake()->numberBetween(10, 20);

            for ($i = 0; $i < $count; $i++) {
                $customerId = null;

                if (fake()->boolean(30)) {
                    $customer = Customer::where('user_id', $user->id)->inRandomOrder()->first();
                    $customerId = $customer?->id;
                }

                Sale::factory()->create([
                    'user_id' => $user->id,
                    'customer_id' => $customerId,
                    'paid' => fake()->boolean(70),
                ]);
            }
        }
    }
}
