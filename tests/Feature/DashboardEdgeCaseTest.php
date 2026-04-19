<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_without_errors_for_brand_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');
    }

    public function test_free_user_dashboard_does_not_display_financial_section(): void
    {
        $user = User::factory()->create(['tier' => 'free']);

        $response = $this->actingAs($user)->get(route('app.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Financial Overview');
        $response->assertSee('Premium Feature');
    }

    public function test_dashboard_recent_activity_empty_state_shown_for_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertSee('No Recent Activity');
    }

    public function test_dashboard_chart_data_contains_thirty_labels(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $eggChartData = $response->viewData('eggChartData');
        $this->assertCount(30, $eggChartData['labels']);
    }
}
