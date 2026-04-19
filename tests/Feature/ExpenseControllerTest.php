<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseControllerTest extends TestCase
{
    use RefreshDatabase;

    // === Task 20: CRUD Operations ===

    public function test_premium_user_can_view_expenses_index(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/expenses');

        $response->assertStatus(200);
        $response->assertViewIs('expenses.index');
    }

    public function test_premium_user_can_store_expense(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/expenses', [
            'date' => '2026-04-10',
            'category' => 'Feed',
            'description' => 'Layer pellets 50lb bag',
            'amount' => 29.99,
        ]);

        $response->assertRedirect(route('app.expenses.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'category' => 'Feed',
            'amount' => 29.99,
        ]);
    }

    public function test_premium_user_can_store_expense_via_htmx(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post('/app/expenses', [
                'date' => '2026-04-10',
                'category' => 'Veterinary',
                'description' => 'Vet checkup',
                'amount' => 75.00,
            ]);

        $response->assertStatus(200);
        $response->assertSee('expense-');
        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'category' => 'Veterinary',
        ]);
    }

    public function test_premium_user_can_update_expense(): void
    {
        $user = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/app/expenses/{$expense->id}", [
            'date' => '2026-04-10',
            'category' => 'Equipment',
            'description' => 'Updated description',
            'amount' => 50.00,
        ]);

        $response->assertRedirect(route('app.expenses.index'));
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'description' => 'Updated description',
        ]);
    }

    public function test_premium_user_can_update_expense_via_htmx(): void
    {
        $user = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->put("/app/expenses/{$expense->id}", [
                'date' => '2026-04-10',
                'category' => 'Maintenance',
                'description' => 'Coop repair',
                'amount' => 100.00,
            ]);

        $response->assertStatus(200);
        $response->assertSee('expense-');
    }

    public function test_premium_user_can_delete_expense(): void
    {
        $user = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/app/expenses/{$expense->id}");

        $response->assertRedirect(route('app.expenses.index'));
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_premium_user_can_delete_expense_via_htmx(): void
    {
        $user = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete("/app/expenses/{$expense->id}");

        $response->assertStatus(200);
        $this->assertEmpty($response->getContent());
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_premium_user_sees_only_own_expenses(): void
    {
        $userA = User::factory()->premium()->create();
        $userB = User::factory()->premium()->create();

        Expense::factory()->create(['user_id' => $userA->id, 'date' => '2026-04-01', 'description' => 'User A expense']);
        Expense::factory()->create(['user_id' => $userB->id, 'date' => '2026-04-02', 'description' => 'User B expense']);

        $response = $this->actingAs($userA)->get('/app/expenses');

        $response->assertStatus(200);
        $response->assertSee('User A expense');
        $response->assertDontSee('User B expense');
    }

    public function test_premium_user_cannot_update_other_users_expense(): void
    {
        $userA = User::factory()->premium()->create();
        $userB = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)->put("/app/expenses/{$expense->id}", [
            'date' => '2026-04-10',
            'category' => 'Feed',
            'description' => 'Hijack attempt',
            'amount' => 1.00,
        ]);

        $response->assertStatus(403);
    }

    public function test_premium_user_cannot_delete_other_users_expense(): void
    {
        $userA = User::factory()->premium()->create();
        $userB = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)->delete("/app/expenses/{$expense->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    // === Task 21: Validation ===

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/expenses', []);

        $response->assertSessionHasErrors(['date', 'category', 'description', 'amount']);
    }

    public function test_store_validates_date_not_in_future(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/expenses', [
            'date' => now()->addDay()->format('Y-m-d'),
            'category' => 'Feed',
            'description' => 'Future expense',
            'amount' => 10.00,
        ]);

        $response->assertSessionHasErrors(['date']);
    }

    public function test_store_validates_category_enum(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/expenses', [
            'date' => '2026-04-10',
            'category' => 'invalid-category',
            'description' => 'Test',
            'amount' => 10.00,
        ]);

        $response->assertSessionHasErrors(['category']);
    }

    public function test_store_validates_amount_non_negative(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/expenses', [
            'date' => '2026-04-10',
            'category' => 'Feed',
            'description' => 'Negative test',
            'amount' => -5.00,
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    public function test_store_validates_description_max_length(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/expenses', [
            'date' => '2026-04-10',
            'category' => 'Feed',
            'description' => str_repeat('x', 501),
            'amount' => 10.00,
        ]);

        $response->assertSessionHasErrors(['description']);
    }

    public function test_store_accepts_valid_data(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/expenses', [
            'date' => '2026-04-10',
            'category' => 'Other',
            'description' => 'Electricity bill',
            'amount' => 45.50,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'description' => 'Electricity bill',
        ]);
    }

    // === Update path validation ===

    public function test_update_validates_required_fields(): void
    {
        $user = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/app/expenses/{$expense->id}", []);

        $response->assertSessionHasErrors(['date', 'category', 'description', 'amount']);
    }

    public function test_update_validates_date_not_in_future(): void
    {
        $user = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/app/expenses/{$expense->id}", [
            'date' => now()->addDay()->format('Y-m-d'),
            'category' => 'Feed',
            'description' => 'Future expense',
            'amount' => 10.00,
        ]);

        $response->assertSessionHasErrors(['date']);
    }

    public function test_update_validates_category_enum(): void
    {
        $user = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/app/expenses/{$expense->id}", [
            'date' => '2026-04-10',
            'category' => 'invalid-category',
            'description' => 'Test',
            'amount' => 10.00,
        ]);

        $response->assertSessionHasErrors(['category']);
    }

    public function test_update_validates_amount_non_negative(): void
    {
        $user = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/app/expenses/{$expense->id}", [
            'date' => '2026-04-10',
            'category' => 'Feed',
            'description' => 'Negative test',
            'amount' => -5.00,
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    public function test_update_validates_description_max_length(): void
    {
        $user = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/app/expenses/{$expense->id}", [
            'date' => '2026-04-10',
            'category' => 'Feed',
            'description' => str_repeat('x', 501),
            'amount' => 10.00,
        ]);

        $response->assertSessionHasErrors(['description']);
    }

    // === Task 22: Category Filtering & Pagination ===

    public function test_index_filters_by_category(): void
    {
        $user = User::factory()->premium()->create();

        Expense::factory()->create(['user_id' => $user->id, 'category' => 'Feed', 'description' => 'Feed expense']);
        Expense::factory()->create(['user_id' => $user->id, 'category' => 'Veterinary', 'description' => 'Medical expense']);

        $response = $this->actingAs($user)->get('/app/expenses?category=Feed');

        $response->assertStatus(200);
        $response->assertSee('Feed expense');
        $response->assertDontSee('Medical expense');
    }

    public function test_index_paginates_at_5_items(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->count(20)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/app/expenses');

        $response->assertStatus(200);
        $response->assertViewHas('expenses', function ($expenses) {
            return $expenses->perPage() === 5 && $expenses->total() === 20;
        });
    }

    public function test_htmx_pagination_returns_partial(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->count(20)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/expenses?page=2');

        $response->assertStatus(200);
        $response->assertViewIs('expenses.partials.records-table');
    }

    public function test_htmx_category_filter_returns_partial(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create(['user_id' => $user->id, 'category' => 'Feed']);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/expenses?category=Feed');

        $response->assertStatus(200);
        $response->assertViewIs('expenses.partials.records-table');
    }

    public function test_index_shows_empty_state_when_no_expenses(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/expenses');

        $response->assertStatus(200);
        $response->assertSee('No expenses yet');
    }

    // === Task 23: Premium Tier Enforcement ===

    public function test_free_user_cannot_access_expenses(): void
    {
        $user = User::factory()->create(['tier' => 'free']);

        $response = $this->actingAs($user)->get('/app/expenses');

        $response->assertRedirect(route('app.dashboard'));
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/app/expenses');

        $response->assertRedirect(route('login'));
    }

    // === Task 24: Edit Form Partial ===

    public function test_premium_user_can_get_edit_form_via_htmx(): void
    {
        $user = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get("/app/expenses/{$expense->id}/edit-form");

        $response->assertStatus(200);
        $response->assertViewIs('expenses.partials.edit-form');
    }

    public function test_premium_user_cannot_get_edit_form_for_other_users_expense(): void
    {
        $userA = User::factory()->premium()->create();
        $userB = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)->get("/app/expenses/{$expense->id}/edit-form");

        $response->assertStatus(403);
    }

    // === Sorting ===

    public function test_index_sorts_by_amount_ascending(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 100.00, 'description' => 'Expensive']);
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 10.00, 'description' => 'Cheap']);

        $response = $this->actingAs($user)->get('/app/expenses?sort=amount&dir=asc');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Cheap', 'Expensive']);
    }

    public function test_index_sorts_by_amount_descending(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 100.00, 'description' => 'Expensive']);
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 10.00, 'description' => 'Cheap']);

        $response = $this->actingAs($user)->get('/app/expenses?sort=amount&dir=desc');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Expensive', 'Cheap']);
    }

    public function test_index_sorts_by_category(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create(['user_id' => $user->id, 'category' => 'Feed', 'description' => 'Feed item']);
        Expense::factory()->create(['user_id' => $user->id, 'category' => 'Birds', 'description' => 'Bird item']);

        $response = $this->actingAs($user)->get('/app/expenses?sort=category&dir=asc');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Bird item', 'Feed item']);
    }

    public function test_index_rejects_invalid_sort_column(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/app/expenses?sort=invalid_column&dir=asc');

        $response->assertStatus(200);
    }

    public function test_htmx_sort_returns_records_table_partial(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/expenses?sort=amount&dir=desc');

        $response->assertStatus(200);
        $response->assertViewIs('expenses.partials.records-table');
    }

    // === Date format in table ===

    public function test_expense_row_displays_date_in_iso_format(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-03-15',
        ]);

        $response = $this->actingAs($user)->get('/app/expenses');

        $response->assertStatus(200);
        $response->assertSee('2026-03-15');
    }

    // === Delete via HTMX triggers expenses:changed ===

    public function test_htmx_delete_triggers_expenses_changed_event(): void
    {
        $user = User::factory()->premium()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete("/app/expenses/{$expense->id}");

        $response->assertStatus(200);
        $response->assertHeader('HX-Trigger', 'expenses:changed');
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }
}
