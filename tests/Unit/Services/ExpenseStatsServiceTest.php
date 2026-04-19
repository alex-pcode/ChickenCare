<?php

namespace Tests\Unit\Services;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use App\Services\ExpenseStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ExpenseStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ExpenseStatsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->premium()->create();
        $this->service = new ExpenseStatsService();
    }

    public function test_totals_by_category_sums_amounts_per_category(): void
    {
        Expense::factory()->for($this->user)->create([
            'category' => ExpenseCategory::Feed->value,
            'amount' => 10.00,
        ]);
        Expense::factory()->for($this->user)->create([
            'category' => ExpenseCategory::Feed->value,
            'amount' => 20.00,
        ]);
        Expense::factory()->for($this->user)->create([
            'category' => ExpenseCategory::Feed->value,
            'amount' => 30.00,
        ]);

        Expense::factory()->for($this->user)->create([
            'category' => ExpenseCategory::Birds->value,
            'amount' => 5.00,
        ]);
        Expense::factory()->for($this->user)->create([
            'category' => ExpenseCategory::Birds->value,
            'amount' => 15.00,
        ]);

        $totals = $this->service->for($this->user)->totalsByCategory();

        $this->assertEquals(60.0, $totals[ExpenseCategory::Feed->value]);
        $this->assertEquals(20.0, $totals[ExpenseCategory::Birds->value]);

        foreach (ExpenseCategory::cases() as $case) {
            $this->assertArrayHasKey($case->value, $totals);
        }
    }

    public function test_grand_total_sums_all_user_expenses(): void
    {
        Expense::factory()->for($this->user)->create(['amount' => 100.50]);
        Expense::factory()->for($this->user)->create(['amount' => 22.95]);

        $grandTotal = $this->service->for($this->user)->grandTotal();

        $this->assertEquals(123.45, $grandTotal);
    }

    public function test_transaction_count_by_category_counts_rows(): void
    {
        Expense::factory()->for($this->user)->create(['category' => ExpenseCategory::Feed->value]);
        Expense::factory()->for($this->user)->create(['category' => ExpenseCategory::Feed->value]);
        Expense::factory()->for($this->user)->create(['category' => ExpenseCategory::Feed->value]);

        Expense::factory()->for($this->user)->create(['category' => ExpenseCategory::Equipment->value]);

        $counts = $this->service->for($this->user)->transactionCountByCategory();

        $this->assertEquals(3, $counts[ExpenseCategory::Feed->value]);
        $this->assertEquals(1, $counts[ExpenseCategory::Equipment->value]);
        $this->assertEquals(0, $counts[ExpenseCategory::Other->value]);
    }

    public function test_category_breakdown_returns_all_eight_categories_sorted_by_total_desc(): void
    {
        Expense::factory()->for($this->user)->create([
            'category' => ExpenseCategory::Feed->value,
            'amount' => 100.00,
        ]);
        Expense::factory()->for($this->user)->create([
            'category' => ExpenseCategory::Birds->value,
            'amount' => 50.00,
        ]);

        $breakdown = $this->service->for($this->user)->categoryBreakdown();

        $this->assertCount(8, $breakdown);

        $this->assertEquals('Feed', $breakdown[0]['name']);
        $this->assertEquals(100.0, $breakdown[0]['total']);
        $this->assertEquals(1, $breakdown[0]['transactionCount']);

        $this->assertEquals('Birds', $breakdown[1]['name']);
        $this->assertEquals(50.0, $breakdown[1]['total']);

        foreach ($breakdown as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('total', $row);
            $this->assertArrayHasKey('transactionCount', $row);
            $this->assertArrayHasKey('percentage', $row);
            $this->assertArrayHasKey('color', $row);
        }
    }

    public function test_category_breakdown_calculates_percentage_to_one_decimal(): void
    {
        Expense::factory()->for($this->user)->create([
            'category' => ExpenseCategory::Feed->value,
            'amount' => 75.00,
        ]);
        Expense::factory()->for($this->user)->create([
            'category' => ExpenseCategory::Birds->value,
            'amount' => 25.00,
        ]);

        $breakdown = $this->service->for($this->user)->categoryBreakdown();
        $feedRow = collect($breakdown)->firstWhere('name', 'Feed');
        $birdsRow = collect($breakdown)->firstWhere('name', 'Birds');

        $this->assertEquals(75.0, $feedRow['percentage']);
        $this->assertEquals(25.0, $birdsRow['percentage']);
    }

    public function test_empty_state_returns_zeros_without_division_by_zero(): void
    {
        $stats = $this->service->for($this->user)->payload();

        $this->assertEquals(0.0, $stats['grandTotal']);

        foreach ($stats['breakdown'] as $row) {
            $this->assertEquals(0.0, $row['total']);
            $this->assertEquals(0, $row['transactionCount']);
            $this->assertEquals(0.0, $row['percentage']);
        }

        foreach ($stats['totalsByCategory'] as $total) {
            $this->assertEquals(0.0, $total);
        }

        foreach ($stats['transactionCountByCategory'] as $count) {
            $this->assertEquals(0, $count);
        }
    }

    public function test_service_is_scoped_to_user(): void
    {
        $otherUser = User::factory()->create();

        Expense::factory()->for($this->user)->create(['amount' => 500.00]);
        Expense::factory()->for($otherUser)->create(['amount' => 999.00]);

        $stats = $this->service->for($this->user)->payload();

        $this->assertEquals(500.0, $stats['grandTotal']);
    }

    public function test_colors_match_palette_constant(): void
    {
        Expense::factory()->for($this->user)->create(['category' => ExpenseCategory::Feed->value]);

        $breakdown = $this->service->for($this->user)->categoryBreakdown();
        $feedRow = collect($breakdown)->firstWhere('name', 'Feed');

        $this->assertEquals('#2A2580', $feedRow['color']);
    }
}
