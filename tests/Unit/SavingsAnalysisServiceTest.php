<?php

namespace Tests\Unit;

use App\Enums\ChickenGoal;
use App\Models\EggEntry;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\User;
use App\Services\SavingsAnalysisService;
use App\Support\SavingsPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SavingsAnalysisServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private SavingsAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SavingsAnalysisService;
    }

    public function test_financial_summary_returns_expected_keys(): void
    {
        $user = User::factory()->premium()->hobby()->create();

        $result = $this->service->financialSummary($user, SavingsPeriod::month());

        $this->assertArrayHasKey('totalEggs', $result);
        $this->assertArrayHasKey('eggValue', $result);
        $this->assertArrayHasKey('actualRevenue', $result);
        $this->assertArrayHasKey('totalExpenses', $result);
        $this->assertArrayHasKey('netResult', $result);
        $this->assertArrayHasKey('isBusinessGoal', $result);
        $this->assertArrayHasKey('eggPrice', $result);
    }

    public function test_financial_summary_month_filter(): void
    {
        Carbon::setTestNow('2026-04-15');
        $user = User::factory()->premium()->hobby()->withEggPrice(0.50)->create();

        // This month egg
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-10',
            'count' => 10,
        ]);
        // Last month egg (should be excluded)
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-03-10',
            'count' => 20,
        ]);

        $result = $this->service->financialSummary($user, SavingsPeriod::month());

        $this->assertSame(10, $result['totalEggs']);
        $this->assertEqualsWithDelta(5.0, $result['eggValue'], 0.01);

        Carbon::setTestNow();
    }

    public function test_financial_summary_year_filter(): void
    {
        Carbon::setTestNow('2026-04-15');
        $user = User::factory()->premium()->hobby()->withEggPrice(0.30)->create();

        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-01',
            'count' => 15,
        ]);
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-01',
            'count' => 10,
        ]);
        // Last year (excluded)
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2025-12-01',
            'count' => 50,
        ]);

        $result = $this->service->financialSummary($user, SavingsPeriod::year());

        $this->assertSame(25, $result['totalEggs']);

        Carbon::setTestNow();
    }

    public function test_financial_summary_custom_filter(): void
    {
        $user = User::factory()->premium()->hobby()->withEggPrice(0.30)->create();

        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-15',
            'count' => 12,
        ]);
        // Outside custom range
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-01',
            'count' => 30,
        ]);

        $result = $this->service->financialSummary(
            $user,
            SavingsPeriod::custom('2026-02-01', '2026-03-31'),
        );

        $this->assertSame(12, $result['totalEggs']);
    }

    public function test_financial_summary_all_time(): void
    {
        $user = User::factory()->premium()->hobby()->withEggPrice(0.30)->create();

        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2025-01-01',
            'count' => 10,
        ]);
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-01',
            'count' => 20,
        ]);

        $result = $this->service->financialSummary($user, SavingsPeriod::all());

        $this->assertSame(30, $result['totalEggs']);
    }

    public function test_financial_summary_hobby_net_result(): void
    {
        Carbon::setTestNow('2026-04-15');
        $user = User::factory()->premium()->hobby()->withEggPrice(0.50)->create();

        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-10',
            'count' => 100,
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-05',
            'amount' => 20.00,
        ]);

        $result = $this->service->financialSummary($user, SavingsPeriod::month());

        // Hobby: eggValue - expenses = (100 * 0.50) - 20 = 30
        $this->assertFalse($result['isBusinessGoal']);
        $this->assertEqualsWithDelta(50.0, $result['eggValue'], 0.01);
        $this->assertEqualsWithDelta(20.0, $result['totalExpenses'], 0.01);
        $this->assertEqualsWithDelta(30.0, $result['netResult'], 0.01);

        Carbon::setTestNow();
    }

    public function test_financial_summary_business_net_result(): void
    {
        Carbon::setTestNow('2026-04-15');
        $user = User::factory()->premium()->business()->withEggPrice(0.50)->create();

        Sale::factory()->create([
            'user_id' => $user->id,
            'sale_date' => '2026-04-10',
            'total_amount' => 75.00,
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-05',
            'amount' => 25.00,
        ]);

        $result = $this->service->financialSummary($user, SavingsPeriod::month());

        // Business: actualRevenue - expenses = 75 - 25 = 50
        $this->assertTrue($result['isBusinessGoal']);
        $this->assertEqualsWithDelta(75.0, $result['actualRevenue'], 0.01);
        $this->assertEqualsWithDelta(25.0, $result['totalExpenses'], 0.01);
        $this->assertEqualsWithDelta(50.0, $result['netResult'], 0.01);

        Carbon::setTestNow();
    }

    public function test_financial_summary_empty_state(): void
    {
        $user = User::factory()->premium()->hobby()->create();

        $result = $this->service->financialSummary($user, SavingsPeriod::month());

        $this->assertSame(0, $result['totalEggs']);
        $this->assertEqualsWithDelta(0.0, $result['eggValue'], 0.01);
        $this->assertEqualsWithDelta(0.0, $result['actualRevenue'], 0.01);
        $this->assertEqualsWithDelta(0.0, $result['totalExpenses'], 0.01);
        $this->assertEqualsWithDelta(0.0, $result['netResult'], 0.01);
    }
}
