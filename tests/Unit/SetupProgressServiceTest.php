<?php

namespace Tests\Unit;

use App\Models\EggEntry;
use App\Models\Expense;
use App\Models\FeedInventory;
use App\Models\FlockBatch;
use App\Models\FlockProfile;
use App\Models\User;
use App\Services\SetupProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private SetupProgressService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SetupProgressService;
        $this->user = User::factory()->create();
    }

    public function test_brand_new_user_has_zero_progress(): void
    {
        $result = $this->service->compute($this->user);

        $this->assertEquals(0, $result['percentage']);
        $this->assertEquals('new', $result['bracket']);
        $this->assertEquals('New User', $result['phase']['label']);
        $this->assertCount(4, $result['items']);
        $this->assertFalse($result['items'][0]['completed']);
        $this->assertFalse($result['items'][1]['completed']);
        $this->assertFalse($result['items'][2]['completed']);
        $this->assertFalse($result['items'][3]['completed']);
    }

    public function test_flock_profile_completes_setup_flock_item(): void
    {
        FlockProfile::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->compute($this->user);

        $this->assertTrue($result['items'][0]['completed']);
    }

    public function test_flock_batch_also_completes_setup_flock_item(): void
    {
        FlockBatch::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->compute($this->user);

        $this->assertTrue($result['items'][0]['completed']);
    }

    public function test_empty_placeholder_flock_profile_does_not_complete_setup_flock_item(): void
    {
        // Visiting the flock page auto-creates an empty profile; it must not count as setup.
        FlockProfile::factory()->create([
            'user_id' => $this->user->id,
            'flock_size' => 0,
            'hens' => 0,
            'roosters' => 0,
            'chicks' => 0,
            'brooding' => 0,
        ]);

        $result = $this->service->compute($this->user);

        $this->assertFalse($result['items'][0]['completed']);
    }

    public function test_egg_entry_completes_add_eggs_item(): void
    {
        EggEntry::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->compute($this->user);

        $this->assertTrue($result['items'][1]['completed']);
    }

    public function test_expense_completes_add_expense_item(): void
    {
        Expense::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->compute($this->user);

        $this->assertTrue($result['items'][2]['completed']);
    }

    public function test_feed_inventory_completes_add_feed_item(): void
    {
        FeedInventory::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->compute($this->user);

        $this->assertTrue($result['items'][3]['completed']);
    }

    public function test_percentage_with_flock_profile_only(): void
    {
        FlockProfile::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->compute($this->user);

        // 50/120 = 41.67 -> 42%
        $this->assertEquals(42, $result['percentage']);
    }

    public function test_percentage_with_flock_and_eggs(): void
    {
        FlockProfile::factory()->create(['user_id' => $this->user->id]);
        EggEntry::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->compute($this->user);

        // (50 + 30)/120 = 66.67 -> 67%
        $this->assertEquals(67, $result['percentage']);
    }

    public function test_all_items_complete_gives_100_percent(): void
    {
        FlockProfile::factory()->create(['user_id' => $this->user->id]);
        EggEntry::factory()->create(['user_id' => $this->user->id]);
        Expense::factory()->create(['user_id' => $this->user->id]);
        FeedInventory::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->compute($this->user);

        $this->assertEquals(100, $result['percentage']);
        $this->assertTrue($result['items'][0]['completed']);
        $this->assertTrue($result['items'][1]['completed']);
        $this->assertTrue($result['items'][2]['completed']);
        $this->assertTrue($result['items'][3]['completed']);
    }

    public function test_bracket_new_for_zero_percent(): void
    {
        $result = $this->service->compute($this->user);

        $this->assertEquals('new', $result['bracket']);
        $this->assertEquals('New User', $result['phase']['label']);
        $this->assertEquals('Get started with basic setup', $result['phase']['message']);
    }

    public function test_bracket_getting_started_for_42_percent(): void
    {
        FlockProfile::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->compute($this->user);

        // 42% -> getting-started bracket (41-70)
        $this->assertEquals('getting-started', $result['bracket']);
        $this->assertEquals('Getting Started', $result['phase']['label']);
        $this->assertEquals('Expand to core features', $result['phase']['message']);
    }

    public function test_bracket_active_for_flock_eggs_and_expense(): void
    {
        FlockProfile::factory()->create(['user_id' => $this->user->id]);
        EggEntry::factory()->create(['user_id' => $this->user->id]);
        Expense::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->compute($this->user);

        // (50 + 30 + 20)/120 = 83% -> active bracket (71-90)
        $this->assertEquals(83, $result['percentage']);
        $this->assertEquals('active', $result['bracket']);
        $this->assertEquals('Active User', $result['phase']['label']);
    }

    public function test_bracket_power_for_100_percent(): void
    {
        FlockProfile::factory()->create(['user_id' => $this->user->id]);
        EggEntry::factory()->create(['user_id' => $this->user->id]);
        Expense::factory()->create(['user_id' => $this->user->id]);
        FeedInventory::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->compute($this->user);

        $this->assertEquals('power', $result['bracket']);
        $this->assertEquals('Power User', $result['phase']['label']);
        $this->assertEquals("You're using all features!", $result['phase']['message']);
    }

    public function test_items_have_correct_structure(): void
    {
        $result = $this->service->compute($this->user);

        foreach ($result['items'] as $item) {
            $this->assertArrayHasKey('key', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('points', $item);
            $this->assertArrayHasKey('icon', $item);
            $this->assertArrayHasKey('completed', $item);
            $this->assertArrayHasKey('action', $item);
            $this->assertArrayHasKey('action_href', $item);
        }
    }

    public function test_items_have_correct_keys_and_points(): void
    {
        $result = $this->service->compute($this->user);

        $this->assertEquals('setup-flock', $result['items'][0]['key']);
        $this->assertEquals(50, $result['items'][0]['points']);
        $this->assertEquals('add-eggs', $result['items'][1]['key']);
        $this->assertEquals(30, $result['items'][1]['points']);
        $this->assertEquals('add-expense', $result['items'][2]['key']);
        $this->assertEquals(20, $result['items'][2]['points']);
        $this->assertEquals('add-feed', $result['items'][3]['key']);
        $this->assertEquals(20, $result['items'][3]['points']);
    }

    public function test_action_hrefs_are_correct(): void
    {
        $result = $this->service->compute($this->user);

        $this->assertEquals(route('app.flock.index'), $result['items'][0]['action_href']);
        $this->assertEquals(route('app.eggs.index'), $result['items'][1]['action_href']);
        $this->assertEquals(route('app.expenses.index'), $result['items'][2]['action_href']);
        $this->assertEquals(route('app.feed.index'), $result['items'][3]['action_href']);
    }
}
