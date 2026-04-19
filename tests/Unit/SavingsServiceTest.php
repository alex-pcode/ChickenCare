<?php

namespace Tests\Unit;

use App\Models\EggEntry;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\User;
use App\Services\SavingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SavingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SavingsService();
    }

    public function test_get_financial_analysis_returns_expected_keys(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertArrayHasKey('income', $result);
        $this->assertArrayHasKey('expenses', $result);
        $this->assertArrayHasKey('profitability', $result);
        $this->assertArrayHasKey('per_egg', $result);
    }

    public function test_income_total_revenue_sums_all_sales(): void
    {
        $user = User::factory()->premium()->create();
        Sale::factory()->count(3)->create([
            'user_id' => $user->id,
            'total_amount' => 10.00,
        ]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertEquals(30.00, $result['income']['total_revenue']);
    }

    public function test_income_splits_paid_and_unpaid_correctly(): void
    {
        $user = User::factory()->premium()->create();
        Sale::factory()->paid()->count(2)->create([
            'user_id' => $user->id,
            'total_amount' => 15.00,
        ]);
        Sale::factory()->unpaid()->create([
            'user_id' => $user->id,
            'total_amount' => 5.00,
        ]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertEquals(30.00, $result['income']['paid_revenue']);
        $this->assertEquals(5.00, $result['income']['unpaid_revenue']);
    }

    public function test_expenses_total_sums_all_expenses(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->count(3)->create([
            'user_id' => $user->id,
            'amount' => 20.00,
        ]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertEquals(60.00, $result['expenses']['total_expenses']);
    }

    public function test_expenses_groups_by_category(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create([
            'user_id' => $user->id,
            'category' => 'feed',
            'amount' => 25.00,
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'category' => 'medical',
            'amount' => 15.00,
        ]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertArrayHasKey('feed', $result['expenses']['by_category']);
        $this->assertArrayHasKey('medical', $result['expenses']['by_category']);
        $this->assertEquals(25.00, $result['expenses']['by_category']['feed']);
        $this->assertEquals(15.00, $result['expenses']['by_category']['medical']);
    }

    public function test_profitability_net_profit_is_revenue_minus_expenses(): void
    {
        $user = User::factory()->premium()->create();
        Sale::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 100.00,
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 40.00,
        ]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertEquals(60.00, $result['profitability']['net_profit']);
    }

    public function test_profitability_margin_is_null_when_no_revenue(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 40.00,
        ]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertNull($result['profitability']['profit_margin_pct']);
    }

    public function test_profitability_is_profitable_true_when_revenue_exceeds_expenses(): void
    {
        $user = User::factory()->premium()->create();
        Sale::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 100.00,
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 40.00,
        ]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertTrue($result['profitability']['is_profitable']);
    }

    public function test_per_egg_cost_is_null_when_no_eggs_recorded(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 40.00,
        ]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertNull($result['per_egg']['cost_per_egg']);
        $this->assertNull($result['per_egg']['revenue_per_egg']);
        $this->assertNull($result['per_egg']['profit_per_egg']);
    }

    public function test_per_egg_cost_divides_expenses_by_egg_count(): void
    {
        $user = User::factory()->premium()->create();
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'count' => 100,
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 50.00,
        ]);
        Sale::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 80.00,
        ]);

        $result = $this->service->getFinancialAnalysis($user);

        $this->assertEquals(0.5, $result['per_egg']['cost_per_egg']);
        $this->assertEquals(0.8, $result['per_egg']['revenue_per_egg']);
        $this->assertEquals(0.3, $result['per_egg']['profit_per_egg']);
    }
}
