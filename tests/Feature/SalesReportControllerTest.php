<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_premium_user_can_view_sales_reports(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/sales/reports');

        $response->assertStatus(200);
        $response->assertViewIs('sales.reports');
    }

    public function test_free_user_cannot_access_sales_reports(): void
    {
        $user = User::factory()->create(['tier' => 'free']);

        $response = $this->actingAs($user)->get('/app/sales/reports');

        $response->assertRedirect(route('app.dashboard'));
        $response->assertSessionHas('warning');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/app/sales/reports');

        $response->assertRedirect(route('login'));
    }

    public function test_date_range_filter_scopes_results(): void
    {
        $user = User::factory()->premium()->create();

        Sale::factory()->create(['user_id' => $user->id, 'sale_date' => '2026-04-10', 'total_amount' => 25.00]);
        Sale::factory()->create(['user_id' => $user->id, 'sale_date' => '2026-03-10', 'total_amount' => 100.00]);

        $response = $this->actingAs($user)->get('/app/sales/reports?from=2026-04-01&to=2026-04-30');

        $response->assertStatus(200);
        $report = $response->viewData('report');
        $this->assertEquals('25.00', $report['summary']['total_revenue']);
        $this->assertEquals(1, $report['summary']['sale_count']);
    }

    public function test_htmx_request_returns_partial_view(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/sales/reports');

        $response->assertStatus(200);
        $response->assertViewIs('sales.partials.report-results');
    }

    public function test_htmx_request_does_not_include_full_layout(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/sales/reports');

        $response->assertStatus(200);
        $response->assertDontSee('<html');
    }

    public function test_invalid_date_params_fall_back_to_default_range(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/sales/reports?from=notadate&to=invalid');

        $response->assertStatus(200);
        $report = $response->viewData('report');
        $this->assertEquals(now()->startOfMonth()->toDateString(), $report['from']->toDateString());
    }

    public function test_report_shows_empty_state_when_no_sales(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/sales/reports');

        $response->assertStatus(200);
        $response->assertSee('No Sales Found');
    }

    public function test_report_data_matches_actual_sales_records(): void
    {
        $user = User::factory()->premium()->create();

        Sale::factory()->create(['user_id' => $user->id, 'sale_date' => now()->toDateString(), 'total_amount' => 10.00]);
        Sale::factory()->create(['user_id' => $user->id, 'sale_date' => now()->toDateString(), 'total_amount' => 20.00]);
        Sale::factory()->create(['user_id' => $user->id, 'sale_date' => now()->toDateString(), 'total_amount' => 30.00]);

        $response = $this->actingAs($user)->get('/app/sales/reports');

        $response->assertStatus(200);
        $report = $response->viewData('report');
        $this->assertEquals('60.00', $report['summary']['total_revenue']);
        $this->assertEquals(3, $report['summary']['sale_count']);
    }

    public function test_per_customer_breakdown_present_in_response(): void
    {
        $user = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id, 'name' => 'Jane Farmer']);

        Sale::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'total_amount' => 25.00,
        ]);

        $response = $this->actingAs($user)->get('/app/sales/reports');

        $response->assertStatus(200);
        $response->assertSee('Jane Farmer');
    }
}
