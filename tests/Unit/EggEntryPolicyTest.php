<?php

namespace Tests\Unit;

use App\Models\EggEntry;
use App\Models\User;
use App\Policies\EggEntryPolicy;
use PHPUnit\Framework\TestCase;

class EggEntryPolicyTest extends TestCase
{
    private EggEntryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new EggEntryPolicy();
    }

    private function makeUser(int $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }

    private function makeEntry(int $userId): EggEntry
    {
        $entry = new EggEntry();
        $entry->user_id = $userId;

        return $entry;
    }

    public function test_user_can_view_own_egg_entry(): void
    {
        $this->assertTrue($this->policy->view($this->makeUser(1), $this->makeEntry(1)));
    }

    public function test_user_cannot_view_other_users_egg_entry(): void
    {
        $this->assertFalse($this->policy->view($this->makeUser(1), $this->makeEntry(2)));
    }

    public function test_user_can_update_own_egg_entry(): void
    {
        $this->assertTrue($this->policy->update($this->makeUser(1), $this->makeEntry(1)));
    }

    public function test_user_cannot_update_other_users_egg_entry(): void
    {
        $this->assertFalse($this->policy->update($this->makeUser(1), $this->makeEntry(2)));
    }

    public function test_user_can_delete_own_egg_entry(): void
    {
        $this->assertTrue($this->policy->delete($this->makeUser(1), $this->makeEntry(1)));
    }

    public function test_user_cannot_delete_other_users_egg_entry(): void
    {
        $this->assertFalse($this->policy->delete($this->makeUser(1), $this->makeEntry(2)));
    }

    // === Story 2.3: Policy Edge-Case Tests (Task 6) ===

    public function test_policy_view_returns_true_for_owner(): void
    {
        $user = $this->makeUser(42);
        $entry = $this->makeEntry(42);

        $this->assertTrue($this->policy->view($user, $entry));
    }

    public function test_policy_blocks_access_with_mismatched_user_id(): void
    {
        $user = $this->makeUser(100);
        $entry = $this->makeEntry(101);

        $this->assertFalse($this->policy->view($user, $entry));
        $this->assertFalse($this->policy->update($user, $entry));
        $this->assertFalse($this->policy->delete($user, $entry));
    }
}
