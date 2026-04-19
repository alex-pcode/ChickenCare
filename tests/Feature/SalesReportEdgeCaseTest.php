<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReportEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_renders_empty_state_when_user_has_no_sales(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/sales/reports');

        $response->assertStatus(200);
        $response->assertSee('No Sales Found');
    }

    public function test_report_with_single_sale_shows_correct_totals(): void
    {
        $user = User::factory()->premium()->create();
        Sale::factory()->create([
            'user_id' => $user->id,
            'sale_date' => now()->toDateString(),
            'total_amount' => 10.00,
        ]);

        $response = $this->actingAs($user)->get('/app/sales/reports');

        $response->assertStatus(200);
        $report = $response->viewData('report');
        $this->assertEquals('10.00', $report['summary']['total_revenue']);
    }

    public function test_report_with_walk_in_sale_shows_walk_in_label(): void
    {
        $user = User::factory()->premium()->create();
        Sale::factory()->create([
            'user_id' => $user->id,
            'customer_id' => null,
            'sale_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/app/sales/reports');

        $response->assertStatus(200);
        $response->assertSee('Walk-in');
    }

    public function test_report_date_range_spanning_multiple_months_shows_monthly_table(): void
    {
        $user = User::factory()->premium()->create();
        Sale::factory()->create([
            'user_id' => $user->id,
            'sale_date' => '2026-01-15',
        ]);
        Sale::factory()->create([
            'user_id' => $user->id,
            'sale_date' => '2026-03-15',
        ]);

        $response = $this->actingAs($user)->get('/app/sales/reports?from=2026-01-01&to=2026-03-31');

        $response->assertStatus(200);
        $response->assertSee('Monthly Breakdown');
    }

    public function test_report_date_range_within_single_month_hides_monthly_table(): void
    {
        $user = User::factory()->premium()->create();
        Sale::factory()->create([
            'user_id' => $user->id,
            'sale_date' => '2026-04-10',
        ]);

        $response = $this->actingAs($user)->get('/app/sales/reports?from=2026-04-01&to=2026-04-30');

        $response->assertStatus(200);
        $response->assertDontSee('Monthly Breakdown');
    }
}
