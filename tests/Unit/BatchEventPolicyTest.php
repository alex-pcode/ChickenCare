<?php

namespace Tests\Unit;

use App\Models\BatchEvent;
use App\Models\FlockBatch;
use App\Models\User;
use App\Policies\BatchEventPolicy;
use PHPUnit\Framework\TestCase;

class BatchEventPolicyTest extends TestCase
{
    private BatchEventPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new BatchEventPolicy();
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

    private function makeEvent(int $userId, int $batchOwnerId): BatchEvent
    {
        $event = new BatchEvent();
        $event->user_id = $userId;
        $event->setRelation('flockBatch', $this->makeBatch($batchOwnerId));

        return $event;
    }

    public function test_user_can_create_event_on_own_batch(): void
    {
        $this->assertTrue($this->policy->create($this->makeUser(1), $this->makeBatch(1)));
    }

    public function test_user_cannot_create_event_on_another_users_batch(): void
    {
        $this->assertFalse($this->policy->create($this->makeUser(1), $this->makeBatch(2)));
    }

    public function test_user_can_update_own_batch_event(): void
    {
        $this->assertTrue($this->policy->update($this->makeUser(1), $this->makeEvent(1, 1)));
    }

    public function test_user_cannot_update_another_users_batch_event(): void
    {
        $this->assertFalse($this->policy->update($this->makeUser(1), $this->makeEvent(2, 2)));
    }

    public function test_user_can_delete_own_batch_event(): void
    {
        $this->assertTrue($this->policy->delete($this->makeUser(1), $this->makeEvent(1, 1)));
    }

    public function test_user_cannot_delete_another_users_batch_event(): void
    {
        $this->assertFalse($this->policy->delete($this->makeUser(1), $this->makeEvent(2, 2)));
    }
}
