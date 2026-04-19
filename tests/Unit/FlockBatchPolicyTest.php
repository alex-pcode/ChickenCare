<?php

namespace Tests\Unit;

use App\Models\FlockBatch;
use App\Models\User;
use App\Policies\FlockBatchPolicy;
use PHPUnit\Framework\TestCase;

class FlockBatchPolicyTest extends TestCase
{
    private FlockBatchPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new FlockBatchPolicy();
    }

    private function makeUser(int $id, string $tier = 'premium'): User
    {
        $user = new User();
        $user->id = $id;
        $user->tier = $tier;
        $user->is_admin = false;

        return $user;
    }

    private function makeBatch(int $userId): FlockBatch
    {
        $batch = new FlockBatch();
        $batch->user_id = $userId;

        return $batch;
    }

    public function test_user_can_view_own_batch(): void
    {
        $this->assertTrue($this->policy->view($this->makeUser(1), $this->makeBatch(1)));
    }

    public function test_user_cannot_view_another_users_batch(): void
    {
        $this->assertFalse($this->policy->view($this->makeUser(1), $this->makeBatch(2)));
    }

    public function test_user_can_update_own_batch(): void
    {
        $this->assertTrue($this->policy->update($this->makeUser(1), $this->makeBatch(1)));
    }

    public function test_user_cannot_update_another_users_batch(): void
    {
        $this->assertFalse($this->policy->update($this->makeUser(1), $this->makeBatch(2)));
    }

    public function test_user_can_delete_own_batch(): void
    {
        $this->assertTrue($this->policy->delete($this->makeUser(1), $this->makeBatch(1)));
    }

    public function test_user_cannot_delete_another_users_batch(): void
    {
        $this->assertFalse($this->policy->delete($this->makeUser(1), $this->makeBatch(2)));
    }

    public function test_any_premium_user_can_create_batch(): void
    {
        $this->assertTrue($this->policy->create($this->makeUser(1, 'premium')));
    }
}
