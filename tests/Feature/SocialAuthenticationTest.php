<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => '/auth/google/callback',
            'services.facebook.client_id' => 'facebook-client-id',
            'services.facebook.client_secret' => 'facebook-client-secret',
            'services.facebook.redirect' => '/auth/facebook/callback',
        ]);
    }

    public function test_social_provider_buttons_are_visible_on_auth_screens(): void
    {
        $loginResponse = $this->get(route('login'));

        $loginResponse->assertOk();
        $loginResponse->assertSee(route('social.redirect', ['provider' => 'google']), false);
        $loginResponse->assertSee(route('social.redirect', ['provider' => 'facebook']), false);
        $loginResponse->assertSee('Continue with Google', false);
        $loginResponse->assertSee('Continue with Facebook', false);

        $registerResponse = $this->get(route('register'));

        $registerResponse->assertOk();
        $registerResponse->assertSee('Sign up with Google', false);
        $registerResponse->assertSee('Sign up with Facebook', false);
    }

    public function test_google_redirect_route_sends_users_to_the_provider(): void
    {
        Socialite::fake('google');

        $response = $this->get(route('social.redirect', ['provider' => 'google']));

        $response->assertRedirect();
    }

    public function test_social_callback_creates_a_new_user_and_social_account(): void
    {
        Socialite::fake('google', $this->fakeProviderUser(
            id: 'google-user-1',
            name: 'Farmer Jane',
            email: 'jane@example.com',
            avatar: 'https://example.com/avatar.jpg',
        ));

        $response = $this->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('app.dashboard', absolute: false));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Farmer Jane',
        ]);
        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_user_id' => 'google-user-1',
            'provider_email' => 'jane@example.com',
        ]);
    }

    public function test_social_callback_links_to_an_existing_user_with_the_same_email(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing Farmer',
        ]);

        Socialite::fake('google', $this->fakeProviderUser(
            id: 'google-user-2',
            name: 'Existing Farmer',
            email: 'existing@example.com',
            avatar: 'https://example.com/existing.jpg',
        ));

        $response = $this->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('app.dashboard', absolute: false));
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-user-2',
        ]);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_social_callback_requires_an_email_address_from_the_provider(): void
    {
        Socialite::fake('facebook', $this->fakeProviderUser(
            id: 'facebook-user-1',
            name: 'No Email Farmer',
            email: null,
            avatar: 'https://example.com/no-email.jpg',
        ));

        $response = $this->get(route('social.callback', ['provider' => 'facebook']));

        $response->assertRedirect(route('register'));
        $response->assertSessionHas('auth_error');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_social_provider_buttons_are_localized_when_locale_cookie_is_serbian(): void
    {
        $loginResponse = $this->withCookie(config('app.locale_cookie'), 'sr')->get(route('login'));

        $loginResponse->assertOk();
        $loginResponse->assertSee('Nastavite putem Google', false);
        $loginResponse->assertSee('Nastavite putem Facebook', false);

        $registerResponse = $this->withCookie(config('app.locale_cookie'), 'sr')->get(route('register'));

        $registerResponse->assertOk();
        $registerResponse->assertSee('Registrujte se putem Google', false);
        $registerResponse->assertSee('Registrujte se putem Facebook', false);
    }

    public function test_unconfigured_social_provider_error_is_localized_in_serbian(): void
    {
        config(['services.google.client_id' => null]);

        $response = $this->withCookie(config('app.locale_cookie'), 'sr')
            ->get(route('social.redirect', ['provider' => 'google']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error', 'Prijava preko Google jos nije podesena.');
    }

    private function fakeProviderUser(string $id, string $name, ?string $email, ?string $avatar = null): SocialiteUser
    {
        return (new SocialiteUser)->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
        ]);
    }
}
