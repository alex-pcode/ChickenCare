<?php

namespace Database\Factories;

use App\Models\FlockProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlockProfile>
 */
class FlockProfileFactory extends Factory
{
    protected $model = FlockProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'farm_name' => fake()->randomElement(['My Chicken Farm', 'Sunny Coop', 'Happy Hens', 'Backyard Flock']),
            'location' => fake()->optional(0.5)->city(),
            'flock_size' => fake()->numberBetween(3, 30),
            'breed' => fake()->optional(0.6)->randomElement(['Rhode Island Red', 'Leghorn', 'Plymouth Rock', 'Orpington', 'Sussex']),
            'start_date' => fake()->dateTimeBetween('-2 years', '-1 month')->format('Y-m-d'),
            'hens' => fake()->numberBetween(2, 20),
            'roosters' => fake()->numberBetween(0, 3),
            'chicks' => fake()->numberBetween(0, 10),
            'brooding' => fake()->numberBetween(0, 3),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
