<?php

namespace Tests\Unit;

use App\Models\BatchEvent;
use App\Models\DeathRecord;
use App\Models\EggEntry;
use App\Models\Expense;
use App\Models\FlockBatch;
use App\Models\Sale;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DashboardService;
        $this->user = User::factory()->premium()->create();
    }

    public function test_get_summary_returns_expected_keys(): void
    {
        $summary = $this->service->getSummary($this->user);

        $this->assertArrayHasKey('eggs', $summary);
        $this->assertArrayHasKey('financial', $summary);
        $this->assertArrayHasKey('flock', $summary);
        $this->assertArrayHasKey('recent_activity', $summary);
    }

    public function test_egg_stats_today_counts_todays_entries(): void
    {
        EggEntry::factory()->create(['user_id' => $this->user->id, 'date' => now()->toDateString(), 'count' => 5]);
        EggEntry::factory()->create(['user_id' => $this->user->id, 'date' => now()->toDateString(), 'count' => 3]);
        EggEntry::factory()->create(['user_id' => $this->user->id, 'date' => now()->subDay()->toDateString(), 'count' => 10]);

        $summary = $this->service->getSummary($this->user);

        $this->assertEquals(8, $summary['eggs']['today']);
    }

    public function test_egg_stats_this_week_sums_correct_entries(): void
    {
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->startOfWeek()->toDateString(),
            'count' => 7,
        ]);
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->subWeek()->startOfWeek()->toDateString(),
            'count' => 20,
        ]);

        $summary = $this->service->getSummary($this->user);

        $this->assertEquals(7, $summary['eggs']['this_week']);
    }

    public function test_egg_stats_this_month_sums_correct_entries(): void
    {
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->startOfMonth()->toDateString(),
            'count' => 12,
        ]);
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->subMonth()->toDateString(),
            'count' => 50,
        ]);

        $summary = $this->service->getSummary($this->user);

        $this->assertEquals(12, $summary['eggs']['this_month']);
    }

    public function test_egg_stats_daily_average_rounds_to_one_decimal(): void
    {
        EggEntry::factory()->create(['user_id' => $this->user->id, 'date' => now()->toDateString(), 'count' => 5]);
        EggEntry::factory()->create(['user_id' => $this->user->id, 'date' => now()->subDay()->toDateString(), 'count' => 8]);
        // Only the entry for current month matters — if both are this month, avg = (5+8)/2 = 6.5
        // If subDay crossed month boundary, only today's entry counts

        $summary = $this->service->getSummary($this->user);

        $this->assertIsFloat($summary['eggs']['daily_average']);
        $decimals = strlen(substr(strrchr((string) $summary['eggs']['daily_average'], '.'), 1));
        $this->assertLessThanOrEqual(1, $decimals);
    }

    public function test_flock_stats_total_birds_only_counts_active_batches(): void
    {
        FlockBatch::factory()->active()->create([
            'user_id' => $this->user->id,
            'current_count' => 10,
        ]);
        FlockBatch::factory()->archived()->create([
            'user_id' => $this->user->id,
            'current_count' => 50,
        ]);

        $summary = $this->service->getSummary($this->user);

        $this->assertEquals(10, $summary['flock']['total_birds']);
    }

    public function test_flock_stats_active_batches_count_is_correct(): void
    {
        FlockBatch::factory()->active()->count(3)->create(['user_id' => $this->user->id]);
        FlockBatch::factory()->archived()->count(2)->create(['user_id' => $this->user->id]);

        $summary = $this->service->getSummary($this->user);

        $this->assertEquals(3, $summary['flock']['active_batches']);
    }

    public function test_recent_activity_returns_collection(): void
    {
        $summary = $this->service->getSummary($this->user);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $summary['recent_activity']);
    }

    public function test_recent_activity_sorts_by_date_descending(): void
    {
        EggEntry::factory()->create(['user_id' => $this->user->id, 'date' => now()->subDays(5)->toDateString(), 'count' => 1]);
        EggEntry::factory()->create(['user_id' => $this->user->id, 'date' => now()->toDateString(), 'count' => 2]);
        EggEntry::factory()->create(['user_id' => $this->user->id, 'date' => now()->subDays(2)->toDateString(), 'count' => 3]);

        $summary = $this->service->getSummary($this->user);
        $dates = $summary['recent_activity']->pluck('date')->toArray();

        for ($i = 0; $i < count($dates) - 1; $i++) {
            $this->assertTrue($dates[$i] >= $dates[$i + 1]);
        }
    }

    public function test_recent_activity_limits_to_ten_items(): void
    {
        // Create 15 items of mixed types
        EggEntry::factory()->count(5)->create(['user_id' => $this->user->id]);
        Sale::factory()->count(5)->create(['user_id' => $this->user->id]);

        $batch = FlockBatch::factory()->create(['user_id' => $this->user->id]);
        BatchEvent::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'batch_id' => $batch->id,
        ]);

        $summary = $this->service->getSummary($this->user);

        $this->assertLessThanOrEqual(10, $summary['recent_activity']->count());
    }

    public function test_egg_chart_data_returns_last_30_days(): void
    {
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'count' => 5,
        ]);

        $chartData = $this->service->getEggChartData($this->user);

        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('datasets', $chartData);
        $this->assertCount(30, $chartData['labels']);
        $this->assertCount(30, $chartData['datasets'][0]['data']);
    }

    public function test_financial_stats_sums_expenses_correctly(): void
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'amount' => 25.50,
        ]);
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'amount' => 14.50,
        ]);
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->subMonths(2)->toDateString(),
            'amount' => 100.00,
        ]);

        $summary = $this->service->getSummary($this->user);

        $this->assertEquals('140.00', $summary['financial']['total_expenses']);
        $this->assertEquals('40.00', $summary['financial']['month_expenses']);
    }
}
