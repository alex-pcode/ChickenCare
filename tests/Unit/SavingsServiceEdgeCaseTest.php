<?php

namespace Tests\Unit;

use App\Models\EggEntry;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\User;
use App\Services\SavingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsServiceEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private SavingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SavingsService();
    }

    public function test_financial_analysis_for_user_with_no_data(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertEquals(0.0, $result['income']['total_revenue']);
        $this->assertEquals(0.0, $result['expenses']['total_expenses']);
        $this->assertEquals(0.0, $result['profitability']['net_profit']);
        $this->assertEquals(0, $result['per_egg']['total_eggs']);
        $this->assertNull($result['per_egg']['cost_per_egg']);
    }

    public function test_profit_margin_is_null_when_revenue_is_zero(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 50.00,
        ]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertNull($result['profitability']['profit_margin_pct']);
    }

    public function test_per_egg_metrics_all_null_when_no_eggs(): void
    {
        $user = User::factory()->premium()->create();
        Sale::factory()->create(['user_id' => $user->id, 'total_amount' => 100.00]);
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 30.00]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertNull($result['per_egg']['cost_per_egg']);
        $this->assertNull($result['per_egg']['revenue_per_egg']);
        $this->assertNull($result['per_egg']['profit_per_egg']);
    }

    public function test_expenses_with_only_one_category(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->count(3)->create([
            'user_id' => $user->id,
            'category' => 'feed',
        ]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertCount(1, $result['expenses']['by_category']);
        $this->assertArrayHasKey('feed', $result['expenses']['by_category']);
    }

    public function test_user_with_expenses_but_no_sales(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 100.00]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertEquals(-100.00, $result['profitability']['net_profit']);
        $this->assertFalse($result['profitability']['is_profitable']);
    }

    public function test_user_with_sales_but_no_expenses(): void
    {
        $user = User::factory()->premium()->create();
        Sale::factory()->create(['user_id' => $user->id, 'total_amount' => 200.00]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertEquals(200.00, $result['profitability']['net_profit']);
        $this->assertEquals(100.0, $result['profitability']['profit_margin_pct']);
        $this->assertTrue($result['profitability']['is_profitable']);
    }

    public function test_this_month_values_only_include_current_month(): void
    {
        $user = User::factory()->premium()->create();

        Sale::factory()->create([
            'user_id' => $user->id,
            'sale_date' => now()->toDateString(),
            'total_amount' => 50.00,
        ]);
        Sale::factory()->create([
            'user_id' => $user->id,
            'sale_date' => now()->subMonths(6)->toDateString(),
            'total_amount' => 200.00,
        ]);

        Expense::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'amount' => 20.00,
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subMonths(6)->toDateString(),
            'amount' => 80.00,
        ]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertEquals(50.00, $result['income']['this_month_revenue']);
        $this->assertEquals(20.00, $result['expenses']['this_month_expenses']);
    }
}
