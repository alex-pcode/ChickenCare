<?php

namespace Tests\Feature;

use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViabilityReplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_viability_page_loads_successfully(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertStatus(200);
    }

    public function test_viability_page_contains_hero_image(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('/images/cute-chickens-discussing.webp', false);
    }

    public function test_viability_page_contains_starting_investment_heading(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('Starting Investment');
    }

    public function test_viability_page_contains_acquisition_method_heading(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('Acquisition Method');
    }

    public function test_viability_page_contains_alpine_component(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('x-data="viabilityCalculator(', false);
    }

    public function test_viability_page_contains_info_box(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee("Don't forget:", false);
    }

    public function test_viability_page_uses_new_defaults(): void
    {
        $user = User::factory()->premium()->create();
        FlockBatch::factory()->active()->create([
            'user_id' => $user->id,
            'hens_count' => 15,
        ]);

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertStatus(200);
        $newDefaults = $response->viewData('newDefaults');
        $this->assertEquals(15, $newDefaults['birdCount']);
    }

    public function test_viability_page_contains_setup_parameters_heading(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('Setup Parameters');
    }

    public function test_viability_page_contains_feeding_approach_heading(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('Feeding Approach');
    }

    public function test_viability_page_contains_production_heading(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('Egg Production Scenario');
    }

    public function test_viability_page_contains_bird_count_input(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('Number of Chickens');
        $response->assertSee('id="bird-count"', false);
    }

    public function test_viability_page_contains_egg_price_input(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('Price per Egg ($)', false);
        $response->assertSee('id="egg-price"', false);
    }

    public function test_viability_page_contains_financial_analysis_heading(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('Financial Analysis');
    }

    public function test_viability_page_contains_four_stat_card_titles(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('Monthly Egg Production');
        $response->assertSee('Monthly Egg Value');
        $response->assertSee('Monthly Feed Cost');
        $response->assertSee('Monthly Profit');
    }

    public function test_viability_page_contains_annual_summary(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('Annual Summary');
    }

    public function test_viability_page_contains_payback_analysis(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('Payback Analysis');
    }

    public function test_viability_page_contains_viability_assessment(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('Viability Assessment');
        $response->assertSee('Break-Even Analysis');
    }

    public function test_viability_page_contains_baby_chick_timeline_markup(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('Baby Chick Timeline Impact');
    }

    public function test_viability_page_contains_assessment_items(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/viability');

        $response->assertSee('Your Assessment');
        $response->assertSee('Recommendations');
    }
}
