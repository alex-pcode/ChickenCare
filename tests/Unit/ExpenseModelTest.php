<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(BelongsTo::class, $expense->user());
        $this->assertTrue($expense->user->is($user));
    }

    public function test_expense_fillable_attributes(): void
    {
        $expense = new Expense();

        $this->assertEquals(['date', 'category', 'description', 'amount'], $expense->getFillable());
    }

    public function test_expense_casts_date_to_carbon(): void
    {
        $expense = Expense::factory()->create();

        $this->assertInstanceOf(Carbon::class, $expense->date);
    }

    public function test_expense_casts_amount_to_decimal(): void
    {
        $expense = Expense::factory()->create(['amount' => 123.45]);

        $this->assertSame('123.45', $expense->amount);
    }
}
