<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validSaleData(array $overrides = []): array
    {
        return array_merge([
            'sale_date' => now()->format('Y-m-d'),
            'dozen_count' => 2,
            'individual_count' => 0,
            'total_amount' => '10.00',
            'customer_id' => null,
            'paid' => false,
            'notes' => null,
        ], $overrides);
    }

    // -----------------------------------------------------------------------
    // CRUD — Task 20
    // -----------------------------------------------------------------------

    public function test_premium_user_can_view_sales_index(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);

        $this->actingAs($user)->get('/app/sales')
            ->assertStatus(200)
            ->assertViewIs('sales.index');
    }

    public function test_premium_user_can_store_sale(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);

        $this->actingAs($user)
            ->post('/app/sales', $this->validSaleData())
            ->assertRedirect(route('app.sales.index'));

        $this->assertDatabaseHas('sales', ['user_id' => $user->id, 'dozen_count' => 2]);
    }

    public function test_premium_user_can_store_sale_via_htmx(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post('/app/sales', $this->validSaleData());

        $response->assertStatus(200);
        $response->assertViewIs('sales.partials.entry-row');
    }

    public function test_premium_user_can_store_sale_without_customer(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);

        $this->actingAs($user)
            ->post('/app/sales', $this->validSaleData(['customer_id' => null]))
            ->assertRedirect(route('app.sales.index'));

        $this->assertDatabaseHas('sales', ['user_id' => $user->id, 'customer_id' => null]);
    }

    public function test_premium_user_can_store_sale_with_customer(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post('/app/sales', $this->validSaleData(['customer_id' => $customer->id]))
            ->assertRedirect(route('app.sales.index'));

        $this->assertDatabaseHas('sales', ['user_id' => $user->id, 'customer_id' => $customer->id]);
    }

    public function test_premium_user_can_update_sale(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->put("/app/sales/{$sale->id}", $this->validSaleData(['dozen_count' => 5]))
            ->assertRedirect(route('app.sales.index'));

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'dozen_count' => 5]);
    }

    public function test_premium_user_can_update_sale_via_htmx(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->put("/app/sales/{$sale->id}", $this->validSaleData(['dozen_count' => 5]));

        $response->assertStatus(200);
        $response->assertViewIs('sales.partials.entry-row');
    }

    public function test_premium_user_can_delete_sale(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete("/app/sales/{$sale->id}")
            ->assertRedirect(route('app.sales.index'));

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }

    public function test_premium_user_can_delete_sale_via_htmx(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete("/app/sales/{$sale->id}");

        $response->assertStatus(200);
        $this->assertEquals('', $response->getContent());
    }

    public function test_premium_user_sees_only_own_sales(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $other = User::factory()->create(['tier' => 'premium']);
        Sale::factory()->count(3)->create(['user_id' => $user->id]);
        Sale::factory()->count(2)->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)->get('/app/sales');

        $response->assertStatus(200);
        $sales = $response->viewData('sales');
        $this->assertCount(3, $sales);
    }

    public function test_premium_user_cannot_update_other_users_sale(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $other = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->put("/app/sales/{$sale->id}", $this->validSaleData())
            ->assertForbidden();
    }

    public function test_premium_user_cannot_delete_other_users_sale(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $other = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->delete("/app/sales/{$sale->id}")
            ->assertForbidden();
    }

    public function test_htmx_pagination_returns_table_partial(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        Sale::factory()->count(20)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/sales?page=2');

        $response->assertStatus(200);
        $response->assertViewIs('sales.partials.table');
    }

    public function test_index_eager_loads_customer(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        Sale::factory()->count(3)->create(['user_id' => $user->id, 'customer_id' => $customer->id]);

        \DB::enableQueryLog();
        $this->actingAs($user)->get('/app/sales');
        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        // With eager loading, customer queries should be fixed (≤ 2: one eager-load, one for dropdown)
        // rather than N queries for N sales (N+1 problem)
        $customerQueries = collect($queries)->filter(
            fn ($q) => str_contains($q['query'], 'customers')
        );
        $this->assertLessThanOrEqual(2, $customerQueries->count());
    }

    // -----------------------------------------------------------------------
    // Payment toggle — Task 21
    // -----------------------------------------------------------------------

    public function test_premium_user_can_toggle_payment_to_paid(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->unpaid()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patch("/app/sales/{$sale->id}/toggle-payment")
            ->assertStatus(200);

        $this->assertTrue((bool) $sale->fresh()->paid);
    }

    public function test_premium_user_can_toggle_payment_to_unpaid(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->paid()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patch("/app/sales/{$sale->id}/toggle-payment")
            ->assertStatus(200);

        $this->assertFalse((bool) $sale->fresh()->paid);
    }

    public function test_toggle_payment_returns_entry_row_partial(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->patch("/app/sales/{$sale->id}/toggle-payment");

        $response->assertStatus(200);
        $response->assertViewIs('sales.partials.entry-row');
    }

    public function test_toggle_payment_denied_for_other_users_sale(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $other = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->patch("/app/sales/{$sale->id}/toggle-payment")
            ->assertForbidden();
    }

    // -----------------------------------------------------------------------
    // Validation — Task 22
    // -----------------------------------------------------------------------

    public function test_store_validates_sale_date_required(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);

        $this->actingAs($user)
            ->post('/app/sales', $this->validSaleData(['sale_date' => '']))
            ->assertSessionHasErrors('sale_date');
    }

    public function test_store_validates_total_amount_required(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);

        $this->actingAs($user)
            ->post('/app/sales', $this->validSaleData(['total_amount' => '']))
            ->assertSessionHasErrors('total_amount');
    }

    public function test_store_validates_total_amount_non_negative(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);

        $this->actingAs($user)
            ->post('/app/sales', $this->validSaleData(['total_amount' => '-5']))
            ->assertSessionHasErrors('total_amount');
    }

    public function test_store_validates_customer_id_belongs_to_user(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $other = User::factory()->create(['tier' => 'premium']);
        $otherCustomer = Customer::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->post('/app/sales', $this->validSaleData(['customer_id' => $otherCustomer->id]))
            ->assertSessionHasErrors('customer_id');
    }

    public function test_store_accepts_null_customer_id(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);

        $this->actingAs($user)
            ->post('/app/sales', $this->validSaleData(['customer_id' => null]))
            ->assertRedirect(route('app.sales.index'));
    }

    // -----------------------------------------------------------------------
    // Tier enforcement — Task 23
    // -----------------------------------------------------------------------

    public function test_free_user_cannot_access_sales(): void
    {
        $user = User::factory()->create(['tier' => 'free']);

        $this->actingAs($user)
            ->get('/app/sales')
            ->assertRedirect(route('app.dashboard'));
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $this->get('/app/sales')
            ->assertRedirect(route('login'));
    }
}
