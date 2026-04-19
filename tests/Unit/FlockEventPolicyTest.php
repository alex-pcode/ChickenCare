<?php

namespace Tests\Unit;

use App\Models\FlockEvent;
use App\Models\FlockProfile;
use App\Models\User;
use App\Policies\FlockEventPolicy;
use PHPUnit\Framework\TestCase;

class FlockEventPolicyTest extends TestCase
{
    private FlockEventPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new FlockEventPolicy();
    }

    private function makeUser(int $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }

    private function makeProfile(int $ownerId): FlockProfile
    {
        $profile = new FlockProfile();
        $profile->user_id = $ownerId;

        return $profile;
    }

    private function makeEvent(int $profileOwnerId): FlockEvent
    {
        $profile = $this->makeProfile($profileOwnerId);

        $event = new FlockEvent();
        $event->setRelation('flockProfile', $profile);

        return $event;
    }

    public function test_user_can_create_event_on_own_profile(): void
    {
        $this->assertTrue($this->policy->create($this->makeUser(1), $this->makeProfile(1)));
    }

    public function test_user_cannot_create_event_on_another_users_profile(): void
    {
        $this->assertFalse($this->policy->create($this->makeUser(1), $this->makeProfile(2)));
    }

    public function test_user_can_update_own_event(): void
    {
        $this->assertTrue($this->policy->update($this->makeUser(1), $this->makeEvent(1)));
    }

    public function test_user_cannot_update_another_users_event(): void
    {
        $this->assertFalse($this->policy->update($this->makeUser(1), $this->makeEvent(2)));
    }

    public function test_user_can_delete_own_event(): void
    {
        $this->assertTrue($this->policy->delete($this->makeUser(1), $this->makeEvent(1)));
    }

    public function test_user_cannot_delete_another_users_event(): void
    {
        $this->assertFalse($this->policy->delete($this->makeUser(1), $this->makeEvent(2)));
    }
}
