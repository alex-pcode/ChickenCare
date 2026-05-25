<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_resolves_to_english_without_saved_preference(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('<html lang="en">', false);
    }

    public function test_guest_browser_persistence_uses_locale_cookie(): void
    {
        $response = $this->withCookie(config('app.locale_cookie'), 'sr')->get('/login');

        $response->assertOk();
        $response->assertSee('<html lang="sr">', false);
    }

    public function test_authenticated_locale_preference_takes_precedence_over_browser_persistence(): void
    {
        $user = User::factory()->create(['locale' => 'sr']);

        $response = $this->actingAs($user)
            ->withCookie(config('app.locale_cookie'), 'en')
            ->get('/app');

        $response->assertOk();
        $response->assertSee('<html lang="sr">', false);
        $response->assertSee('Kontrolna tabla');
    }

    public function test_unsupported_browser_locale_falls_back_to_english(): void
    {
        $response = $this->withCookie(config('app.locale_cookie'), 'de')->get('/login');

        $response->assertOk();
        $response->assertSee('<html lang="en">', false);
    }

    public function test_unsupported_session_locale_falls_back_to_english(): void
    {
        $response = $this->withSession(['locale' => 'de'])->get('/login');

        $response->assertOk();
        $response->assertSee('<html lang="en">', false);
    }

    public function test_unsupported_authenticated_locale_preference_falls_back_to_english(): void
    {
        $user = User::factory()->create(['locale' => 'de']);

        $response = $this->actingAs($user)->get('/app');

        $response->assertOk();
        $response->assertSee('<html lang="en">', false);
        $response->assertSee('Dashboard');
    }

    public function test_boosted_request_keeps_authenticated_serbian_locale(): void
    {
        $user = User::factory()->create(['locale' => 'sr']);

        $response = $this->actingAs($user)
            ->withHeaders([
                'HX-Request' => 'true',
                'HX-Boosted' => 'true',
            ])
            ->get('/app/account');

        $response->assertOk();
        $response->assertSee('<html lang="sr">', false);
        $response->assertSee('Podešavanja naloga');
    }
}
