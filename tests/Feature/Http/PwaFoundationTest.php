<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_layout_emits_pwa_metadata(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('rel="manifest" href="/manifest.webmanifest"', false);
        $response->assertSee('name="theme-color" content="#4a7c59"', false);
        $response->assertSee('rel="apple-touch-icon" href="/images/pwa/apple-touch-icon.png"', false);
        $response->assertSee('x-data="window.ChickenCare.pwa.banner()"', false);
        $response->assertSee('data-install-title="Install ChickenCare"', false);
    }

    public function test_authenticated_layout_emits_pwa_metadata(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('app.dashboard'));

        $response->assertOk();
        $response->assertSee('rel="manifest" href="/manifest.webmanifest"', false);
        $response->assertSee('name="theme-color" content="#4a7c59"', false);
        $response->assertSee('rel="apple-touch-icon" href="/images/pwa/apple-touch-icon.png"', false);
        $response->assertSee('x-data="window.ChickenCare.pwa.banner()"', false);
        $response->assertSee('data-update-title="Update available"', false);
    }

    public function test_manifest_file_contains_expected_install_contract(): void
    {
        $manifestPath = public_path('manifest.webmanifest');

        $this->assertFileExists($manifestPath);

        $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('ChickenCare', $manifest['name']);
        $this->assertSame('/app', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#4a7c59', $manifest['theme_color']);
        $this->assertSame('#fafafa', $manifest['background_color']);
        $this->assertCount(3, $manifest['icons']);
    }

    public function test_pwa_icons_exist(): void
    {
        $this->assertFileExists(public_path('images/pwa/icon-192-maskable.png'));
        $this->assertFileExists(public_path('images/pwa/icon-512-maskable.png'));
        $this->assertFileExists(public_path('images/pwa/icon-512.png'));
        $this->assertFileExists(public_path('images/pwa/apple-touch-icon.png'));
    }

    public function test_service_worker_exists(): void
    {
        $this->assertFileExists(public_path('sw.js'));

        $serviceWorker = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("const SW_VERSION = '2026-04-26-1';", $serviceWorker);
        $this->assertStringContainsString('caches.keys()', $serviceWorker);
        $this->assertStringContainsString("self.addEventListener('fetch'", $serviceWorker);
        $this->assertStringContainsString("request.headers.get('HX-Boosted') === 'true'", $serviceWorker);
        $this->assertStringContainsString("const OFFLINE_HTML_PATH = '/offline';", $serviceWorker);
        $this->assertStringContainsString("event.data?.type === 'SKIP_WAITING'", $serviceWorker);
    }

    public function test_offline_route_renders_the_controlled_fallback_page(): void
    {
        $response = $this->get(route('offline'));

        $response->assertOk();
        $response->assertSee(__('ui.pwa.offline_title'));
        $response->assertSee(__('ui.pwa.offline_message'));
        $response->assertSee(route('landing', absolute: false));
    }

    public function test_csrf_token_endpoint_requires_authentication(): void
    {
        $response = $this->getJson(route('csrf-token'));

        $response->assertUnauthorized();
    }

    public function test_csrf_token_endpoint_returns_the_current_token_for_authenticated_requests(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('csrf-token'));

        $response->assertOk();
        $response->assertJsonStructure(['token']);
        $this->assertSame(csrf_token(), $response->json('token'));
    }

    /**
     * Note: HTTP-level assertions for cache headers require real Apache environment.
     * These tests verify file existence and content; cache header verification
     * is performed via production `curl -I` checklist in DEPLOY.md.
     * Static files are served by Apache/Plesk, not Laravel middleware.
     */
}
