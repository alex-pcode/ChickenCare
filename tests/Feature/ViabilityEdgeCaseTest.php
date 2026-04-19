<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViabilityEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_viability_calculator_handles_zero_birds_input_without_error(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability?' . http_build_query([
            'birds' => 0,
            'laying_rate' => 0.7,
            'price_per_dozen' => 3.00,
            'sell_as' => 'dozen',
        ]));

        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertNotNull($results);
    }

    public function test_viability_shows_not_viable_notice_when_costs_exceed_revenue(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability?' . http_build_query([
            'birds' => 5,
            'laying_rate' => 0.3,
            'price_per_dozen' => 1.00,
            'sell_as' => 'dozen',
            'monthly_feed_cost' => 500.00,
        ]));

        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertNotNull($results);
        $this->assertLessThan(0, $results['monthly_profit']);
    }

    public function test_viability_results_partial_does_not_show_break_even_when_null(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability?' . http_build_query([
            'birds' => 10,
            'laying_rate' => 0.7,
            'price_per_dozen' => 3.00,
            'sell_as' => 'dozen',
            'cost_per_bird' => 0,
        ]));

        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertNotNull($results);
        $this->assertNull($results['break_even_months']);
    }

    public function test_viability_form_repopulates_from_results_inputs(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability?' . http_build_query([
            'birds' => 25,
            'laying_rate' => 0.7,
            'price_per_dozen' => 3.00,
            'sell_as' => 'dozen',
        ]));

        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertNotNull($results);
        $this->assertEquals(25, $results['inputs']['birds']);
    }
}
