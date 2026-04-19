<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ViabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViabilityServiceEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private ViabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ViabilityService();
    }

    public function test_calculate_with_zero_birds_does_not_divide_by_zero(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, ['birds' => 0]);

        // birds is clamped to min 1
        $this->assertGreaterThanOrEqual(1, $result['inputs']['birds']);
        $this->assertNotNull($result['profit_per_bird']);
    }

    public function test_calculate_with_zero_monthly_eggs_does_not_divide_by_zero(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 10,
            'laying_rate' => 0.0,
        ]);

        $this->assertEquals(0, $result['monthly_eggs']);
        $this->assertNull($result['cost_per_egg']);
    }

    public function test_calculate_break_even_null_when_costs_exceed_revenue(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 10,
            'laying_rate' => 0.7,
            'price_per_dozen' => 1.00,
            'sell_as' => 'dozen',
            'monthly_feed_cost' => 200.00,
            'cost_per_bird' => 10.00,
        ]);

        $this->assertNull($result['break_even_months']);
    }

    public function test_calculate_break_even_null_when_no_acquisition_cost(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 10,
            'laying_rate' => 0.7,
            'price_per_dozen' => 5.00,
            'sell_as' => 'dozen',
            'cost_per_bird' => 0.00,
        ]);

        $this->assertNull($result['break_even_months']);
    }

    public function test_calculate_annual_roi_null_when_acquisition_cost_zero(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 10,
            'cost_per_bird' => 0.00,
        ]);

        $this->assertNull($result['annual_roi_pct']);
    }

    public function test_calculate_with_maximum_laying_rate(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 10,
            'laying_rate' => 1.0,
        ]);

        // 10 * 1.0 * 30 = 300
        $this->assertEquals(300, $result['monthly_eggs']);
    }

    public function test_calculate_individual_selling_revenue(): void
    {
        $user = User::factory()->premium()->create();

        $result = $this->service->calculate($user, [
            'birds' => 5,
            'laying_rate' => 0.5,
            'price_per_individual' => 0.50,
            'sell_as' => 'individual',
        ]);

        // 5 * 0.5 * 30 = 75 eggs * $0.50 = $37.50
        $this->assertEquals(75, $result['monthly_eggs']);
        $this->assertEquals(37.50, $result['monthly_revenue']);
    }

    public function test_calculate_dozen_selling_uses_floor_division(): void
    {
        $user = User::factory()->premium()->create();

        // Exact scenario: 25 eggs at $3/dozen = floor(25/12) * $3 = 2 * $3 = $6
        $result = $this->service->calculate($user, [
            'birds' => 1,
            'laying_rate' => round(25 / 30, 4), // ~0.8333 => round(1 * 0.8333 * 30) = 25
            'price_per_dozen' => 3.00,
            'sell_as' => 'dozen',
            'monthly_feed_cost' => 0,
            'other_monthly_costs' => 0,
        ]);

        $this->assertEquals(25, $result['monthly_eggs']);
        $this->assertEquals(6.00, $result['monthly_revenue']);
    }

    public function test_get_defaults_birds_falls_back_to_ten_when_no_active_flock(): void
    {
        $user = User::factory()->premium()->create();

        $defaults = $this->service->getDefaults($user);

        $this->assertEquals(10, $defaults['birds']);
    }
}
