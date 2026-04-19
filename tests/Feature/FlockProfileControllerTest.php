<?php

namespace Tests\Feature;

use App\Models\FlockProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FlockProfileControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->premium()->create();
        $this->otherUser = User::factory()->premium()->create();
    }

    // === Index / Show-or-Create ===

    public function test_premium_user_can_view_flock_page(): void
    {
        $response = $this->actingAs($this->user)->get('/app/flock');

        $response->assertStatus(200);
        $response->assertViewIs('flock.index');
    }

    public function test_premium_user_with_profile_sees_existing_profile(): void
    {
        $profile = FlockProfile::factory()->create([
            'user_id' => $this->user->id,
            'farm_name' => 'Sunny Coop',
            'hens' => 12,
        ]);

        $response = $this->actingAs($this->user)->get('/app/flock');

        $response->assertStatus(200);
        $response->assertViewHas('profile', function ($viewProfile) use ($profile) {
            return $viewProfile->id === $profile->id;
        });
    }

    public function test_free_user_is_blocked_from_flock_page(): void
    {
        $freeUser = User::factory()->create();

        $response = $this->actingAs($freeUser)->get('/app/flock');

        $response->assertRedirect(route('app.dashboard'));
    }

    public function test_free_user_htmx_gets_premium_gate_partial(): void
    {
        $freeUser = User::factory()->create();

        $response = $this->actingAs($freeUser)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/flock');

        $response->assertOk();
        $response->assertViewIs('partials.premium-gate');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/app/flock');

        $response->assertRedirect('/login');
    }

    // === Auto-creation (index creates profile if none exists) ===

    public function test_premium_user_can_create_flock_profile(): void
    {
        $this->assertNull($this->user->flockProfile);

        $response = $this->actingAs($this->user)->get('/app/flock');

        $response->assertStatus(200);
        $this->assertDatabaseHas('flock_profiles', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_index_returns_same_profile_on_repeat_visit(): void
    {
        // First visit auto-creates
        $this->actingAs($this->user)->get('/app/flock');
        $profileId = $this->user->fresh()->flockProfile->id;

        // Unload cached relationship so second request re-queries
        $this->user->unsetRelation('flockProfile');

        // Second visit returns the same profile
        $response = $this->actingAs($this->user)->get('/app/flock');

        $response->assertStatus(200);
        $this->assertDatabaseCount('flock_profiles', 1);
        $response->assertViewHas('profile', function ($viewProfile) use ($profileId) {
            return $viewProfile->id === $profileId;
        });
    }

    // === Update ===

    public function test_premium_user_can_update_own_profile(): void
    {
        $profile = FlockProfile::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/app/flock/{$profile->id}", [
                'flock_size' => 25,
                'hens' => 15,
                'roosters' => 2,
                'chicks' => 5,
                'brooding' => 3,
            ]);

        $response->assertRedirect(route('app.flock.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('flock_profiles', [
            'id' => $profile->id,
            'flock_size' => 25,
            'hens' => 15,
        ]);
    }

    public function test_premium_user_can_update_profile_via_htmx(): void
    {
        $profile = FlockProfile::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['HX-Request' => 'true'])
            ->put("/app/flock/{$profile->id}", [
                'flock_size' => 20,
                'hens' => 10,
                'roosters' => 2,
                'chicks' => 5,
                'brooding' => 3,
            ]);

        $response->assertOk();
        $response->assertViewIs('flock.partials.flock-overview');
        $this->assertDatabaseHas('flock_profiles', [
            'id' => $profile->id,
            'flock_size' => 20,
        ]);
    }

    public function test_user_cannot_update_another_users_profile(): void
    {
        $profile = FlockProfile::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/app/flock/{$profile->id}", [
                'flock_size' => 99,
                'hens' => 50,
                'roosters' => 5,
                'chicks' => 10,
                'brooding' => 0,
            ]);

        $response->assertForbidden();
    }

    public function test_update_validates_required_fields(): void
    {
        $profile = FlockProfile::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/app/flock/{$profile->id}", []);

        $response->assertSessionHasErrors(['flock_size', 'hens', 'roosters', 'chicks', 'brooding']);
    }
}
