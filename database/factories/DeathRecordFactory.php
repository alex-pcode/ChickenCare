<?php

namespace Database\Factories;

use App\Enums\DeathCause;
use App\Models\DeathRecord;
use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeathRecord>
 */
class DeathRecordFactory extends Factory
{
    protected $model = DeathRecord::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'batch_id' => FlockBatch::factory(),
            'date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'count' => fake()->numberBetween(1, 3),
            'cause' => fake()->randomElement(DeathCause::cases()),
            'description' => fake()->randomElement([
                'Found dead in coop', 'Predator attack overnight', 'Died of natural causes',
                'Culled due to illness', 'Injury from rooster', 'Unknown cause of death',
                'Disease symptoms observed', 'Fox got into run',
            ]),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function predator(): static
    {
        return $this->state(fn () => [
            'cause' => DeathCause::Predator,
            'description' => 'Predator attack overnight',
        ]);
    }

    public function disease(): static
    {
        return $this->state(fn () => [
            'cause' => DeathCause::Disease,
            'description' => 'Disease symptoms observed before death',
        ]);
    }

    public function age(): static
    {
        return $this->state(fn () => [
            'cause' => DeathCause::Age,
            'description' => 'Died of natural causes',
        ]);
    }

    public function injury(): static
    {
        return $this->state(fn () => [
            'cause' => DeathCause::Injury,
            'description' => 'Injury from rooster',
        ]);
    }

    public function culled(): static
    {
        return $this->state(fn () => [
            'cause' => DeathCause::Culled,
            'description' => 'Culled due to illness',
        ]);
    }

    public function unknown(): static
    {
        return $this->state(fn () => [
            'cause' => DeathCause::Unknown,
            'description' => 'Unknown cause of death',
        ]);
    }
}
