<?php

namespace Tests\Unit;

use App\Models\EggEntry;
use App\Models\Expense;
use App\Models\User;
use App\Services\SavingsAnalysisService;
use App\Support\SavingsPeriod;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SavingsAnalysisCostTest extends TestCase
{
    use LazilyRefreshDatabase;

    private SavingsAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SavingsAnalysisService();
    }

    public function test_cost_per_egg_divides_expenses_by_eggs(): void
    {
        $user = User::factory()->premium()->withEggPrice(0.50)->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'count' => 100, 'date' => now()]);
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 30.00, 'date' => now()]);

        $result = $this->service->costAnalysis($user, SavingsPeriod::month(), 0.50);

        $this->assertEqualsWithDelta(0.30, $result['costPerEgg'], 0.001);
    }

    public function test_profit_per_egg_is_egg_price_minus_cost(): void
    {
        $user = User::factory()->premium()->withEggPrice(0.50)->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'count' => 100, 'date' => now()]);
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 30.00, 'date' => now()]);

        $result = $this->service->costAnalysis($user, SavingsPeriod::month(), 0.50);

        // 0.50 - (30/100) = 0.50 - 0.30 = 0.20
        $this->assertEqualsWithDelta(0.20, $result['profitPerEgg'], 0.001);
        $this->assertTrue($result['profitPositive']);
    }

    public function test_negative_profit_per_egg(): void
    {
        $user = User::factory()->premium()->withEggPrice(0.20)->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'count' => 100, 'date' => now()]);
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 50.00, 'date' => now()]);

        $result = $this->service->costAnalysis($user, SavingsPeriod::month(), 0.20);

        // 0.20 - (50/100) = 0.20 - 0.50 = -0.30
        $this->assertEqualsWithDelta(-0.30, $result['profitPerEgg'], 0.001);
        $this->assertFalse($result['profitPositive']);
    }

    public function test_eggs_to_break_even(): void
    {
        $user = User::factory()->premium()->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'count' => 50, 'date' => now()]);
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 100.00, 'date' => now()]);

        $result = $this->service->costAnalysis($user, SavingsPeriod::month(), 0.50);

        // ceil(100 / 0.50) = 200
        $this->assertEquals(200, $result['eggsToBreakEven']);
    }

    public function test_no_cost_data_when_zero_eggs(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 50.00, 'date' => now()]);

        $result = $this->service->costAnalysis($user, SavingsPeriod::month(), 0.50);

        $this->assertNull($result['costPerEgg']);
        $this->assertNull($result['profitPerEgg']);
        $this->assertFalse($result['hasCostData']);
    }

    public function test_no_break_even_when_zero_expenses(): void
    {
        $user = User::factory()->premium()->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'count' => 50, 'date' => now()]);

        $result = $this->service->costAnalysis($user, SavingsPeriod::month(), 0.50);

        $this->assertNull($result['eggsToBreakEven']);
        $this->assertFalse($result['hasBreakEvenData']);
    }

    public function test_no_break_even_when_zero_egg_price(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 50.00, 'date' => now()]);

        $result = $this->service->costAnalysis($user, SavingsPeriod::month(), 0.00);

        $this->assertNull($result['eggsToBreakEven']);
        $this->assertFalse($result['hasBreakEvenData']);
    }

    public function test_cost_analysis_respects_period_filter(): void
    {
        $user = User::factory()->premium()->create();
        // This month
        EggEntry::factory()->create(['user_id' => $user->id, 'count' => 50, 'date' => now()]);
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 25.00, 'date' => now()]);
        // Last year — should not be included in month filter
        EggEntry::factory()->create(['user_id' => $user->id, 'count' => 200, 'date' => now()->subYear()]);
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 100.00, 'date' => now()->subYear()]);

        $result = $this->service->costAnalysis($user, SavingsPeriod::month(), 0.50);

        // Only this month: 25/50 = 0.50
        $this->assertEqualsWithDelta(0.50, $result['costPerEgg'], 0.001);
    }

    public function test_all_null_for_empty_user(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->costAnalysis($user, SavingsPeriod::month(), 0.50);

        $this->assertNull($result['costPerEgg']);
        $this->assertNull($result['profitPerEgg']);
        $this->assertNull($result['eggsToBreakEven']);
        $this->assertFalse($result['hasCostData']);
        $this->assertFalse($result['hasBreakEvenData']);
    }
}
