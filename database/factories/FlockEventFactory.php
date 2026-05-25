<?php

namespace Database\Factories;

use App\Models\FlockEvent;
use App\Models\FlockProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlockEvent>
 */
class FlockEventFactory extends Factory
{
    protected $model = FlockEvent::class;

    public function definition(): array
    {
        return [
            'flock_profile_id' => FlockProfile::factory(),
            'date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'type' => fake()->randomElement(['acquisition', 'laying_start', 'broody', 'hatching', 'recount', 'other']),
            'description' => fake()->sentence(),
            'affected_birds' => fake()->optional(0.6)->numberBetween(1, 15),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function acquisition(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'acquisition',
            'description' => 'Acquired new birds',
        ]);
    }

    public function layingStart(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'laying_start',
            'description' => 'Hens started laying eggs',
        ]);
    }

    public function broody(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'broody',
            'description' => 'Hen went broody',
        ]);
    }

    public function hatching(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'hatching',
            'description' => 'Eggs hatched successfully',
        ]);
    }

    public function recount(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'recount',
            'description' => 'Periodic head count',
            'affected_birds' => fake()->numberBetween(5, 50),
        ]);
    }

    public function other(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'other',
            'description' => fake()->sentence(),
        ]);
    }
}
