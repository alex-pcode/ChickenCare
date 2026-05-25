<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('app.dashboard', absolute: false));
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_guest_locale_cookie_carries_into_authenticated_dashboard_after_login(): void
    {
        $user = User::factory()->create(['locale' => null]);

        $response = $this->withCookie(config('app.locale_cookie'), 'sr')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('app.dashboard', absolute: false));

        $dashboardResponse = $this->get('/app/');

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('<html lang="sr">', false);
        $dashboardResponse->assertSee('Kontrolna tabla');
    }

    public function test_login_page_renders_serbian_copy_when_locale_cookie_is_serbian(): void
    {
        $response = $this->withCookie(config('app.locale_cookie'), 'sr')->get('/login');

        $response->assertOk();
        $response->assertSee('<html lang="sr">', false);
        $response->assertSee('Prijavite se');
        $response->assertSee('Zapamti me');
        $response->assertSee('Nastavite putem Google');
        $response->assertSee('ili nastavite e-postom');
        $response->assertDontSee('auth.pages.login.title', false);
    }

    public function test_failed_login_uses_serbian_validation_message_when_locale_cookie_is_serbian(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/login')
            ->withCookie(config('app.locale_cookie'), 'sr')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'Ovi podaci za prijavu se ne poklapaju sa našom evidencijom.',
        ]);
    }
}
