<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    // === Task 20: CRUD Operations ===

    public function test_premium_user_can_view_customers_index(): void
    {
        $user = User::factory()->premium()->create();
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'Alice']);

        $response = $this->actingAs($user)->get('/app/customers');

        $response->assertStatus(200);
        $response->assertViewIs('customers.index');
        $response->assertSee('Alice');
    }

    public function test_premium_user_can_store_customer(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/customers', [
            'name' => 'Bob Johnson',
            'phone' => '555-1234',
            'notes' => 'Regular buyer',
        ]);

        $response->assertRedirect(route('app.customers.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('customers', [
            'user_id' => $user->id,
            'name' => 'Bob Johnson',
            'phone' => '555-1234',
        ]);
    }

    public function test_premium_user_can_store_customer_via_htmx(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post('/app/customers', [
                'name' => 'Carol White',
                'phone' => '555-5678',
            ]);

        $response->assertStatus(200);
        $response->assertSee('Carol White');
        $response->assertSee('customer-');
    }

    public function test_premium_user_can_update_customer(): void
    {
        $user = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

        $response = $this->actingAs($user)->put("/app/customers/{$customer->id}", [
            'name' => 'New Name',
            'phone' => '555-9999',
            'notes' => 'Updated notes',
        ]);

        $response->assertRedirect(route('app.customers.index'));
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'New Name']);
    }

    public function test_premium_user_can_update_customer_via_htmx(): void
    {
        $user = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->put("/app/customers/{$customer->id}", [
                'name' => 'New Name',
                'phone' => null,
            ]);

        $response->assertStatus(200);
        $response->assertSee('New Name');
    }

    public function test_premium_user_can_deactivate_customer(): void
    {
        $user = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/app/customers/{$customer->id}");

        $response->assertRedirect(route('app.customers.index'));
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'is_active' => false]);
    }

    public function test_premium_user_can_deactivate_customer_via_htmx(): void
    {
        $user = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete("/app/customers/{$customer->id}");

        $response->assertStatus(200);
        $this->assertEmpty($response->getContent());
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'is_active' => false]);
    }

    public function test_premium_user_sees_only_own_customers(): void
    {
        $user1 = User::factory()->premium()->create();
        $user2 = User::factory()->premium()->create();
        Customer::factory()->create(['user_id' => $user1->id, 'name' => 'My Customer']);
        Customer::factory()->create(['user_id' => $user2->id, 'name' => 'Not My Customer']);

        $response = $this->actingAs($user1)->get('/app/customers');

        $response->assertSee('My Customer');
        $response->assertDontSee('Not My Customer');
    }

    public function test_premium_user_cannot_update_other_users_customer(): void
    {
        $user = User::factory()->premium()->create();
        $other = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)->put("/app/customers/{$customer->id}", [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(403);
    }

    public function test_premium_user_cannot_delete_other_users_customer(): void
    {
        $user = User::factory()->premium()->create();
        $other = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)->delete("/app/customers/{$customer->id}");

        $response->assertStatus(403);
    }

    // === Task 21: Search and Filtering ===

    public function test_index_filters_active_customers_by_default(): void
    {
        $user = User::factory()->premium()->create();
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'Active One', 'is_active' => true]);
        Customer::factory()->inactive()->create(['user_id' => $user->id, 'name' => 'Inactive One']);

        $response = $this->actingAs($user)->get('/app/customers');

        $response->assertSee('Active One');
        $response->assertDontSee('Inactive One');
    }

    public function test_index_shows_inactive_customers_with_status_filter(): void
    {
        $user = User::factory()->premium()->create();
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'Active One', 'is_active' => true]);
        Customer::factory()->inactive()->create(['user_id' => $user->id, 'name' => 'Inactive One']);

        $response = $this->actingAs($user)->get('/app/customers?status=inactive');

        $response->assertDontSee('Active One');
        $response->assertSee('Inactive One');
    }

    public function test_index_shows_all_customers_with_status_all(): void
    {
        $user = User::factory()->premium()->create();
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'Active One', 'is_active' => true]);
        Customer::factory()->inactive()->create(['user_id' => $user->id, 'name' => 'Inactive One']);

        $response = $this->actingAs($user)->get('/app/customers?status=all');

        $response->assertSee('Active One');
        $response->assertSee('Inactive One');
    }

    public function test_index_searches_customers_by_name(): void
    {
        $user = User::factory()->premium()->create();
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'John Smith']);
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'Jane Doe']);

        $response = $this->actingAs($user)->get('/app/customers?search=John');

        $response->assertSee('John Smith');
        $response->assertDontSee('Jane Doe');
    }

    public function test_index_search_is_case_insensitive(): void
    {
        $user = User::factory()->premium()->create();
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'John Smith']);

        $response = $this->actingAs($user)->get('/app/customers?search=john');

        $response->assertSee('John Smith');
    }

    public function test_htmx_search_returns_table_partial(): void
    {
        $user = User::factory()->premium()->create();
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'John Smith']);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/customers?search=John');

        $response->assertStatus(200);
        $response->assertSee('John Smith');
        $response->assertDontSee('<x-layout.page-header');
    }

    public function test_index_shows_empty_state_when_no_customers_match(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/customers');

        $response->assertSee('No customers found');
    }

    // === Task 22: Validation ===

    public function test_store_validates_name_required(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/customers', [
            'name' => '',
            'phone' => '555-1234',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_validates_name_max_length(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/customers', [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_validates_phone_max_length(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/customers', [
            'name' => 'Valid Name',
            'phone' => str_repeat('1', 51),
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_store_accepts_valid_data_with_nullable_fields(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post('/app/customers', [
            'name' => 'Valid Name',
        ]);

        $response->assertRedirect(route('app.customers.index'));
        $this->assertDatabaseHas('customers', [
            'user_id' => $user->id,
            'name' => 'Valid Name',
            'phone' => null,
            'notes' => null,
        ]);
    }

    // === Task 23: Premium Tier Enforcement ===

    public function test_free_user_cannot_access_customers(): void
    {
        $user = User::factory()->create(['tier' => 'free']);

        $response = $this->actingAs($user)->get('/app/customers');

        $response->assertRedirect(route('app.dashboard'));
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/app/customers');

        $response->assertRedirect(route('login'));
    }

    // === Task 24: Edit Form and Soft Deactivation Integrity ===

    public function test_premium_user_can_get_edit_form_via_htmx(): void
    {
        $user = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id, 'name' => 'Edit Me']);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get("/app/customers/{$customer->id}/edit-form");

        $response->assertStatus(200);
        $response->assertSee('Edit Me');
        $response->assertSee('Save');
        $response->assertSee('Cancel');
    }

    public function test_premium_user_cannot_get_edit_form_for_other_users_customer(): void
    {
        $user = User::factory()->premium()->create();
        $other = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)->get("/app/customers/{$customer->id}/edit-form");

        $response->assertStatus(403);
    }

    public function test_deactivate_does_not_hard_delete_customer(): void
    {
        $user = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete("/app/customers/{$customer->id}");

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $this->assertFalse($customer->fresh()->is_active);
    }

    public function test_deactivated_customer_hidden_from_default_index(): void
    {
        $user = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id, 'name' => 'Soon Inactive']);

        $this->actingAs($user)->delete("/app/customers/{$customer->id}");

        $response = $this->actingAs($user)->get('/app/customers');

        $response->assertDontSee('Soon Inactive');
    }
}
