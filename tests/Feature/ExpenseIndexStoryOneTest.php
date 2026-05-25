<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseIndexStoryOneTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->premium()->create();
    }

    public function test_expenses_index_renders_hero_image_and_badge(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('app.expenses.index'));

        $response->assertStatus(200);
        $response->assertSee('/images/chicken-coin.webp');
        $response->assertSee('💰 Expense Tracker');
        $response->assertSee('expenses-hero__status', false);
    }

    public function test_form_card_renders_with_eight_titlecase_categories(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('app.expenses.index'));

        $response->assertStatus(200);

        $categories = ['Birds', 'Feed', 'Equipment', 'Veterinary', 'Maintenance', 'Supplies', 'Start-up', 'Other'];
        foreach ($categories as $category) {
            $response->assertSee($category);
        }
    }

    public function test_form_card_has_lg_mx_20_width_constraint(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('app.expenses.index'));

        $response->assertStatus(200);
        $response->assertSee('expenses__form-container', false);
        $response->assertSee('form-card', false);
    }

    public function test_htmx_validation_failure_returns_json_errors(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('HX-Request', 'true')
            ->post(route('app.expenses.store'), [
                'date' => now()->format('Y-m-d'),
                'category' => 'Feed',
                'description' => 'Test expense',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('amount');
    }

    public function test_htmx_successful_store_returns_entry_row_partial(): void
    {
        $data = [
            'date' => now()->format('Y-m-d'),
            'category' => 'Feed',
            'description' => 'Chicken feed purchase',
            'amount' => 45.50,
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('HX-Request', 'true')
            ->post(route('app.expenses.store'), $data);

        $response->assertStatus(200);
        $response->assertHeader('HX-Trigger', 'expenses:changed');
        $response->assertSee($data['description']);
        $response->assertSee('$45.50');
    }

    public function test_expense_category_enum_values_are_stored_correctly(): void
    {
        $data = [
            'date' => now()->format('Y-m-d'),
            'category' => 'Veterinary',
            'description' => 'Vet checkup',
            'amount' => 75.00,
        ];

        $this->actingAs($this->user)
            ->withHeader('HX-Request', 'true')
            ->post(route('app.expenses.store'), $data);

        $this->assertDatabaseHas('expenses', [
            'user_id' => $this->user->id,
            'category' => 'Veterinary',
            'description' => 'Vet checkup',
        ]);
    }
}
