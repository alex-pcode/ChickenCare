<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\User;
use App\Policies\ExpensePolicy;
use PHPUnit\Framework\TestCase;

class ExpensePolicyTest extends TestCase
{
    private ExpensePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ExpensePolicy();
    }

    private function makeUser(int $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }

    private function makeExpense(int $userId): Expense
    {
        $expense = new Expense();
        $expense->user_id = $userId;

        return $expense;
    }

    public function test_user_can_view_own_expense(): void
    {
        $this->assertTrue($this->policy->view($this->makeUser(1), $this->makeExpense(1)));
    }

    public function test_user_cannot_view_other_users_expense(): void
    {
        $this->assertFalse($this->policy->view($this->makeUser(1), $this->makeExpense(2)));
    }

    public function test_user_can_update_own_expense(): void
    {
        $this->assertTrue($this->policy->update($this->makeUser(1), $this->makeExpense(1)));
    }

    public function test_user_cannot_update_other_users_expense(): void
    {
        $this->assertFalse($this->policy->update($this->makeUser(1), $this->makeExpense(2)));
    }

    public function test_user_can_delete_own_expense(): void
    {
        $this->assertTrue($this->policy->delete($this->makeUser(1), $this->makeExpense(1)));
    }

    public function test_user_cannot_delete_other_users_expense(): void
    {
        $this->assertFalse($this->policy->delete($this->makeUser(1), $this->makeExpense(2)));
    }
}
