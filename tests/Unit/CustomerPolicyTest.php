<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\User;
use App\Policies\CustomerPolicy;
use PHPUnit\Framework\TestCase;

class CustomerPolicyTest extends TestCase
{
    private CustomerPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CustomerPolicy();
    }

    private function makeUser(int $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }

    private function makeCustomer(int $userId): Customer
    {
        $customer = new Customer();
        $customer->user_id = $userId;

        return $customer;
    }

    public function test_user_can_view_own_customer(): void
    {
        $this->assertTrue($this->policy->view($this->makeUser(1), $this->makeCustomer(1)));
    }

    public function test_user_cannot_view_other_users_customer(): void
    {
        $this->assertFalse($this->policy->view($this->makeUser(1), $this->makeCustomer(2)));
    }

    public function test_user_can_update_own_customer(): void
    {
        $this->assertTrue($this->policy->update($this->makeUser(1), $this->makeCustomer(1)));
    }

    public function test_user_cannot_update_other_users_customer(): void
    {
        $this->assertFalse($this->policy->update($this->makeUser(1), $this->makeCustomer(2)));
    }

    public function test_user_can_delete_own_customer(): void
    {
        $this->assertTrue($this->policy->delete($this->makeUser(1), $this->makeCustomer(1)));
    }

    public function test_user_cannot_delete_other_users_customer(): void
    {
        $this->assertFalse($this->policy->delete($this->makeUser(1), $this->makeCustomer(2)));
    }
}
