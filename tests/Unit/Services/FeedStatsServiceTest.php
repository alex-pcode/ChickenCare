<?php

namespace Tests\Unit\Services;

use App\Models\DeathRecord;
use App\Models\FeedInventory;
use App\Models\FlockBatch;
use App\Models\User;
use App\Services\FeedStatsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private FeedStatsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 4, 20));
        $this->service = new FeedStatsService;
    }

    public function test_monthly_trends_groups_depleted_feed_by_month(): void
    {
        $user = User::factory()->create();
        $batch = FlockBatch::factory()->create([
            'user_id' => $user->id,
            'acquisition_date' => '2026-01-01',
            'initial_count' => 12,
            'current_count' => 10,
            'is_active' => true,
        ]);

        DeathRecord::factory()->create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'date' => '2026-04-10',
            'count' => 2,
        ]);

        FeedInventory::factory()->depleted()->create([
            'user_id' => $user->id,
            'opened_date' => '2026-03-01',
            'depleted_date' => '2026-03-15',
            'total_cost' => 20,
        ]);

        FeedInventory::factory()->depleted()->create([
            'user_id' => $user->id,
            'opened_date' => '2026-03-10',
            'depleted_date' => '2026-03-22',
            'total_cost' => 10,
        ]);

        FeedInventory::factory()->depleted()->create([
            'user_id' => $user->id,
            'opened_date' => '2026-04-05',
            'depleted_date' => '2026-04-18',
            'total_cost' => 30,
        ]);

        $trends = $this->service->for($user)->monthlyTrends('3months');

        $this->assertCount(2, $trends);
        $this->assertSame('Mar 2026', $trends[0]['month']);
        $this->assertEquals(30.0, $trends[0]['totalCost']);
        $this->assertSame(2, $trends[0]['feedCount']);
        $this->assertSame(12, $trends[0]['avgFlockSize']);
        $this->assertEquals(2.5, $trends[0]['costPerBirdPerMonth']);
        $this->assertSame('Apr 2026', $trends[1]['month']);
        $this->assertEquals(30.0, $trends[1]['totalCost']);
        $this->assertSame(1, $trends[1]['feedCount']);
    }
}
