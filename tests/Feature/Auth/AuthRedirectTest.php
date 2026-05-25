<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/app');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_app(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app');

        $response->assertStatus(200);
    }

    public function test_confirm_password_page_renders_serbian_copy_for_authenticated_user(): void
    {
        $user = User::factory()->create(['locale' => 'sr']);

        $response = $this->actingAs($user)->get('/confirm-password');

        $response->assertOk();
        $response->assertSee('Potvrdite lozinku');
        $response->assertSee('Ovo je zasticeni deo aplikacije. Potvrdite lozinku pre nego sto nastavite.');
        $response->assertSee('Potvrdi');
        $response->assertDontSee('auth.pages.confirm_password.title', false);
    }

    public function test_verify_email_page_renders_serbian_copy_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'locale' => 'sr',
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['status' => 'verification-link-sent'])
            ->get('/verify-email');

        $response->assertOk();
        $response->assertSee('Verifikujte e-postu');
        $response->assertSee('Novi link za verifikaciju je poslat na adresu e-poste koju ste naveli prilikom registracije.');
        $response->assertSee('Posalji ponovo verifikacioni imejl');
        $response->assertSee('Odjavite se');
        $response->assertDontSee('auth.pages.verify_email.title', false);
    }
}
