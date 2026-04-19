<?php

namespace Tests\Feature;

use App\Models\FlockEvent;
use App\Models\FlockProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FlockEventControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected FlockProfile $profile;

    protected FlockProfile $otherProfile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->premium()->create();
        $this->otherUser = User::factory()->premium()->create();
        $this->profile = FlockProfile::factory()->create(['user_id' => $this->user->id]);
        $this->otherProfile = FlockProfile::factory()->create(['user_id' => $this->otherUser->id]);
    }

    private function validEventData(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-04-10',
            'type' => 'acquisition',
            'description' => 'Acquired 5 new hens',
            'affected_birds' => 5,
        ], $overrides);
    }

    // === Store ===

    public function test_user_can_add_event_to_own_profile(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/app/flock/{$this->profile->id}/events", $this->validEventData());

        $response->assertRedirect(route('app.flock.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('flock_events', [
            'flock_profile_id' => $this->profile->id,
            'type' => 'acquisition',
        ]);
    }

    public function test_user_can_add_event_via_htmx(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post("/app/flock/{$this->profile->id}/events", $this->validEventData());

        $response->assertOk();
        $response->assertViewIs('flock.partials.timeline');
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/app/flock/{$this->profile->id}/events", []);

        $response->assertSessionHasErrors(['date', 'type', 'description']);
    }

    public function test_store_validates_event_type_enum(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/app/flock/{$this->profile->id}/events", $this->validEventData([
                'type' => 'invalid_type',
            ]));

        $response->assertSessionHasErrors(['type']);
    }

    public function test_user_cannot_add_event_to_another_users_profile(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/app/flock/{$this->otherProfile->id}/events", $this->validEventData());

        $response->assertForbidden();
    }

    // === Update ===

    public function test_user_can_update_own_event(): void
    {
        $event = FlockEvent::factory()->create([
            'flock_profile_id' => $this->profile->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/app/flock/{$this->profile->id}/events/{$event->id}", $this->validEventData([
                'description' => 'Updated description',
            ]));

        $response->assertRedirect(route('app.flock.index'));
        $this->assertDatabaseHas('flock_events', [
            'id' => $event->id,
            'description' => 'Updated description',
        ]);
    }

    public function test_user_can_update_event_via_htmx(): void
    {
        $event = FlockEvent::factory()->create([
            'flock_profile_id' => $this->profile->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['HX-Request' => 'true'])
            ->put("/app/flock/{$this->profile->id}/events/{$event->id}", $this->validEventData([
                'description' => 'HTMX updated',
            ]));

        $response->assertOk();
        $response->assertViewIs('flock.partials.timeline');
    }

    public function test_user_cannot_update_another_users_event(): void
    {
        $event = FlockEvent::factory()->create([
            'flock_profile_id' => $this->otherProfile->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/app/flock/{$this->otherProfile->id}/events/{$event->id}", $this->validEventData());

        $response->assertForbidden();
    }

    // === Destroy ===

    public function test_user_can_delete_own_event(): void
    {
        $event = FlockEvent::factory()->create([
            'flock_profile_id' => $this->profile->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete("/app/flock/{$this->profile->id}/events/{$event->id}");

        $response->assertRedirect(route('app.flock.index'));
        $this->assertDatabaseMissing('flock_events', ['id' => $event->id]);
    }

    public function test_user_can_delete_event_via_htmx(): void
    {
        $event = FlockEvent::factory()->create([
            'flock_profile_id' => $this->profile->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete("/app/flock/{$this->profile->id}/events/{$event->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('flock_events', ['id' => $event->id]);
    }

    public function test_user_cannot_delete_another_users_event(): void
    {
        $event = FlockEvent::factory()->create([
            'flock_profile_id' => $this->otherProfile->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete("/app/flock/{$this->otherProfile->id}/events/{$event->id}");

        $response->assertForbidden();
    }
}
