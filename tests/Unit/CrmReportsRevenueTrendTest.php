<?php

namespace Tests\Unit;

use App\Models\Sale;
use App\Models\User;
use App\Services\CrmReportsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmReportsRevenueTrendTest extends TestCase
{
    use RefreshDatabase;

    public function test_month_period_returns_daily_points_for_current_month(): void
    {
        $user = User::factory()->create();
        $service = app(CrmReportsService::class);

        Sale::factory()->for($user)->create([
            'sale_date' => now()->startOfMonth()->toDateString(),
            'total_amount' => 10.00,
        ]);
        Sale::factory()->for($user)->create([
            'sale_date' => now()->startOfMonth()->addDays(2)->toDateString(),
            'total_amount' => 5.50,
        ]);

        $trend = $service->revenueTrend($user, 'month');

        $this->assertCount(now()->daysInMonth, $trend);
        $this->assertSame(10.00, $trend[0]['revenue']);
        $this->assertSame(0.0, $trend[1]['revenue']);
        $this->assertSame(5.50, $trend[2]['revenue']);
    }

    public function test_all_period_returns_monthly_points(): void
    {
        $user = User::factory()->create();
        $service = app(CrmReportsService::class);

        $trend = $service->revenueTrend($user, 'all');

        $this->assertCount(12, $trend);
    }
}
