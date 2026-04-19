<?php

namespace Tests\Unit;

use App\Models\BatchEvent;
use App\Models\EggEntry;
use App\Models\FlockBatch;
use App\Models\Sale;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardService();
    }

    public function test_egg_stats_all_zero_for_new_user_with_no_entries(): void
    {
        $user = User::factory()->premium()->create();

        $summary = $this->service->getSummary($user);

        $this->assertEquals(0, $summary['eggs']['today']);
        $this->assertEquals(0, $summary['eggs']['this_week']);
        $this->assertEquals(0, $summary['eggs']['this_month']);
        $this->assertEquals(0, $summary['eggs']['daily_average']);
    }

    public function test_egg_chart_data_fills_zero_for_days_with_no_entries(): void
    {
        $user = User::factory()->premium()->create();

        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDays(29)->toDateString(),
            'count' => 5,
        ]);
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'count' => 3,
        ]);

        $chartData = $this->service->getEggChartData($user);

        $this->assertCount(30, $chartData['labels']);
        $this->assertCount(30, $chartData['datasets'][0]['data']);

        $nonZero = array_filter($chartData['datasets'][0]['data'], fn ($v) => $v > 0);
        $this->assertCount(2, $nonZero);
    }

    public function test_flock_stats_all_zero_for_user_with_no_batches(): void
    {
        $user = User::factory()->premium()->create();

        $summary = $this->service->getSummary($user);

        $this->assertEquals(0, $summary['flock']['total_birds']);
        $this->assertEquals(0, $summary['flock']['active_batches']);
        $this->assertEquals(0, $summary['flock']['total_hens']);
        $this->assertEquals(0, $summary['flock']['total_mortality']);
    }

    public function test_flock_stats_excludes_inactive_batches_from_counts(): void
    {
        $user = User::factory()->premium()->create();

        FlockBatch::factory()->active()->create([
            'user_id' => $user->id,
            'hens_count' => 5,
            'current_count' => 5,
        ]);
        FlockBatch::factory()->archived()->create([
            'user_id' => $user->id,
            'hens_count' => 10,
            'current_count' => 10,
        ]);

        $summary = $this->service->getSummary($user);

        $this->assertEquals(5, $summary['flock']['total_hens']);
    }

    public function test_recent_activity_empty_collection_for_new_user(): void
    {
        $user = User::factory()->premium()->create();

        $summary = $this->service->getSummary($user);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $summary['recent_activity']);
        $this->assertTrue($summary['recent_activity']->isEmpty());
    }

    public function test_recent_activity_includes_all_three_types_when_all_present(): void
    {
        $user = User::factory()->premium()->create();

        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
        ]);
        Sale::factory()->create([
            'user_id' => $user->id,
            'sale_date' => now()->toDateString(),
        ]);
        $batch = FlockBatch::factory()->create(['user_id' => $user->id]);
        BatchEvent::factory()->create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'date' => now()->toDateString(),
        ]);

        $summary = $this->service->getSummary($user);
        $types = $summary['recent_activity']->pluck('type')->unique()->toArray();

        $this->assertContains('egg', $types);
        $this->assertContains('sale', $types);
        $this->assertContains('batch_event', $types);
    }

    public function test_egg_chart_data_handles_single_entry_correctly(): void
    {
        $user = User::factory()->premium()->create();

        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDays(15)->toDateString(),
            'count' => 7,
        ]);

        $chartData = $this->service->getEggChartData($user);

        $nonZero = array_filter($chartData['datasets'][0]['data'], fn ($v) => $v > 0);
        $this->assertCount(1, $nonZero);
        $this->assertContains(7, $chartData['datasets'][0]['data']);
    }

    public function test_get_summary_does_not_throw_when_no_data_exists(): void
    {
        $user = User::factory()->premium()->create();

        $summary = $this->service->getSummary($user);

        $this->assertIsArray($summary);
        $this->assertEquals('0.00', $summary['financial']['total_revenue']);
        $this->assertEquals('0.00', $summary['financial']['total_expenses']);
    }
}
