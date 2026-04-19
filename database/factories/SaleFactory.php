<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_id' => null,
            'sale_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'dozen_count' => fake()->numberBetween(1, 10),
            'individual_count' => fake()->numberBetween(0, 11),
            'total_amount' => fake()->randomFloat(2, 1.00, 50.00),
            'paid' => fake()->boolean(70),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function paid(): static
    {
        return $this->state(['paid' => true]);
    }

    public function unpaid(): static
    {
        return $this->state(['paid' => false]);
    }

    public function withCustomer(Customer $customer): static
    {
        return $this->state(['customer_id' => $customer->id]);
    }
}
