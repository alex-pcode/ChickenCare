<?php

namespace Database\Factories;

use App\Enums\BatchAgeAtAcquisition;
use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlockBatch>
 */
class FlockBatchFactory extends Factory
{
    protected $model = FlockBatch::class;

    public function definition(): array
    {
        $initialCount = fake()->numberBetween(5, 30);
        $currentCount = fake()->numberBetween(3, $initialCount);
        $hens = fake()->numberBetween(0, $currentCount);
        $roosters = fake()->numberBetween(0, max(0, $currentCount - $hens));
        $chicks = fake()->numberBetween(0, max(0, $currentCount - $hens - $roosters));
        $brooding = fake()->numberBetween(0, min(3, $hens));

        return [
            'user_id' => User::factory(),
            'batch_name' => fake()->randomElement([
                'Spring 2026 Layers', 'Fall 2025 Hatch', 'Summer Chicks',
                'Heritage Flock', 'Market Birds', 'Backyard Layers',
                'Egg Squad Alpha', 'Winter Hatch 2026',
            ]),
            'breed' => fake()->randomElement([
                'Rhode Island Red', 'Leghorn', 'Plymouth Rock', 'Orpington',
                'Sussex', 'Wyandotte', 'Australorp', 'Silkie', 'Cornish Cross',
            ]),
            'acquisition_date' => fake()->dateTimeBetween('-1 year', '-1 week')->format('Y-m-d'),
            'initial_count' => $initialCount,
            'current_count' => $currentCount,
            'hens_count' => $hens,
            'roosters_count' => $roosters,
            'chicks_count' => $chicks,
            'brooding_count' => $brooding,
            'type' => fake()->randomElement(['hens', 'roosters', 'chicks', 'mixed']),
            'age_at_acquisition' => fake()->randomElement(BatchAgeAtAcquisition::cases()),
            'expected_laying_start_date' => fake()->optional(0.5)->dateTimeBetween('-6 months', '+6 months')?->format('Y-m-d'),
            'actual_laying_start_date' => fake()->optional(0.3)->dateTimeBetween('-6 months', 'now')?->format('Y-m-d'),
            'source' => fake()->randomElement([
                'Local Breeder', 'Hatchery', 'Farm Supply Store',
                'Neighbor', 'Online Order', 'Swap Meet',
            ]),
            'cost' => fake()->randomFloat(2, 10, 500),
            'notes' => fake()->optional(0.4)->sentence(),
            'is_active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function hens(): static
    {
        return $this->state(fn () => [
            'type' => 'hens',
            'roosters_count' => 0,
            'chicks_count' => 0,
        ]);
    }

    public function mixed(): static
    {
        return $this->state(fn () => ['type' => 'mixed']);
    }
}
