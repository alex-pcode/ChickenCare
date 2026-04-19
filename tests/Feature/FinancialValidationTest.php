<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialValidationTest extends TestCase
{
    use RefreshDatabase;

    private function premiumUser(): User
    {
        return User::factory()->create(['tier' => 'premium']);
    }

    private function saleData(array $overrides = []): array
    {
        return array_merge([
            'sale_date' => now()->format('Y-m-d'),
            'dozen_count' => 2,
            'individual_count' => 0,
            'total_amount' => '10.00',
        ], $overrides);
    }

    private function expenseData(array $overrides = []): array
    {
        return array_merge([
            'date' => now()->format('Y-m-d'),
            'category' => 'Feed',
            'description' => 'Test',
            'amount' => '5.00',
        ], $overrides);
    }

    private function feedData(array $overrides = []): array
    {
        return array_merge([
            'brand' => 'Pellets',
            'feed_type' => 'Both',
            'quantity' => '10',
            'unit' => 'kg',
            'total_cost' => '25.00',
        ], $overrides);
    }

    // -----------------------------------------------------------------------
    // Sale amount
    // -----------------------------------------------------------------------

    public function test_sale_total_amount_rejects_negative_value(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/sales', $this->saleData(['total_amount' => '-0.01']))
            ->assertSessionHasErrors('total_amount');
    }

    public function test_sale_total_amount_accepts_zero(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/sales', $this->saleData(['total_amount' => '0.00']))
            ->assertRedirect(route('app.sales.index'));
    }

    public function test_sale_total_amount_accepts_two_decimal_places(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/sales', $this->saleData(['total_amount' => '12.50']))
            ->assertRedirect(route('app.sales.index'));
    }

    public function test_sale_total_amount_rejects_three_decimal_places(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/sales', $this->saleData(['total_amount' => '12.505']))
            ->assertSessionHasErrors('total_amount');
    }

    // -----------------------------------------------------------------------
    // Expense amount
    // -----------------------------------------------------------------------

    public function test_expense_amount_rejects_negative_value(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/expenses', $this->expenseData(['amount' => '-1.00']))
            ->assertSessionHasErrors('amount');
    }

    public function test_expense_amount_accepts_zero(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/expenses', $this->expenseData(['amount' => '0.00']))
            ->assertRedirect(route('app.expenses.index'));
    }

    // -----------------------------------------------------------------------
    // Feed quantity
    // -----------------------------------------------------------------------

    public function test_feed_quantity_rejects_negative_value(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/feed', $this->feedData(['quantity' => '-1']))
            ->assertSessionHasErrors('quantity');
    }

    public function test_feed_quantity_rejects_zero(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/feed', $this->feedData(['quantity' => '0']))
            ->assertSessionHasErrors('quantity');
    }

    // -----------------------------------------------------------------------
    // Sale egg counts
    // -----------------------------------------------------------------------

    public function test_sale_dozen_count_rejects_negative(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/sales', $this->saleData(['dozen_count' => '-1']))
            ->assertSessionHasErrors('dozen_count');
    }

    public function test_sale_individual_count_rejects_negative(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/sales', $this->saleData(['individual_count' => '-1']))
            ->assertSessionHasErrors('individual_count');
    }
}
