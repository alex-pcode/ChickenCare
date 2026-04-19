<?php

namespace Database\Factories;

use App\Models\EggEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EggEntry>
 */
class EggEntryFactory extends Factory
{
    protected $model = EggEntry::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
            'count' => fake()->numberBetween(0, 12),
            'size' => fake()->optional(0.8)->randomElement(['small', 'medium', 'large', 'extra-large', 'jumbo']),
            'color' => fake()->optional(0.8)->randomElement(['white', 'brown', 'blue', 'green', 'speckled', 'cream']),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
