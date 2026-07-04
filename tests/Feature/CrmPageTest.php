<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CrmPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    // ── Story 1: Shell & Tab Navigation ──

    public function test_premium_user_can_view_crm_page(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/crm');

        $response->assertStatus(200);
        $response->assertViewIs('crm.index');
        $response->assertSee('CRM System');
        $response->assertSee('data-loading-skeleton="crm-tab"', false);
    }

    public function test_default_tab_is_quick_sale(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/crm');

        $response->assertStatus(200);
        $response->assertSee('Quick Sale ⚡');
    }

    public function test_query_string_selects_customers_tab(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/crm?tab=customers');

        $response->assertStatus(200);
        $response->assertSee('Customers');
    }

    public function test_query_string_selects_reports_tab(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/crm?tab=reports');

        $response->assertStatus(200);
        $response->assertSee('Overview');
    }

    public function test_reports_tab_ignores_invalid_date_params(): void
    {
        $user = User::factory()->premium()->create();

        $this->actingAs($user)
            ->get('/app/crm?tab=reports&period=custom&from=garbage&to=2026-13-45')
            ->assertStatus(200);

        $this->actingAs($user)
            ->get('/app/crm?tab=reports&period=custom&from[]=2026-01-01')
            ->assertStatus(200);
    }

    public function test_htmx_request_returns_partial_only(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true', 'HX-Target' => 'crm-tab-content'])
            ->get('/app/crm?tab=quick-sale');

        $response->assertStatus(200);
        $response->assertSee('Quick Sale');
        $response->assertDontSee('<!DOCTYPE html>');
    }

    public function test_invalid_tab_defaults_to_quick_sale(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/crm?tab=invalid');

        $response->assertStatus(200);
        $response->assertSee('Quick Sale ⚡');
    }

    public function test_free_user_cannot_access_crm(): void
    {
        $user = User::factory()->create(['tier' => 'free']);

        $response = $this->actingAs($user)->get('/app/crm');

        $response->assertRedirect();
    }

    // ── Story 2: Quick Sale ──

    public function test_quick_sale_tab_shows_customer_select(): void
    {
        $user = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id, 'name' => 'Alice Farmer']);

        $response = $this->actingAs($user)->get('/app/crm?tab=quick-sale');

        $response->assertStatus(200);
        $response->assertSee('Alice Farmer');
        $response->assertSee('Record Sale');
    }

    public function test_sale_can_be_stored_from_crm(): void
    {
        $user = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post('/app/sales', [
                'customer_id' => $customer->id,
                'sale_date' => today()->format('Y-m-d'),
                'dozen_count' => 2,
                'individual_count' => 6,
                'total_amount' => 9.00,
                'notes' => 'CRM test sale',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('sales', [
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'dozen_count' => 2,
            'individual_count' => 6,
        ]);
    }

    public function test_sale_validation_errors(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)
            ->from('/app/crm?tab=quick-sale')
            ->post('/app/sales', [
                'sale_date' => today()->format('Y-m-d'),
                'dozen_count' => 0,
                'individual_count' => 0,
                'total_amount' => 5.00,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    // ── Story 3: Customers Tab ──

    public function test_customers_tab_shows_customer_list(): void
    {
        $user = User::factory()->premium()->create();
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'Bob Builder']);

        $response = $this->actingAs($user)->get('/app/crm?tab=customers');

        $response->assertStatus(200);
        $response->assertSee('Bob Builder');
    }

    public function test_customers_tab_sortable_by_name(): void
    {
        $user = User::factory()->premium()->create();
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'Zelda']);
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'Alice']);

        $response = $this->actingAs($user)->get('/app/crm?tab=customers&sort=name&dir=asc');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Alice', 'Zelda']);
    }

    public function test_customers_tab_empty_state(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/crm?tab=customers');

        $response->assertStatus(200);
        $response->assertSee('No Customers Yet');
    }

    // ── Story 4: Reports Overview ──

    public function test_reports_tab_shows_overview_by_default(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/crm?tab=reports');

        $response->assertStatus(200);
        $response->assertSee('Overview');
    }

    public function test_reports_overview_shows_revenue_with_sales_data(): void
    {
        $user = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        Sale::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'sale_date' => today(),
            'total_amount' => 25.50,
            'dozen_count' => 2,
            'individual_count' => 0,
        ]);

        $response = $this->actingAs($user)->get('/app/crm?tab=reports&view=overview&period=month');

        $response->assertStatus(200);
        $response->assertSee('Revenue Overview');
    }

    public function test_reports_overview_empty_state(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/crm?tab=reports');

        $response->assertStatus(200);
        $response->assertSee('No Data Yet');
    }

    public function test_reports_period_filter_all_time(): void
    {
        $user = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        Sale::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'sale_date' => today()->subMonths(6),
            'total_amount' => 15.00,
            'dozen_count' => 1,
            'individual_count' => 3,
        ]);

        $response = $this->actingAs($user)->get('/app/crm?tab=reports&view=overview&period=all');

        $response->assertStatus(200);
        $response->assertSee('$15.00');
    }

    // ── Story 5: Per-Customer View ──

    public function test_per_customer_view_shows_customer_selector(): void
    {
        $user = User::factory()->premium()->create();
        Customer::factory()->create(['user_id' => $user->id, 'name' => 'Carol Hen']);

        $response = $this->actingAs($user)->get('/app/crm?tab=reports&view=customer');

        $response->assertStatus(200);
        $response->assertSee('Carol Hen');
        $response->assertSee('Select a Customer');
    }

    public function test_per_customer_view_shows_customer_report(): void
    {
        $user = User::factory()->premium()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id, 'name' => 'Dave Egg']);
        Sale::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'sale_date' => today(),
            'total_amount' => 12.00,
            'dozen_count' => 1,
            'individual_count' => 0,
        ]);

        $response = $this->actingAs($user)->get("/app/crm?tab=reports&view=customer&customer_id={$customer->id}");

        $response->assertStatus(200);
        $response->assertSee('Dave Egg');
        $response->assertSee('$12.00');
    }

    public function test_per_customer_view_empty_selection(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/crm?tab=reports&view=customer');

        $response->assertStatus(200);
        $response->assertSee('Select a Customer');
    }
}
