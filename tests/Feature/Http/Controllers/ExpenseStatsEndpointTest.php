<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ExpenseStatsEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->premium()->create();
        $this->actingAs($this->user);
    }

    public function test_stats_endpoint_requires_authentication(): void
    {
        auth()->logout();

        $this->get('/app/expenses/stats')
            ->assertRedirect('/login');
    }

    public function test_stats_endpoint_returns_expected_shape_for_authenticated_user(): void
    {
        Expense::factory()->for($this->user)->create([
            'category' => ExpenseCategory::Feed->value,
            'amount' => 100.00,
        ]);
        Expense::factory()->for($this->user)->create([
            'category' => ExpenseCategory::Feed->value,
            'amount' => 50.00,
        ]);
        Expense::factory()->for($this->user)->create([
            'category' => ExpenseCategory::Birds->value,
            'amount' => 25.00,
        ]);

        $response = $this->getJson('/app/expenses/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'grandTotal',
            'totalsByCategory',
            'transactionCountByCategory',
            'breakdown',
        ]);

        $breakdown = $response->json('breakdown');
        $this->assertIsArray($breakdown);
        $this->assertCount(8, $breakdown);

        foreach ($breakdown as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('total', $row);
            $this->assertArrayHasKey('transactionCount', $row);
            $this->assertArrayHasKey('percentage', $row);
            $this->assertArrayHasKey('color', $row);
        }
    }

    public function test_stats_endpoint_returns_raw_numeric_totals(): void
    {
        Expense::factory()->for($this->user)->create(['amount' => 1234.50]);

        $response = $this->getJson('/app/expenses/stats');

        $this->assertEquals(1234.5, $response->json('grandTotal'));
        $this->assertIsNumeric($response->json('grandTotal'));
    }

    public function test_stats_endpoint_scopes_to_authenticated_user(): void
    {
        $otherUser = User::factory()->create();

        Expense::factory()->for($this->user)->create(['amount' => 500.00]);
        Expense::factory()->for($otherUser)->create(['amount' => 999.00]);

        $response = $this->getJson('/app/expenses/stats');

        $this->assertEquals(500.0, $response->json('grandTotal'));
    }

    public function test_stats_endpoint_returns_zeros_when_user_has_no_expenses(): void
    {
        $response = $this->getJson('/app/expenses/stats');

        $this->assertEquals(0, $response->json('grandTotal'));

        foreach ($response->json('breakdown') as $row) {
            $this->assertEquals(0.0, $row['total']);
            $this->assertEquals(0, $row['transactionCount']);
            $this->assertEquals(0.0, $row['percentage']);
        }
    }
}
