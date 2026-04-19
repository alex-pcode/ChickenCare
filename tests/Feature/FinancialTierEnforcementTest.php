<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialTierEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private function freeUser(): User
    {
        return User::factory()->create(['tier' => 'free']);
    }

    public function test_free_user_cannot_access_expenses_index(): void
    {
        $this->actingAs($this->freeUser())
            ->get('/app/expenses')
            ->assertRedirect(route('app.dashboard'));
    }

    public function test_free_user_cannot_post_to_expenses_store(): void
    {
        $this->actingAs($this->freeUser())
            ->post('/app/expenses', [
                'date' => now()->format('Y-m-d'),
                'category' => 'Feed',
                'description' => 'Test',
                'amount' => '10.00',
            ])
            ->assertRedirect(route('app.dashboard'));
    }

    public function test_free_user_cannot_access_feed_index(): void
    {
        $this->actingAs($this->freeUser())
            ->get('/app/feed')
            ->assertRedirect(route('app.dashboard'));
    }

    public function test_free_user_cannot_access_customers_index(): void
    {
        $this->actingAs($this->freeUser())
            ->get('/app/customers')
            ->assertRedirect(route('app.dashboard'));
    }

    public function test_free_user_cannot_access_sales_index(): void
    {
        $this->actingAs($this->freeUser())
            ->get('/app/sales')
            ->assertRedirect(route('app.dashboard'));
    }

    public function test_htmx_free_user_receives_premium_gate_partial(): void
    {
        $user = $this->freeUser();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/expenses');

        // HTMX requests get a 200 with the premium gate partial (not a redirect)
        $response->assertStatus(200);
        $response->assertViewIs('partials.premium-gate');
    }

    public function test_unauthenticated_user_redirected_to_login_for_all_premium_routes(): void
    {
        $routes = [
            '/app/expenses',
            '/app/feed',
            '/app/customers',
            '/app/sales',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertRedirect(route('login'));
        }
    }
}
