<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoostedNavigationCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_boosted_get_navigation_is_briefly_cacheable(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app', [
            'HX-Request' => 'true',
            'HX-Boosted' => 'true',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('max-age=5', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('HX-Request', (string) $response->headers->get('Vary'));
    }

    public function test_direct_navigation_stays_no_store(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertOk();
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_non_boosted_htmx_partial_stays_no_store(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app', [
            'HX-Request' => 'true',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }
}
