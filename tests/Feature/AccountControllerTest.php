<?php

namespace Tests\Feature;

use App\Enums\ChickenGoal;
use App\Models\EggEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_account_page(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/app/account');
        $response->assertStatus(200);
        $response->assertViewIs('account.index');
        $response->assertSee('Account Settings');
        $response->assertSee('data-loading-skeleton="account-tab"', false);
        $response->assertSee('role="tablist"', false);
    }

    public function test_account_page_defaults_to_profile_tab(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/app/account');
        $response->assertSee('activeTab: \'profile\'', false);
        $response->assertSee('Profile');
    }

    public function test_account_page_selects_goals_tab(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/app/account?tab=goals');
        $response->assertStatus(200);
    }

    public function test_htmx_request_returns_partial_only(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/account?tab=profile');
        $response->assertStatus(200);
        $response->assertDontSee('<!DOCTYPE html>', false);
        $response->assertSee('account-profile', false);
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get('/app/account');
        $response->assertRedirect('/login');
    }

    public function test_invalid_tab_defaults_to_profile(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/app/account?tab=invalid');
        $response->assertStatus(200);
    }

    public function test_profile_update_happy_path(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        $response = $this->actingAs($user)->patch('/app/account/profile', [
            'name' => 'New Name',
        ]);
        $response->assertRedirect(route('app.account.index'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_profile_update_via_htmx(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->patch('/app/account/profile', ['name' => 'New Name']);
        $response->assertStatus(200);
        $response->assertHeader('HX-Trigger', 'account-profile-updated');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_profile_update_validation_failure(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->patch('/app/account/profile', ['name' => '']);
        $response->assertSessionHasErrors('name');
    }

    public function test_profile_update_name_too_long(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->patch('/app/account/profile', [
            'name' => str_repeat('a', 256),
        ]);
        $response->assertSessionHasErrors('name');
    }

    // Security tab tests

    public function test_security_tab_renders(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/app/account?tab=security');
        $response->assertStatus(200);
        $response->assertSee('Password Reset');
        $response->assertSee('Reset Password');
    }

    public function test_password_reset_link_dispatched(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'test@example.com'])
            ->andReturn(Password::RESET_LINK_SENT);

        $user = User::factory()->create(['email' => 'test@example.com']);
        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post('/app/account/password-reset-link');

        $response->assertStatus(200);
        $response->assertHeader('HX-Trigger', 'account-password-reset-sent');
    }

    public function test_password_reset_link_failure(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::RESET_THROTTLED);

        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post('/app/account/password-reset-link');

        $response->assertStatus(200);
    }

    // Billing tab tests

    public function test_billing_tab_renders_for_free_user(): void
    {
        $user = User::factory()->create(['tier' => 'free']);
        $response = $this->actingAs($user)->get('/app/account?tab=billing');
        $response->assertStatus(200);
        $response->assertSee('Free');
        $response->assertSee('Basic features available');
    }

    public function test_billing_tab_renders_for_premium_user(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $response = $this->actingAs($user)->get('/app/account?tab=billing');
        $response->assertStatus(200);
        $response->assertSee('Premium');
        $response->assertSee('Full access to all features');
    }

    public function test_upgrade_button_is_disabled(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/app/account?tab=billing');
        $response->assertSee('disabled', false);
        $response->assertSee('Coming Soon');
    }

    // Goals tab tests

    public function test_goals_tab_renders(): void
    {
        $user = User::factory()->create(['chicken_goal' => ChickenGoal::Hobby]);
        $response = $this->actingAs($user)->get('/app/account?tab=goals');
        $response->assertStatus(200);
        $response->assertSee('Farm Preferences');
        $response->assertSee('Yearly Egg Production Goal');
        $response->assertSee('Price per Egg ($)');
    }

    public function test_preferences_update_happy_path(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->patch('/app/account/preferences', [
            'chicken_goal' => 'hobby',
            'yearly_egg_goal' => 1200,
            'egg_price' => 0.50,
            'locale' => 'sr',
        ]);
        $response->assertRedirect(route('app.account.index', ['tab' => 'goals']));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'chicken_goal' => 'hobby',
            'yearly_egg_goal' => 1200,
            'locale' => 'sr',
        ]);
    }

    public function test_full_page_locale_change_renders_success_flash_in_new_locale(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->patch('/app/account/preferences', [
                'chicken_goal' => 'hobby',
                'yearly_egg_goal' => 1200,
                'egg_price' => 0.50,
                'locale' => 'sr',
            ]);

        $response->assertOk();
        $response->assertSee('<html lang="sr">', false);
        $response->assertSee('Podešavanja su uspešno ažurirana!');
        $response->assertDontSee('Preferences updated successfully!');
    }

    public function test_preferences_update_via_htmx(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->patch('/app/account/preferences', [
                'chicken_goal' => 'business',
                'yearly_egg_goal' => 5000,
                'egg_price' => 1.25,
            ]);
        $response->assertStatus(200);
        $response->assertHeader('HX-Trigger', 'account-preferences-updated');
        $response->assertHeaderMissing('HX-Redirect');
    }

    public function test_htmx_locale_change_returns_hx_redirect_for_full_shell_refresh(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->patch('/app/account/preferences', [
                'chicken_goal' => 'business',
                'yearly_egg_goal' => 5000,
                'egg_price' => 1.25,
                'locale' => 'sr',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('HX-Redirect', route('app.account.index', ['tab' => 'goals']));
        $response->assertHeaderMissing('HX-Trigger');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => 'sr',
        ]);
    }

    public function test_preferences_validation_failure_invalid_goal(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->patch('/app/account/preferences', [
            'chicken_goal' => 'invalid',
            'yearly_egg_goal' => 1200,
            'egg_price' => 0.50,
        ]);
        $response->assertSessionHasErrors('chicken_goal');
    }

    public function test_preferences_validation_failure_negative_price(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->patch('/app/account/preferences', [
            'chicken_goal' => 'hobby',
            'yearly_egg_goal' => 1200,
            'egg_price' => -1,
        ]);
        $response->assertSessionHasErrors('egg_price');
    }

    public function test_preferences_validation_failure_invalid_locale(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/app/account/preferences', [
            'chicken_goal' => 'hobby',
            'yearly_egg_goal' => 1200,
            'egg_price' => 0.50,
            'locale' => 'de',
        ]);

        $response->assertSessionHasErrors('locale');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => null,
        ]);
    }

    public function test_htmx_preferences_validation_failure_uses_serbian_messages(): void
    {
        $user = User::factory()->create(['locale' => 'sr']);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->patch('/app/account/preferences', [
                'chicken_goal' => 'hobby',
                'yearly_egg_goal' => 1200,
                'egg_price' => 0.50,
                'locale' => 'de',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.locale.0', 'Izabrana vrednost za polje jezik nije ispravna.');
        $response->assertDontSee('validation.in', false);
    }

    public function test_historical_data_hidden_when_no_entries(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/app/account?tab=goals');
        $response->assertDontSee('Historical Data');
    }

    public function test_historical_data_shown_when_entries_exist(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->create(['user_id' => $user->id]);
        $response = $this->actingAs($user)->get('/app/account?tab=goals');
        $response->assertSee('Historical Data');
    }

    public function test_account_page_renders_serbian_labels_when_locale_is_serbian(): void
    {
        $user = User::factory()->create(['locale' => 'sr']);

        $response = $this->actingAs($user)->get('/app/account');

        $response->assertOk();
        $response->assertSee('Podešavanja naloga');
        $response->assertSee('Profil');
        $response->assertSee('Bezbednost');
        $response->assertSee('Naplata');
        $response->assertSee('Ciljevi i podešavanja');
        $response->assertSee('Podešavanja naloga');
    }

    public function test_account_billing_htmx_partial_renders_serbian_copy(): void
    {
        $user = User::factory()->create(['locale' => 'sr', 'tier' => 'free']);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/account?tab=billing');

        $response->assertOk();
        $response->assertDontSee('<!DOCTYPE html>', false);
        $response->assertSee('Trenutni paket');
        $response->assertSee('Besplatni');
        $response->assertSee('Dostupne su osnovne funkcije');
        $response->assertSee('Predjite na Premium (uskoro)');
        $response->assertDontSee('account.billing.current_plan', false);
    }

    public function test_security_tab_renders_serbian_copy(): void
    {
        $user = User::factory()->create(['locale' => 'sr']);

        $response = $this->actingAs($user)->get('/app/account?tab=security');

        $response->assertOk();
        $response->assertSee('Resetovanje lozinke');
        $response->assertSee('Resetujte lozinku tako sto cete dobiti bezbedan link putem e-poste');
        $response->assertSee('Resetuj lozinku');
    }

    public function test_password_reset_failure_message_is_localized_for_serbian_locale(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::RESET_THROTTLED);

        $user = User::factory()->create(['locale' => 'sr']);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post('/app/account/password-reset-link');

        $response->assertOk();

        $payload = json_decode((string) $response->headers->get('HX-Trigger'), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Sačekajte pre nego što pokušate ponovo.', $payload['account-password-reset-failed']['message']);
    }

    public function test_serbian_goals_progress_copy_is_translated(): void
    {
        $user = User::factory()->create([
            'locale' => 'sr',
            'yearly_egg_goal' => 100,
        ]);
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => now()->startOfWeek(),
            'count' => 10,
        ]);

        $response = $this->actingAs($user)->get('/app/account?tab=goals');

        $response->assertOk();
        $response->assertSee('Samo nastavite!');
        $response->assertSee('Potrebno vam je još 90 jaja');
    }
}
