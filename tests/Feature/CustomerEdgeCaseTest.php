<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private function premiumUser(): User
    {
        return User::factory()->create(['tier' => 'premium']);
    }

    public function test_customer_index_empty_state_for_new_user(): void
    {
        $user = $this->premiumUser();

        $response = $this->actingAs($user)->get('/app/customers');

        $response->assertStatus(200);
        $response->assertViewIs('customers.index');
        $customers = $response->viewData('customers');
        $this->assertTrue($customers->isEmpty());
    }

    public function test_customer_deactivation_preserves_record_in_database(): void
    {
        $user = $this->premiumUser();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete("/app/customers/{$customer->id}");

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_active' => false,
        ]);
    }

    public function test_customer_with_sales_retains_sale_fk_after_deactivation(): void
    {
        $user = $this->premiumUser();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $sale = Sale::factory()->create(['user_id' => $user->id, 'customer_id' => $customer->id]);

        // Deactivate — does NOT hard-delete the customer
        $this->actingAs($user)->delete("/app/customers/{$customer->id}");

        // Sale's FK should still point to the customer
        $this->assertEquals($customer->id, $sale->fresh()->customer_id);
    }

    public function test_customer_name_max_length_validation(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post('/app/customers', [
                'name' => str_repeat('a', 256),
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_customer_search_returns_empty_state_when_no_match(): void
    {
        $user = $this->premiumUser();
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'Alice']);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/customers?search=NonExistentName');

        $response->assertStatus(200);
        $response->assertViewIs('customers.partials.table');
        $customers = $response->viewData('customers');
        $this->assertTrue($customers->isEmpty());
    }
}
