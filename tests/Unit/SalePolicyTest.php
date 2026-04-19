<?php

namespace Tests\Unit;

use App\Models\Sale;
use App\Models\User;
use App\Policies\SalePolicy;
use PHPUnit\Framework\TestCase;

class SalePolicyTest extends TestCase
{
    private function makeUser(int $id): User
    {
        $user = new User;
        $user->id = $id;

        return $user;
    }

    private function makeSale(int $userId): Sale
    {
        $sale = new Sale;
        $sale->user_id = $userId;

        return $sale;
    }

    public function test_user_can_view_own_sale(): void
    {
        $policy = new SalePolicy;
        $user = $this->makeUser(1);
        $sale = $this->makeSale(1);

        $this->assertTrue($policy->view($user, $sale));
    }

    public function test_user_cannot_view_other_users_sale(): void
    {
        $policy = new SalePolicy;
        $user = $this->makeUser(1);
        $sale = $this->makeSale(2);

        $this->assertFalse($policy->view($user, $sale));
    }

    public function test_user_can_update_own_sale(): void
    {
        $policy = new SalePolicy;
        $user = $this->makeUser(1);
        $sale = $this->makeSale(1);

        $this->assertTrue($policy->update($user, $sale));
    }

    public function test_user_cannot_update_other_users_sale(): void
    {
        $policy = new SalePolicy;
        $user = $this->makeUser(1);
        $sale = $this->makeSale(2);

        $this->assertFalse($policy->update($user, $sale));
    }

    public function test_user_can_delete_own_sale(): void
    {
        $policy = new SalePolicy;
        $user = $this->makeUser(1);
        $sale = $this->makeSale(1);

        $this->assertTrue($policy->delete($user, $sale));
    }

    public function test_user_cannot_delete_other_users_sale(): void
    {
        $policy = new SalePolicy;
        $user = $this->makeUser(1);
        $sale = $this->makeSale(2);

        $this->assertFalse($policy->delete($user, $sale));
    }
}
