<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_sees_app_layout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertStatus(200);
        $response->assertSee('class="sidebar"', false);
        $response->assertSee('class="navbar"', false);
        $response->assertSee('id="main-content"', false);
        $response->assertSee('id="modal-container"', false);
    }

    public function test_sidebar_shows_free_tier_nav_items(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertSee('Dashboard');
        $response->assertSee('Egg Tracking');
        $response->assertSee('Account');
    }

    public function test_sidebar_hides_premium_items_for_free_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertDontSee('Flock Profile');
        $response->assertDontSee('Batches');
        $response->assertDontSee('Expenses');
        $response->assertDontSee('Feed Inventory');
        $response->assertDontSee('Customers');
        $response->assertDontSee('Sales Reports');
        $response->assertDontSee('Savings');
        $response->assertDontSee('Viability');
    }

    public function test_sidebar_shows_premium_items_for_premium_user(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertSee('Flock Profile');
        $response->assertSee('Batches');
        $response->assertSee('Expenses');
        $response->assertSee('Feed Inventory');
        $response->assertSee('Customers');
        $response->assertSee('Sales');
        $response->assertSee('Savings');
        $response->assertSee('Viability');
    }

    public function test_active_link_has_aria_current(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertSee('aria-current="page"', false);
        $response->assertSee('sidebar__link--active', false);
    }

    public function test_sidebar_has_navigation_role(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertSee('role="navigation"', false);
        $response->assertSee('aria-label="Main navigation"', false);
    }

    public function test_guest_layout_renders_for_auth_pages(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('class="auth-layout"', false);
        $response->assertDontSee('class="sidebar"', false);
    }

    public function test_flash_message_displays(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['success' => 'Test flash message'])
            ->get('/app');

        $response->assertSee('Test flash message');
        $response->assertSee('role="alert"', false);
    }

    public function test_csrf_token_in_body_hx_headers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertSee('hx-headers', false);
        $response->assertSee('X-CSRF-TOKEN', false);
    }
}
