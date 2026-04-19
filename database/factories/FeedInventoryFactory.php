<?php

namespace Database\Factories;

use App\Enums\FeedType;
use App\Models\FeedInventory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedInventory>
 */
class FeedInventoryFactory extends Factory
{
    protected $model = FeedInventory::class;

    /** @var list<string> */
    private static array $feedBrands = [
        'Layer Pellets',
        'Scratch Grains',
        'Oyster Shell',
        'Starter Crumble',
        'Grower Mash',
        'Mealworm Treats',
        'Grit Mix',
        'Sunflower Seeds',
    ];

    public function definition(): array
    {
        $openedDate = fake()->optional(0.8)->dateTimeBetween('-60 days', 'now');

        return [
            'user_id' => User::factory(),
            'brand' => fake()->randomElement(self::$feedBrands),
            'feed_type' => fake()->randomElement(FeedType::cases()),
            'quantity' => fake()->randomFloat(2, 5.00, 50.00),
            'unit' => fake()->randomElement(['kg', 'lbs']),
            'opened_date' => $openedDate?->format('Y-m-d'),
            'depleted_date' => null,
            'batch_number' => fake()->optional(0.3)->bothify('??-####'),
            'total_cost' => fake()->randomFloat(2, 10.00, 100.00),
            'expense_id' => null,
        ];
    }

    public function depleted(): static
    {
        return $this->state(function (array $attributes) {
            $openedDate = $attributes['opened_date']
                ? \Carbon\Carbon::parse($attributes['opened_date'])
                : fake()->dateTimeBetween('-60 days', '-30 days');

            $openedCarbon = $openedDate instanceof \Carbon\Carbon ? $openedDate : \Carbon\Carbon::parse($openedDate->format('Y-m-d'));

            return [
                'opened_date' => $openedCarbon->format('Y-m-d'),
                'depleted_date' => $openedCarbon->copy()->addDays(fake()->numberBetween(7, 30))->format('Y-m-d'),
            ];
        });
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'depleted_date' => null,
        ]);
    }
}
