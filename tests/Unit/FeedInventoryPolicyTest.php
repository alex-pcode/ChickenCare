<?php

namespace Tests\Unit;

use App\Models\FeedInventory;
use App\Models\User;
use App\Policies\FeedInventoryPolicy;
use PHPUnit\Framework\TestCase;

class FeedInventoryPolicyTest extends TestCase
{
    private FeedInventoryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new FeedInventoryPolicy();
    }

    private function makeUser(int $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }

    private function makeFeed(int $userId): FeedInventory
    {
        $feed = new FeedInventory();
        $feed->user_id = $userId;

        return $feed;
    }

    public function test_user_can_view_own_feed_inventory(): void
    {
        $this->assertTrue($this->policy->view($this->makeUser(1), $this->makeFeed(1)));
    }

    public function test_user_cannot_view_other_users_feed_inventory(): void
    {
        $this->assertFalse($this->policy->view($this->makeUser(1), $this->makeFeed(2)));
    }

    public function test_user_can_update_own_feed_inventory(): void
    {
        $this->assertTrue($this->policy->update($this->makeUser(1), $this->makeFeed(1)));
    }

    public function test_user_cannot_update_other_users_feed_inventory(): void
    {
        $this->assertFalse($this->policy->update($this->makeUser(1), $this->makeFeed(2)));
    }

    public function test_user_can_delete_own_feed_inventory(): void
    {
        $this->assertTrue($this->policy->delete($this->makeUser(1), $this->makeFeed(1)));
    }

    public function test_user_cannot_delete_other_users_feed_inventory(): void
    {
        $this->assertFalse($this->policy->delete($this->makeUser(1), $this->makeFeed(2)));
    }
}
