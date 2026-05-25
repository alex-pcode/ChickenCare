<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsurePremiumTierMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_premium_user_can_access_premium_route(): void
    {
        $user = User::factory()->premium()->create();

        $this->actingAs($user)
            ->get('/app/expenses')
            ->assertOk();
    }

    public function test_admin_user_can_access_premium_route(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get('/app/expenses')
            ->assertOk();
    }

    public function test_free_user_redirected_from_premium_route(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/app/expenses')
            ->assertRedirect('/app')
            ->assertSessionHas('warning', 'Upgrade to Premium to access this feature.');
    }

    public function test_free_user_redirected_from_premium_route_with_serbian_warning(): void
    {
        $user = User::factory()->create(['locale' => 'sr']);

        $this->actingAs($user)
            ->get('/app/expenses')
            ->assertRedirect('/app')
            ->assertSessionHas('warning', 'Predjite na Premium da biste pristupili ovoj funkciji.');
    }

    public function test_free_user_htmx_request_returns_premium_gate_partial(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/expenses');

        $response->assertOk();
        $response->assertSee('premium-gate', false);
    }

    public function test_free_user_htmx_request_returns_localized_premium_gate_partial_for_serbian_locale(): void
    {
        $user = User::factory()->create(['locale' => 'sr']);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/expenses');

        $response->assertOk();
        $response->assertViewIs('partials.premium-gate');
        $response->assertSee('Premium funkcija');
        $response->assertSee('Za pristup funkciji Troškovi potrebna je Premium pretplata.');
        $response->assertDontSee('app.expenses.index', false);
    }
}
