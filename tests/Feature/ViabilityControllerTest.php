<?php

namespace Tests\Feature;

use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViabilityControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_premium_user_can_view_viability_page(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertStatus(200);
        $response->assertViewIs('viability.index');
    }

    public function test_free_user_cannot_access_viability(): void
    {
        $user = User::factory()->create(['tier' => 'free']);

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertRedirect(route('app.dashboard'));
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/app/viability');

        $response->assertRedirect(route('login'));
    }

    public function test_costs_page_is_public_and_renders_the_calculator(): void
    {
        // The same calculator is available publicly at /costs (no auth required).
        $response = $this->get('/costs');

        $response->assertStatus(200);
        $response->assertViewIs('landing.costs');
        $response->assertSee('viabilityCalculator(', false);
    }

    public function test_calculator_shows_placeholder_when_no_inputs(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertStatus(200);
        $response->assertViewHas('results', null);
        $response->assertSee('Starting Investment');
    }

    public function test_calculator_returns_results_with_valid_inputs(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability?'.http_build_query([
            'birds' => 10,
            'laying_rate' => 0.7,
            'price_per_dozen' => 3.00,
            'sell_as' => 'dozen',
        ]));

        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertNotNull($results);
        $this->assertEquals(210, $results['monthly_eggs']);
    }

    public function test_htmx_request_with_inputs_returns_results_partial(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/viability?'.http_build_query([
                'birds' => 10,
                'laying_rate' => 0.7,
                'price_per_dozen' => 3.00,
                'sell_as' => 'dozen',
            ]));

        $response->assertStatus(200);
        $response->assertViewIs('viability.partials.results');
    }

    public function test_invalid_inputs_fall_back_to_defaults(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability?'.http_build_query([
            'birds' => 'notanumber',
            'laying_rate' => 0.7,
        ]));

        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertNotNull($results);
        $this->assertGreaterThanOrEqual(1, $results['inputs']['birds']);
    }

    public function test_viability_page_prefills_form_from_defaults(): void
    {
        $user = User::factory()->premium()->create();
        FlockBatch::factory()->active()->create([
            'user_id' => $user->id,
            'hens_count' => 30,
        ]);

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertStatus(200);
        $defaults = $response->viewData('defaults');
        $this->assertEquals(30, $defaults['birds']);
    }
}
