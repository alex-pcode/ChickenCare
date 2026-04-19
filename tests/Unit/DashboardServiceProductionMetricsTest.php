<?php

namespace Tests\Unit;

use App\Models\EggEntry;
use App\Models\User;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceProductionMetricsTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 4, 18));
        $this->service = new DashboardService;
        $this->user = User::factory()->create();
    }

    public function testProductionMetricsReturnsExpectedKeys(): void
    {
        $result = $this->service->getProductionMetrics($this->user);

        $this->assertArrayHasKey('totalEggs', $result);
        $this->assertArrayHasKey('dailyAverage', $result);
        $this->assertArrayHasKey('last7DaysTotal', $result);
        $this->assertArrayHasKey('previous7DaysTotal', $result);
        $this->assertArrayHasKey('thisMonthProduction', $result);
        $this->assertArrayHasKey('lastMonthProduction', $result);
        $this->assertArrayHasKey('weekDelta', $result);
        $this->assertArrayHasKey('monthDelta', $result);
    }

    public function testProductionMetricsWithNoData(): void
    {
        $result = $this->service->getProductionMetrics($this->user);

        $this->assertSame(0, $result['totalEggs']);
        $this->assertSame(0.0, $result['dailyAverage']);
        $this->assertSame(0, $result['last7DaysTotal']);
        $this->assertSame(0, $result['previous7DaysTotal']);
        $this->assertSame(0, $result['thisMonthProduction']);
        $this->assertSame(0, $result['lastMonthProduction']);
        $this->assertNull($result['weekDelta']);
        $this->assertNull($result['monthDelta']);
    }

    public function testTotalEggsCountsAllEntries(): void
    {
        EggEntry::factory()->for($this->user)->create(['date' => '2026-01-15', 'count' => 10]);
        EggEntry::factory()->for($this->user)->create(['date' => '2026-02-20', 'count' => 15]);
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-10', 'count' => 8]);

        $result = $this->service->getProductionMetrics($this->user);

        $this->assertSame(33, $result['totalEggs']);
    }

    public function testDailyAverageCalculation(): void
    {
        // April 2026, current day is 18
        // Create entries on days 5, 10, 15 => max day = 15, total = 30
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-05', 'count' => 10]);
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-10', 'count' => 12]);
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-15', 'count' => 8]);

        $result = $this->service->getProductionMetrics($this->user);

        // 30 / 15 = 2.0
        $this->assertSame(2.0, $result['dailyAverage']);
    }

    public function testLast7DaysTotal(): void
    {
        // Today is 2026-04-18, last 7 days = April 12-18 inclusive
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-12', 'count' => 5]);
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-15', 'count' => 7]);
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-18', 'count' => 3]);
        // Outside window
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-11', 'count' => 100]);

        $result = $this->service->getProductionMetrics($this->user);

        $this->assertSame(15, $result['last7DaysTotal']);
    }

    public function testPrevious7DaysTotal(): void
    {
        // Today is 2026-04-18, previous 7 days = April 5-11 inclusive
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-05', 'count' => 4]);
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-08', 'count' => 6]);
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-11', 'count' => 2]);
        // Outside window (in current 7 days)
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-12', 'count' => 100]);
        // Outside window (before previous 7 days)
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-04', 'count' => 100]);

        $result = $this->service->getProductionMetrics($this->user);

        $this->assertSame(12, $result['previous7DaysTotal']);
    }

    public function testWeekDeltaPositive(): void
    {
        // Previous 7 days (April 5-11): 10 eggs
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-07', 'count' => 10]);
        // Last 7 days (April 12-18): 15 eggs
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-14', 'count' => 15]);

        $result = $this->service->getProductionMetrics($this->user);

        // ((15 - 10) / 10) * 100 = 50
        $this->assertSame(50, $result['weekDelta']);
    }

    public function testWeekDeltaNegative(): void
    {
        // Previous 7 days (April 5-11): 20 eggs
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-07', 'count' => 20]);
        // Last 7 days (April 12-18): 10 eggs
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-14', 'count' => 10]);

        $result = $this->service->getProductionMetrics($this->user);

        // ((10 - 20) / 20) * 100 = -50
        $this->assertSame(-50, $result['weekDelta']);
    }

    public function testWeekDeltaZero(): void
    {
        // Previous 7 days (April 5-11): 10 eggs
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-07', 'count' => 10]);
        // Last 7 days (April 12-18): 10 eggs
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-14', 'count' => 10]);

        $result = $this->service->getProductionMetrics($this->user);

        $this->assertSame(0, $result['weekDelta']);
    }

    public function testWeekDeltaNullWhenNoPreviousData(): void
    {
        // Only last 7 days data
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-14', 'count' => 10]);

        $result = $this->service->getProductionMetrics($this->user);

        $this->assertNull($result['weekDelta']);
    }

    public function testThisMonthProduction(): void
    {
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-01', 'count' => 5]);
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-10', 'count' => 8]);
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-18', 'count' => 3]);
        // Different month — should not count
        EggEntry::factory()->for($this->user)->create(['date' => '2026-03-15', 'count' => 100]);

        $result = $this->service->getProductionMetrics($this->user);

        $this->assertSame(16, $result['thisMonthProduction']);
    }

    public function testLastMonthProductionWithSameDayCutoff(): void
    {
        // Current day is 18, so only last month entries on day <= 18 should count
        EggEntry::factory()->for($this->user)->create(['date' => '2026-03-05', 'count' => 10]);
        EggEntry::factory()->for($this->user)->create(['date' => '2026-03-18', 'count' => 7]);
        // Day 20 > 18, should NOT count
        EggEntry::factory()->for($this->user)->create(['date' => '2026-03-20', 'count' => 100]);
        // Day 25 > 18, should NOT count
        EggEntry::factory()->for($this->user)->create(['date' => '2026-03-25', 'count' => 50]);

        $result = $this->service->getProductionMetrics($this->user);

        $this->assertSame(17, $result['lastMonthProduction']);
    }

    public function testMonthDeltaCalculation(): void
    {
        // Last month (March, days <= 18): 20 eggs
        EggEntry::factory()->for($this->user)->create(['date' => '2026-03-10', 'count' => 20]);
        // This month (April): 30 eggs
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-10', 'count' => 30]);

        $result = $this->service->getProductionMetrics($this->user);

        // ((30 - 20) / 20) * 100 = 50
        $this->assertSame(50, $result['monthDelta']);
    }

    public function testMonthDeltaNullWhenNoLastMonthData(): void
    {
        // Only this month data
        EggEntry::factory()->for($this->user)->create(['date' => '2026-04-10', 'count' => 10]);

        $result = $this->service->getProductionMetrics($this->user);

        $this->assertNull($result['monthDelta']);
    }

    public function testJanuaryDecemberRollover(): void
    {
        $this->travelTo(Carbon::create(2026, 1, 15));

        // December 2025 entries (day <= 15)
        EggEntry::factory()->for($this->user)->create(['date' => '2025-12-10', 'count' => 12]);
        // January 2026 entries
        EggEntry::factory()->for($this->user)->create(['date' => '2026-01-10', 'count' => 18]);

        $result = $this->service->getProductionMetrics($this->user);

        $this->assertSame(12, $result['lastMonthProduction']);
        $this->assertSame(18, $result['thisMonthProduction']);
        // ((18 - 12) / 12) * 100 = 50
        $this->assertSame(50, $result['monthDelta']);
    }
}
