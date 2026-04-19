<?php

namespace Tests\Unit;

use App\Models\DeathRecord;
use App\Models\FlockBatch;
use App\Models\User;
use App\Policies\DeathRecordPolicy;
use PHPUnit\Framework\TestCase;

class DeathRecordPolicyTest extends TestCase
{
    private DeathRecordPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new DeathRecordPolicy();
    }

    private function makeUser(int $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }

    private function makeBatch(int $userId): FlockBatch
    {
        $batch = new FlockBatch();
        $batch->user_id = $userId;

        return $batch;
    }

    private function makeDeath(int $userId, int $batchOwnerId): DeathRecord
    {
        $death = new DeathRecord();
        $death->user_id = $userId;
        $death->setRelation('flockBatch', $this->makeBatch($batchOwnerId));

        return $death;
    }

    public function test_user_can_create_death_on_own_batch(): void
    {
        $this->assertTrue($this->policy->create($this->makeUser(1), $this->makeBatch(1)));
    }

    public function test_user_cannot_create_death_on_another_users_batch(): void
    {
        $this->assertFalse($this->policy->create($this->makeUser(1), $this->makeBatch(2)));
    }

    public function test_user_can_update_own_death_record(): void
    {
        $this->assertTrue($this->policy->update($this->makeUser(1), $this->makeDeath(1, 1)));
    }

    public function test_user_cannot_update_another_users_death_record(): void
    {
        $this->assertFalse($this->policy->update($this->makeUser(1), $this->makeDeath(2, 2)));
    }

    public function test_user_can_delete_own_death_record(): void
    {
        $this->assertTrue($this->policy->delete($this->makeUser(1), $this->makeDeath(1, 1)));
    }

    public function test_user_cannot_delete_another_users_death_record(): void
    {
        $this->assertFalse($this->policy->delete($this->makeUser(1), $this->makeDeath(2, 2)));
    }
}
