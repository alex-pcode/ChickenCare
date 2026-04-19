<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\FlockBatch;
use App\Models\User;
use App\Services\ViabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private ViabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ViabilityService();
    }

    public function test_calculate_returns_expected_keys(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, ['birds' => 10]);

        $this->assertArrayHasKey('monthly_eggs', $result);
        $this->assertArrayHasKey('monthly_revenue', $result);
        $this->assertArrayHasKey('monthly_costs', $result);
        $this->assertArrayHasKey('monthly_profit', $result);
        $this->assertArrayHasKey('cost_per_egg', $result);
        $this->assertArrayHasKey('profit_per_bird', $result);
        $this->assertArrayHasKey('break_even_months', $result);
        $this->assertArrayHasKey('annual_roi_pct', $result);
        $this->assertArrayHasKey('inputs', $result);
    }

    public function test_monthly_eggs_calculated_from_birds_and_laying_rate(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 10,
            'laying_rate' => 0.7,
        ]);

        // 10 birds * 0.7 * 30 = 210
        $this->assertEquals(210, $result['monthly_eggs']);
    }

    public function test_monthly_revenue_calculated_for_dozen_selling(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 10,
            'laying_rate' => 0.7,
            'price_per_dozen' => 3.00,
            'sell_as' => 'dozen',
        ]);

        // 210 eggs / 12 = 17 dozen (floor) * $3 = $51
        $this->assertEquals(51.00, $result['monthly_revenue']);
    }

    public function test_monthly_revenue_calculated_for_individual_selling(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 10,
            'laying_rate' => 0.7,
            'price_per_individual' => 0.30,
            'sell_as' => 'individual',
        ]);

        // 210 eggs * $0.30 = $63
        $this->assertEquals(63.00, $result['monthly_revenue']);
    }

    public function test_monthly_profit_is_revenue_minus_costs(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 10,
            'laying_rate' => 0.7,
            'price_per_dozen' => 3.00,
            'sell_as' => 'dozen',
            'monthly_feed_cost' => 20.00,
            'other_monthly_costs' => 5.00,
        ]);

        // Revenue $51 - Costs $25 = $26
        $this->assertEquals(26.00, $result['monthly_profit']);
    }

    public function test_cost_per_egg_divides_monthly_costs_by_monthly_eggs(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 10,
            'laying_rate' => 0.7,
            'monthly_feed_cost' => 21.00,
            'other_monthly_costs' => 0.00,
        ]);

        // $21 / 210 eggs = $0.10
        $this->assertEquals(0.10, $result['cost_per_egg']);
    }

    public function test_profit_per_bird_divides_profit_by_bird_count(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 10,
            'laying_rate' => 0.7,
            'price_per_dozen' => 3.00,
            'sell_as' => 'dozen',
            'monthly_feed_cost' => 21.00,
            'other_monthly_costs' => 0.00,
        ]);

        // Profit $51 - $21 = $30, $30 / 10 birds = $3.00
        $this->assertEquals(3.00, $result['profit_per_bird']);
    }

    public function test_break_even_is_null_when_monthly_profit_is_zero_or_negative(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 10,
            'laying_rate' => 0.7,
            'price_per_dozen' => 1.00,
            'sell_as' => 'dozen',
            'monthly_feed_cost' => 100.00,
            'cost_per_bird' => 10.00,
        ]);

        $this->assertNull($result['break_even_months']);
    }

    public function test_break_even_months_calculated_from_acquisition_cost_and_monthly_profit(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 10,
            'laying_rate' => 0.7,
            'price_per_dozen' => 3.00,
            'sell_as' => 'dozen',
            'monthly_feed_cost' => 21.00,
            'other_monthly_costs' => 0.00,
            'cost_per_bird' => 10.00,
        ]);

        // Acquisition: $10 * 10 = $100, Profit: $30/mo, ceil(100/30) = 4
        $this->assertEquals(4, $result['break_even_months']);
    }

    public function test_annual_roi_is_null_when_acquisition_cost_is_zero(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 10,
            'cost_per_bird' => 0.00,
        ]);

        $this->assertNull($result['annual_roi_pct']);
    }

    public function test_inputs_are_echoed_back_in_results(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 15,
            'laying_rate' => 0.8,
            'sell_as' => 'individual',
        ]);

        $this->assertEquals(15, $result['inputs']['birds']);
        $this->assertEquals(0.8, $result['inputs']['laying_rate']);
        $this->assertEquals('individual', $result['inputs']['sell_as']);
    }

    public function test_get_defaults_returns_expected_keys(): void
    {
        $user = User::factory()->premium()->create();

        $defaults = $this->service->getDefaults($user);

        $this->assertArrayHasKey('birds', $defaults);
        $this->assertArrayHasKey('laying_rate', $defaults);
        $this->assertArrayHasKey('price_per_dozen', $defaults);
        $this->assertArrayHasKey('price_per_individual', $defaults);
        $this->assertArrayHasKey('monthly_feed_cost', $defaults);
        $this->assertArrayHasKey('other_monthly_costs', $defaults);
        $this->assertArrayHasKey('cost_per_bird', $defaults);
        $this->assertArrayHasKey('sell_as', $defaults);
    }

    public function test_get_defaults_prefills_birds_from_active_flock(): void
    {
        $user = User::factory()->premium()->create();
        FlockBatch::factory()->active()->create([
            'user_id' => $user->id,
            'hens_count' => 25,
        ]);

        $defaults = $this->service->getDefaults($user);

        $this->assertEquals(25, $defaults['birds']);
    }
}
