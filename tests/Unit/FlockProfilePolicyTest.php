<?php

namespace Tests\Unit;

use App\Models\FlockProfile;
use App\Models\User;
use App\Policies\FlockProfilePolicy;
use PHPUnit\Framework\TestCase;

class FlockProfilePolicyTest extends TestCase
{
    private FlockProfilePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new FlockProfilePolicy();
    }

    private function makeUser(int $id, string $tier = 'premium'): User
    {
        $user = new User();
        $user->id = $id;
        $user->tier = $tier;
        $user->is_admin = false;

        return $user;
    }

    private function makeProfile(int $userId): FlockProfile
    {
        $profile = new FlockProfile();
        $profile->user_id = $userId;

        return $profile;
    }

    public function test_user_can_view_own_profile(): void
    {
        $this->assertTrue($this->policy->view($this->makeUser(1), $this->makeProfile(1)));
    }

    public function test_user_cannot_view_another_users_profile(): void
    {
        $this->assertFalse($this->policy->view($this->makeUser(1), $this->makeProfile(2)));
    }

    public function test_user_can_update_own_profile(): void
    {
        $this->assertTrue($this->policy->update($this->makeUser(1), $this->makeProfile(1)));
    }

    public function test_user_cannot_update_another_users_profile(): void
    {
        $this->assertFalse($this->policy->update($this->makeUser(1), $this->makeProfile(2)));
    }

    public function test_user_can_create_profile_when_none_exists(): void
    {
        $user = $this->makeUser(1);
        $user->setRelation('flockProfile', null);

        $this->assertTrue($this->policy->create($user));
    }

    public function test_user_cannot_delete_profile(): void
    {
        $this->assertFalse($this->policy->delete($this->makeUser(1), $this->makeProfile(1)));
    }
}
