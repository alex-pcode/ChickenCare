<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_loads(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertStatus(302);
        }

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertStatus(429);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_forgot_password_page_renders_serbian_copy_when_locale_cookie_is_serbian(): void
    {
        $response = $this->withCookie(config('app.locale_cookie'), 'sr')->get('/forgot-password');

        $response->assertOk();
        $response->assertSee('Zaboravljena lozinka');
        $response->assertSee('Posalji link za resetovanje lozinke');
        $response->assertDontSee('auth.pages.forgot_password.title', false);
    }

    public function test_reset_password_page_renders_serbian_copy_when_locale_cookie_is_serbian(): void
    {
        $response = $this->withCookie(config('app.locale_cookie'), 'sr')
            ->get(route('password.reset', ['token' => 'test-token']).'?email=test@example.com');

        $response->assertOk();
        $response->assertSee('<html lang="sr">', false);
        $response->assertSee('Resetuj lozinku');
        $response->assertSee('Potvrdite lozinku');
        $response->assertDontSee('auth.pages.reset_password.title', false);
    }

    public function test_password_reset_link_request_stores_serbian_status_message(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->from('/forgot-password')
            ->withCookie(config('app.locale_cookie'), 'sr')
            ->post('/forgot-password', ['email' => $user->email]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHas('status', 'Poslali smo vam link za resetovanje lozinke putem e-pošte.');
    }
}
