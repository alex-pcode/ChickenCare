<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /** @var array<string, list<string>> */
    private static array $descriptionsByCategory = [
        'Feed' => ['Layer pellets 50lb bag', 'Scratch grains', 'Oyster shell supplement', 'Mealworm treats', 'Chick starter feed'],
        'Veterinary' => ['Dewormer treatment', 'Veterinary checkup', 'Electrolyte supplements', 'Wound care supplies', 'Vaccination dose'],
        'Equipment' => ['Replacement waterer', 'New feeder trough', 'Heat lamp bulb', 'Egg basket', 'Nesting box pads'],
        'Maintenance' => ['Coop roof repair', 'New bedding shavings', 'Fence wire replacement', 'Door latch hardware', 'Predator-proof mesh'],
        'Other' => ['Electricity for coop lights', 'Water bill portion', 'Heated waterer power', 'Timer for coop door', 'Extension cord'],
        'Supplies' => ['Egg cartons', 'Cleaning supplies', 'Labels for egg sales', 'Record keeping notebook', 'Miscellaneous supplies'],
        'Birds' => ['Chick purchase', 'Adult hen acquisition', 'Breeding stock cost', 'Replacement bird purchase', 'Rooster acquisition'],
        'Start-up' => ['Initial coop construction', 'First flock purchase', 'Starting equipment', 'Permit fees', 'Infrastructure setup'],
    ];

    public function definition(): array
    {
        $category = fake()->randomElement(['Feed', 'Veterinary', 'Equipment', 'Maintenance', 'Other', 'Supplies', 'Birds', 'Start-up']);

        return [
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
            'category' => $category,
            'description' => fake()->randomElement(self::$descriptionsByCategory[$category]),
            'amount' => fake()->randomFloat(2, 5.00, 500.00),
        ];
    }
}
