<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportService();
        $this->user = User::factory()->premium()->create();
    }

    public function test_report_with_single_sale_returns_correct_summary(): void
    {
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => now()->toDateString(),
            'total_amount' => 42.50,
        ]);

        $report = $this->service->getSalesReport($this->user);

        $this->assertEquals(1, $report['summary']['sale_count']);
        $this->assertEquals('42.50', $report['summary']['total_revenue']);
        $this->assertEquals('42.50', $report['summary']['average_sale']);
    }

    public function test_customer_breakdown_with_only_walk_in_sales(): void
    {
        Sale::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'customer_id' => null,
            'sale_date' => now()->toDateString(),
        ]);

        $report = $this->service->getSalesReport($this->user);

        $this->assertCount(1, $report['by_customer']);
        $this->assertEquals('Walk-in / No Customer', $report['by_customer']->first()['customer_name']);
    }

    public function test_weekly_totals_with_sales_all_in_same_week(): void
    {
        $monday = now()->startOfWeek();

        for ($i = 0; $i < 5; $i++) {
            Sale::factory()->create([
                'user_id' => $this->user->id,
                'sale_date' => $monday->copy()->addDays($i)->toDateString(),
            ]);
        }

        $from = $monday->copy();
        $to = $monday->copy()->endOfWeek();
        $report = $this->service->getSalesReport($this->user, $from, $to);

        $this->assertCount(1, $report['by_week']);
    }

    public function test_monthly_totals_not_shown_when_range_is_single_month(): void
    {
        $from = Carbon::parse('2026-04-01');
        $to = Carbon::parse('2026-04-30');

        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => '2026-04-10',
        ]);
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => '2026-04-20',
        ]);

        $report = $this->service->getSalesReport($this->user, $from, $to);

        $this->assertCount(1, $report['by_month']);
    }

    public function test_report_with_paid_and_unpaid_sales_splits_correctly(): void
    {
        Sale::factory()->paid()->count(3)->create([
            'user_id' => $this->user->id,
            'sale_date' => now()->toDateString(),
            'total_amount' => 10.00,
        ]);
        Sale::factory()->unpaid()->count(2)->create([
            'user_id' => $this->user->id,
            'sale_date' => now()->toDateString(),
            'total_amount' => 5.00,
        ]);

        $report = $this->service->getSalesReport($this->user);

        $paid = (float) str_replace(',', '', $report['summary']['paid_amount']);
        $unpaid = (float) str_replace(',', '', $report['summary']['unpaid_amount']);
        $total = (float) str_replace(',', '', $report['summary']['total_revenue']);

        $this->assertEquals($total, $paid + $unpaid);
    }

    public function test_customer_breakdown_revenue_sorted_descending(): void
    {
        $customerA = Customer::factory()->create(['user_id' => $this->user->id]);
        $customerB = Customer::factory()->create(['user_id' => $this->user->id]);

        Sale::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customerA->id,
            'sale_date' => now()->toDateString(),
            'total_amount' => 100.00,
        ]);
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customerB->id,
            'sale_date' => now()->toDateString(),
            'total_amount' => 200.00,
        ]);

        $report = $this->service->getSalesReport($this->user);

        $this->assertEquals($customerB->name, $report['by_customer']->first()['customer_name']);
    }

    public function test_date_range_includes_sales_on_from_date(): void
    {
        $from = Carbon::parse('2026-04-01');
        $to = Carbon::parse('2026-04-30');

        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => '2026-04-01',
            'total_amount' => 10.00,
        ]);
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => '2026-04-10',
            'total_amount' => 20.00,
        ]);

        $report = $this->service->getSalesReport($this->user, $from, $to);

        $this->assertEquals(2, $report['summary']['sale_count']);
        $this->assertEquals('30.00', $report['summary']['total_revenue']);
    }
}
