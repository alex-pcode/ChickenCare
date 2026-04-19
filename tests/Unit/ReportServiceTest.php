<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReportService;
        $this->user = User::factory()->premium()->create();
    }

    public function test_get_sales_report_returns_expected_keys(): void
    {
        $report = $this->service->getSalesReport($this->user);

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('by_customer', $report);
        $this->assertArrayHasKey('by_week', $report);
        $this->assertArrayHasKey('by_month', $report);
        $this->assertArrayHasKey('from', $report);
        $this->assertArrayHasKey('to', $report);
    }

    public function test_summary_total_revenue_sums_sales_in_range(): void
    {
        $from = Carbon::parse('2026-04-01');
        $to = Carbon::parse('2026-04-30');

        Sale::factory()->create(['user_id' => $this->user->id, 'sale_date' => '2026-04-10', 'total_amount' => 25.00]);
        Sale::factory()->create(['user_id' => $this->user->id, 'sale_date' => '2026-04-15', 'total_amount' => 15.00]);
        Sale::factory()->create(['user_id' => $this->user->id, 'sale_date' => '2026-03-15', 'total_amount' => 100.00]);

        $report = $this->service->getSalesReport($this->user, $from, $to);

        $this->assertEquals('40.00', $report['summary']['total_revenue']);
    }

    public function test_summary_sale_count_is_correct(): void
    {
        Sale::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'sale_date' => now()->toDateString(),
        ]);

        $report = $this->service->getSalesReport($this->user);

        $this->assertEquals(3, $report['summary']['sale_count']);
    }

    public function test_summary_average_sale_rounds_to_two_decimals(): void
    {
        Sale::factory()->create(['user_id' => $this->user->id, 'sale_date' => now()->toDateString(), 'total_amount' => 10.00]);
        Sale::factory()->create(['user_id' => $this->user->id, 'sale_date' => now()->toDateString(), 'total_amount' => 20.00]);
        Sale::factory()->create(['user_id' => $this->user->id, 'sale_date' => now()->toDateString(), 'total_amount' => 30.00]);

        $report = $this->service->getSalesReport($this->user);

        $this->assertEquals('20.00', $report['summary']['average_sale']);
    }

    public function test_summary_unpaid_amount_only_includes_unpaid_sales(): void
    {
        Sale::factory()->paid()->create(['user_id' => $this->user->id, 'sale_date' => now()->toDateString(), 'total_amount' => 50.00]);
        Sale::factory()->unpaid()->create(['user_id' => $this->user->id, 'sale_date' => now()->toDateString(), 'total_amount' => 30.00]);

        $report = $this->service->getSalesReport($this->user);

        $this->assertEquals('30.00', $report['summary']['unpaid_amount']);
        $this->assertEquals('50.00', $report['summary']['paid_amount']);
    }

    public function test_customer_breakdown_groups_by_customer(): void
    {
        $customerA = Customer::factory()->create(['user_id' => $this->user->id]);
        $customerB = Customer::factory()->create(['user_id' => $this->user->id]);

        Sale::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customerA->id, 'sale_date' => now()->toDateString()]);
        Sale::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customerB->id, 'sale_date' => now()->toDateString()]);

        $report = $this->service->getSalesReport($this->user);

        $this->assertCount(2, $report['by_customer']);
    }

    public function test_customer_breakdown_null_customer_labelled_walk_in(): void
    {
        Sale::factory()->create(['user_id' => $this->user->id, 'customer_id' => null, 'sale_date' => now()->toDateString()]);

        $report = $this->service->getSalesReport($this->user);

        $this->assertEquals('Walk-in / No Customer', $report['by_customer']->first()['customer_name']);
    }

    public function test_customer_breakdown_sorted_by_revenue_descending(): void
    {
        $customerA = Customer::factory()->create(['user_id' => $this->user->id]);
        $customerB = Customer::factory()->create(['user_id' => $this->user->id]);

        Sale::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customerA->id, 'sale_date' => now()->toDateString(), 'total_amount' => 10.00]);
        Sale::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customerB->id, 'sale_date' => now()->toDateString(), 'total_amount' => 50.00]);

        $report = $this->service->getSalesReport($this->user);

        $this->assertEquals($customerB->name, $report['by_customer']->first()['customer_name']);
    }

    public function test_customer_breakdown_sorted_correctly_with_large_values(): void
    {
        $customerSmall = Customer::factory()->create(['user_id' => $this->user->id]);
        $customerLarge = Customer::factory()->create(['user_id' => $this->user->id]);

        Sale::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customerSmall->id, 'sale_date' => now()->toDateString(), 'total_amount' => 50.00]);
        Sale::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customerLarge->id, 'sale_date' => now()->toDateString(), 'total_amount' => 1500.00]);

        $report = $this->service->getSalesReport($this->user);

        $this->assertEquals($customerLarge->name, $report['by_customer']->first()['customer_name']);
        $this->assertEquals('1,500.00', $report['by_customer']->first()['total_revenue']);
    }

    public function test_weekly_totals_group_by_week(): void
    {
        $from = Carbon::parse('2026-04-01');
        $to = Carbon::parse('2026-04-30');

        Sale::factory()->create(['user_id' => $this->user->id, 'sale_date' => '2026-04-07']);
        Sale::factory()->create(['user_id' => $this->user->id, 'sale_date' => '2026-04-14']);

        $report = $this->service->getSalesReport($this->user, $from, $to);

        $this->assertCount(2, $report['by_week']);
    }

    public function test_monthly_totals_group_by_month(): void
    {
        $from = Carbon::parse('2026-03-01');
        $to = Carbon::parse('2026-04-30');

        Sale::factory()->create(['user_id' => $this->user->id, 'sale_date' => '2026-03-15']);
        Sale::factory()->create(['user_id' => $this->user->id, 'sale_date' => '2026-04-15']);

        $report = $this->service->getSalesReport($this->user, $from, $to);

        $this->assertCount(2, $report['by_month']);
    }

    public function test_weekly_totals_do_not_mutate_sale_dates(): void
    {
        $from = Carbon::parse('2026-03-01');
        $to = Carbon::parse('2026-04-30');

        // Sale on Apr 1 (Wed) — startOfWeek would mutate to Mar 30
        Sale::factory()->create(['user_id' => $this->user->id, 'sale_date' => '2026-04-01', 'total_amount' => 10.00]);
        Sale::factory()->create(['user_id' => $this->user->id, 'sale_date' => '2026-03-15', 'total_amount' => 20.00]);

        $report = $this->service->getSalesReport($this->user, $from, $to);

        // Monthly grouping must still correctly place the Apr 1 sale in April, not March
        $monthLabels = $report['by_month']->pluck('month_label')->toArray();
        $this->assertContains('April 2026', $monthLabels);
        $this->assertContains('March 2026', $monthLabels);
        $this->assertCount(2, $report['by_month']);
    }

    public function test_default_date_range_is_current_month(): void
    {
        $report = $this->service->getSalesReport($this->user);

        $this->assertEquals(now()->startOfMonth()->toDateString(), $report['from']->toDateString());
        $this->assertEquals(now()->endOfMonth()->toDateString(), $report['to']->toDateString());
    }

    public function test_empty_report_when_no_sales_in_range(): void
    {
        $report = $this->service->getSalesReport($this->user);

        $this->assertEquals(0, $report['summary']['sale_count']);
        $this->assertEquals('0.00', $report['summary']['total_revenue']);
        $this->assertCount(0, $report['by_customer']);
    }
}
