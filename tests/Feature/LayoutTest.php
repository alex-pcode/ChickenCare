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
        $response->assertSee('data-loading-skeleton="page-shell"', false);
        $response->assertSee('id="skeleton-template-page-shell"', false);
        $response->assertSee('id="modal-container"', false);
    }

    public function test_authenticated_layout_includes_first_paint_skeleton_markup_and_styles(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertStatus(200);
        $response->assertSee('id="fp-skeleton"', false);
        $response->assertSee('class="fp-skeleton__sidebar"', false);
        $response->assertSee('@keyframes shimmer', false);
        $response->assertSeeInOrder(['id="fp-skeleton"', 'class="app-layout"'], false);
    }

    public function test_sidebar_shows_free_tier_nav_items(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertSee('Dashboard');
        $response->assertSee('Eggs');
        $response->assertSee('Account');
    }

    public function test_sidebar_hides_premium_items_for_free_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertDontSee('sidebar__section-label">Premium', false);
        $response->assertDontSee('My Flock');
        $response->assertDontSee('Batches');
        $response->assertDontSee('mobile-dock__label">CRM', false);
        $response->assertDontSee('mobile-dock__label">Expenses', false);
        $response->assertDontSee('mobile-dock__label">More', false);
        $response->assertDontSee('Savings');
        $response->assertDontSee('Viability');
    }

    public function test_sidebar_shows_premium_items_for_premium_user(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertSee('My Flock');
        $response->assertSee('Batches');
        $response->assertSee('CRM');
        $response->assertSee('Expenses');
        $response->assertSee('Feed');
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

    public function test_free_tier_layout_exposes_only_free_route_warmup_targets(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app');
        $warmRoutes = $this->extractWarmRoutes($response->getContent());

        $response->assertStatus(200);
        $response->assertSee('data-warm-routes=', false);
        $this->assertContains(route('app.eggs.index'), $warmRoutes);
        $this->assertContains(route('app.account.index'), $warmRoutes);
        $this->assertNotContains(route('app.crm.index'), $warmRoutes);
        $this->assertNotContains(route('app.expenses.index'), $warmRoutes);
    }

    public function test_premium_layout_exposes_premium_route_warmup_targets(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app');
        $warmRoutes = $this->extractWarmRoutes($response->getContent());

        $response->assertStatus(200);
        $response->assertSee('data-warm-routes=', false);
        $this->assertContains(route('app.crm.index'), $warmRoutes);
        $this->assertContains(route('app.expenses.index'), $warmRoutes);
        $this->assertContains(route('app.feed.index'), $warmRoutes);
        $this->assertContains(route('app.viability.index'), $warmRoutes);
    }

    public function test_premium_layout_scopes_mobile_dock_sheet_state_locally(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertOk();
        $response->assertSee('@click="$dispatch(\'mobile-dock-more-open\')"', false);
        $response->assertSee('class="mobile-dock-sheet" x-data="{ moreOpen: false }"', false);
        $response->assertSee('x-on:mobile-dock-more-open.window="moreOpen = true"', false);
    }

    public function test_layout_uses_serbian_lang_attribute_and_navigation_labels_when_locale_is_serbian(): void
    {
        $user = User::factory()->create(['locale' => 'sr']);

        $response = $this->actingAs($user)->get('/app');

        $response->assertOk();
        $response->assertSee('<html lang="sr">', false);
        $response->assertSee('Kontrolna tabla');
        $response->assertSee('Nalog');
        $response->assertSee('Prikazi ili sakrij navigaciju');
    }

    public function test_layout_uses_english_fallback_when_serbian_key_is_missing(): void
    {
        app()->setLocale('sr');

        $this->assertSame('Dashboard Fallback Probe', __('dashboard.fallback_probe'));
    }

    /**
     * @return array<int, string>
     */
    private function extractWarmRoutes(string $content): array
    {
        preg_match("/data-warm-routes='([^']+)'/", $content, $matches);

        $decoded = html_entity_decode($matches[1] ?? '[]', ENT_QUOTES);

        return json_decode($decoded, true, flags: JSON_THROW_ON_ERROR);
    }
}
