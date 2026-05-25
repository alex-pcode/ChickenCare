<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_loads(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_user_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('app.dashboard', absolute: false));

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('free', $user->tier);
        $this->assertFalse($user->is_admin);
    }

    public function test_registration_validates_required_fields(): void
    {
        $response = $this->post('/register', []);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_registration_page_renders_serbian_copy_when_locale_cookie_is_serbian(): void
    {
        $response = $this->withCookie(config('app.locale_cookie'), 'sr')->get('/register');

        $response->assertOk();
        $response->assertSee('<html lang="sr">', false);
        $response->assertSee('Registrujte se');
        $response->assertSee('Registrujte se putem Google');
        $response->assertSee('ili se registrujte e-postom');
        $response->assertSee('Vec ste registrovani?');
        $response->assertDontSee('auth.pages.register.title', false);
    }
}
