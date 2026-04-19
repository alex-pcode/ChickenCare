<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Database\Seeders\ExpenseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExpenseDataLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_table_exists_with_correct_columns(): void
    {
        $this->assertTrue(Schema::hasTable('expenses'));
        $this->assertTrue(Schema::hasColumns('expenses', [
            'id', 'user_id', 'date', 'category', 'description', 'amount', 'created_at', 'updated_at',
        ]));
    }

    public function test_expense_factory_creates_valid_model(): void
    {
        $expense = Expense::factory()->create();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
        $this->assertNotNull($expense->date);
        $this->assertNotNull($expense->category);
        $this->assertNotNull($expense->description);
        $this->assertNotNull($expense->amount);
    }

    public function test_expense_factory_respects_category_values(): void
    {
        $validCategories = ['Birds', 'Feed', 'Equipment', 'Veterinary', 'Maintenance', 'Supplies', 'Start-up', 'Other'];
        $expenses = Expense::factory()->count(20)->create();

        foreach ($expenses as $expense) {
            $this->assertContains($expense->category, $validCategories);
        }
    }

    public function test_expense_belongs_to_user_via_foreign_key(): void
    {
        $user = User::factory()->create();
        Expense::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('expenses', ['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('expenses', ['user_id' => $user->id]);
    }

    public function test_expense_seeder_creates_entries_for_users(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(ExpenseSeeder::class);

        $premiumUser = User::where('tier', 'premium')->first();
        $this->assertNotNull($premiumUser);
        $this->assertGreaterThanOrEqual(20, $premiumUser->expenses()->count());
    }
}
