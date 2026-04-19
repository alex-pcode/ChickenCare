<?php

namespace Database\Factories;

use App\Enums\BatchEventType;
use App\Models\BatchEvent;
use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BatchEvent>
 */
class BatchEventFactory extends Factory
{
    protected $model = BatchEvent::class;

    public function definition(): array
    {
        $type = fake()->randomElement(BatchEventType::cases());

        $descriptions = [
            'health_check' => ['Routine health inspection', 'Vet checkup — all birds healthy', 'Noticed mild respiratory symptoms'],
            'vaccination' => ['Annual Newcastle vaccination', 'Marek\'s disease booster', 'Fowl pox vaccination'],
            'relocation' => ['Moved to outdoor run', 'Transferred to new coop', 'Relocated to brooder'],
            'breeding' => ['Introduced rooster to flock', 'Collected fertile eggs', 'Started breeding program'],
            'laying_start' => ['First eggs observed', 'Pullets started laying', 'Laying resumed after molt'],
            'brooding_start' => ['Hen went broody', 'Set broody hen on eggs', 'Natural incubation started'],
            'brooding_stop' => ['Brooding period ended', 'Chicks hatched successfully', 'Hen broke from broodiness'],
            'production_note' => ['Egg production up this week', 'Feed consumption increased', 'Molting observed'],
            'flock_added' => ['Added new birds to batch', 'Integrated chicks from brooder', 'Received new shipment'],
            'flock_loss' => ['Lost birds to predator', 'Birds culled due to illness', 'Bird escaped from run'],
            'other' => ['General flock observation', 'Coop maintenance performed', 'Changed feed brand'],
        ];

        return [
            'user_id' => User::factory(),
            'batch_id' => FlockBatch::factory(),
            'date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'type' => $type,
            'description' => fake()->randomElement($descriptions[$type->value] ?? $descriptions['other']),
            'affected_count' => fake()->optional(0.5)->numberBetween(1, 10),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function healthCheck(): static
    {
        return $this->state(fn () => [
            'type' => BatchEventType::HealthCheck,
            'description' => 'Routine health inspection',
        ]);
    }

    public function vaccination(): static
    {
        return $this->state(fn () => [
            'type' => BatchEventType::Vaccination,
            'description' => 'Annual Newcastle vaccination',
        ]);
    }

    public function layingStart(): static
    {
        return $this->state(fn () => [
            'type' => BatchEventType::LayingStart,
            'description' => 'First eggs observed',
        ]);
    }

    public function broodingStart(): static
    {
        return $this->state(fn () => [
            'type' => BatchEventType::BroodingStart,
            'description' => 'Hen went broody',
        ]);
    }

    public function broodingStop(): static
    {
        return $this->state(fn () => [
            'type' => BatchEventType::BroodingStop,
            'description' => 'Brooding period ended',
        ]);
    }

    public function relocation(): static
    {
        return $this->state(fn () => [
            'type' => BatchEventType::Relocation,
            'description' => 'Moved to outdoor run',
        ]);
    }

    public function breeding(): static
    {
        return $this->state(fn () => [
            'type' => BatchEventType::Breeding,
            'description' => 'Introduced rooster to flock',
        ]);
    }

    public function flockLoss(): static
    {
        return $this->state(fn () => [
            'type' => BatchEventType::FlockLoss,
            'description' => 'Lost birds to predator',
        ]);
    }
}
