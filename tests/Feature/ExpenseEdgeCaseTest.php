<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private function premiumUser(): User
    {
        return User::factory()->create(['tier' => 'premium']);
    }

    private function validExpense(array $overrides = []): array
    {
        return array_merge([
            'date' => now()->format('Y-m-d'),
            'category' => 'Feed',
            'description' => 'Test expense',
            'amount' => '10.00',
        ], $overrides);
    }

    public function test_expense_category_filter_returns_only_matching_category(): void
    {
        $user = $this->premiumUser();
        Expense::factory()->count(3)->create(['user_id' => $user->id, 'category' => 'Feed']);
        Expense::factory()->count(2)->create(['user_id' => $user->id, 'category' => 'Veterinary']);

        $response = $this->actingAs($user)->get('/app/expenses?category=Feed');

        $response->assertStatus(200);
        $expenses = $response->viewData('expenses');
        $this->assertCount(3, $expenses);
        $expenses->each(fn ($e) => $this->assertEquals('Feed', $e->category));
    }

    public function test_expense_store_validates_amount_as_decimal(): void
    {
        // The expense amount rule is `numeric, min:0` — no decimal precision constraint.
        // 10.999 passes and is stored. This test documents the current behavior.
        $user = $this->premiumUser();

        $response = $this->actingAs($user)
            ->post('/app/expenses', $this->validExpense(['amount' => '10.999']));

        // No decimal precision rule — passes through
        $response->assertRedirect(route('app.expenses.index'));
        $this->assertDatabaseHas('expenses', ['user_id' => $user->id, 'description' => 'Test expense']);
    }

    public function test_expense_store_validates_amount_non_negative(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/expenses', $this->validExpense(['amount' => '-1']))
            ->assertSessionHasErrors('amount');
    }

    public function test_expense_store_validates_amount_required(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/expenses', $this->validExpense(['amount' => '']))
            ->assertSessionHasErrors('amount');
    }

    public function test_expense_store_validates_category_from_enum(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/expenses', $this->validExpense(['category' => 'invalid']))
            ->assertSessionHasErrors('category');
    }

    public function test_expense_index_empty_state_for_new_user(): void
    {
        $user = $this->premiumUser();

        $response = $this->actingAs($user)->get('/app/expenses');

        $response->assertStatus(200);
        $response->assertViewIs('expenses.index');
        $expenses = $response->viewData('expenses');
        $this->assertTrue($expenses->isEmpty());
    }

    public function test_expense_htmx_pagination_returns_table_partial(): void
    {
        $user = $this->premiumUser();
        Expense::factory()->count(20)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/expenses?page=2');

        $response->assertStatus(200);
        $response->assertViewIs('expenses.partials.records-table');
    }

    public function test_expense_scoped_to_user_does_not_leak_across_users(): void
    {
        $userA = $this->premiumUser();
        $userB = $this->premiumUser();
        Expense::factory()->count(3)->create(['user_id' => $userA->id]);

        $response = $this->actingAs($userB)->get('/app/expenses');

        $expenses = $response->viewData('expenses');
        $this->assertTrue($expenses->isEmpty());
    }
}
