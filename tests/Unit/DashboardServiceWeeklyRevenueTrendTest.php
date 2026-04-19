<?php

namespace Tests\Unit;

use App\Models\Sale;
use App\Models\User;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceWeeklyRevenueTrendTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 4, 18)); // Friday
        $this->service = new DashboardService;
        $this->user = User::factory()->premium()->create();
    }

    public function testReturnsTwelveWeeksOfData(): void
    {
        $result = $this->service->getWeeklyRevenueTrend($this->user);

        $this->assertCount(12, $result['labels']);
        $this->assertCount(12, $result['datasets'][0]['data']);
    }

    public function testReturnsCustomWeekCount(): void
    {
        $result = $this->service->getWeeklyRevenueTrend($this->user, 6);

        $this->assertCount(6, $result['labels']);
        $this->assertCount(6, $result['datasets'][0]['data']);
    }

    public function testZeroFillForWeeksWithNoSales(): void
    {
        // Create sales in only 2 specific weeks
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => '2026-04-15', // Week of 4/13
            'total_amount' => 25.00,
        ]);
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => '2026-02-03', // Week of 2/2
            'total_amount' => 10.00,
        ]);

        $result = $this->service->getWeeklyRevenueTrend($this->user);

        $nonZeroCount = count(array_filter($result['datasets'][0]['data'], fn ($v) => $v > 0));
        $this->assertSame(2, $nonZeroCount);

        // All other weeks should be zero
        $zeroCount = count(array_filter($result['datasets'][0]['data'], fn ($v) => $v == 0));
        $this->assertSame(10, $zeroCount);
    }

    public function testMondayAnchoredBuckets(): void
    {
        // Wednesday 2026-04-15 should go into the Monday 2026-04-13 bucket
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => '2026-04-15',
            'total_amount' => 30.00,
        ]);

        $result = $this->service->getWeeklyRevenueTrend($this->user);

        // The last label should be '4/13' (current week's Monday)
        $lastLabel = end($result['labels']);
        $this->assertSame('4/13', $lastLabel);

        // The last data point should contain our sale
        $lastData = end($result['datasets'][0]['data']);
        $this->assertSame(30.0, $lastData);
    }

    public function testWeekLabelsFormat(): void
    {
        $result = $this->service->getWeeklyRevenueTrend($this->user);

        foreach ($result['labels'] as $label) {
            $this->assertMatchesRegularExpression('/^\d{1,2}\/\d{1,2}$/', $label);
        }
    }

    public function testDatasetStructure(): void
    {
        $result = $this->service->getWeeklyRevenueTrend($this->user);
        $dataset = $result['datasets'][0];

        $this->assertSame('Weekly Revenue', $dataset['label']);
        $this->assertSame('rgba(84, 76, 230, 0.3)', $dataset['backgroundColor']);
        $this->assertSame('#544CE6', $dataset['borderColor']);
        $this->assertSame(2, $dataset['borderWidth']);
        $this->assertSame(0.35, $dataset['tension']);
        $this->assertSame('origin', $dataset['fill']);
        $this->assertSame('#544CE6', $dataset['pointBackgroundColor']);
        $this->assertSame(3, $dataset['pointRadius']);
    }

    public function testMultipleSalesInSameWeek(): void
    {
        // All in the same week (week of 4/13)
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => '2026-04-13',
            'total_amount' => 10.00,
        ]);
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => '2026-04-15',
            'total_amount' => 20.00,
        ]);
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => '2026-04-17',
            'total_amount' => 15.50,
        ]);

        $result = $this->service->getWeeklyRevenueTrend($this->user);

        $lastData = end($result['datasets'][0]['data']);
        $this->assertSame(45.50, $lastData);
    }

    public function testYearBoundaryRollover(): void
    {
        // Freeze time to January 2026
        $this->travelTo(Carbon::create(2026, 1, 9)); // Friday Jan 9

        // Create a sale in late December 2025
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => '2025-12-16', // Tuesday, week of 12/15
            'total_amount' => 42.00,
        ]);

        $result = $this->service->getWeeklyRevenueTrend($this->user);

        // Should have 12 weeks of labels
        $this->assertCount(12, $result['labels']);

        // The December sale should appear somewhere in the data
        $this->assertContains(42.0, $result['datasets'][0]['data']);
    }
}
