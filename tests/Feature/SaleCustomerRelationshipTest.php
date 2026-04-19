<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCustomerRelationshipTest extends TestCase
{
    use RefreshDatabase;

    private function premiumUser(): User
    {
        return User::factory()->create(['tier' => 'premium']);
    }

    private function validSaleData(array $overrides = []): array
    {
        return array_merge([
            'sale_date' => now()->format('Y-m-d'),
            'dozen_count' => 2,
            'individual_count' => 0,
            'total_amount' => '10.00',
            'customer_id' => null,
            'paid' => false,
        ], $overrides);
    }

    public function test_sale_customer_id_set_null_when_customer_hard_deleted(): void
    {
        $user = $this->premiumUser();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $sale = Sale::factory()->create(['user_id' => $user->id, 'customer_id' => $customer->id]);

        // Hard-delete bypasses the soft-deactivation (destroy on CustomerController)
        $customer->forceDelete();

        $this->assertNull($sale->fresh()->customer_id);
    }

    public function test_sale_displays_walk_in_label_when_customer_null(): void
    {
        $user = $this->premiumUser();
        Sale::factory()->create(['user_id' => $user->id, 'customer_id' => null]);

        $response = $this->actingAs($user)->get('/app/sales');

        $response->assertStatus(200);
        $response->assertSee('Walk-in / No Customer');
    }

    public function test_sale_displays_walk_in_label_when_customer_was_deleted(): void
    {
        $user = $this->premiumUser();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        Sale::factory()->create(['user_id' => $user->id, 'customer_id' => $customer->id]);

        $customer->forceDelete();

        $response = $this->actingAs($user)->get('/app/sales');

        $response->assertStatus(200);
        $response->assertSee('Walk-in / No Customer');
    }

    public function test_sale_can_be_created_with_valid_customer_id(): void
    {
        $user = $this->premiumUser();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post('/app/sales', $this->validSaleData(['customer_id' => $customer->id]))
            ->assertRedirect(route('app.sales.index'));

        $this->assertDatabaseHas('sales', [
            'user_id' => $user->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_sale_rejects_customer_id_from_another_user(): void
    {
        $user = $this->premiumUser();
        $other = $this->premiumUser();
        $otherCustomer = Customer::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->post('/app/sales', $this->validSaleData(['customer_id' => $otherCustomer->id]))
            ->assertSessionHasErrors('customer_id');
    }

    public function test_sale_can_be_updated_to_remove_customer_association(): void
    {
        $user = $this->premiumUser();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $sale = Sale::factory()->create(['user_id' => $user->id, 'customer_id' => $customer->id]);

        $this->actingAs($user)
            ->put("/app/sales/{$sale->id}", $this->validSaleData(['customer_id' => null]));

        $this->assertNull($sale->fresh()->customer_id);
    }

    public function test_sale_can_be_updated_to_add_customer_association(): void
    {
        $user = $this->premiumUser();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $sale = Sale::factory()->create(['user_id' => $user->id, 'customer_id' => null]);

        $this->actingAs($user)
            ->put("/app/sales/{$sale->id}", $this->validSaleData(['customer_id' => $customer->id]));

        $this->assertEquals($customer->id, $sale->fresh()->customer_id);
    }
}
