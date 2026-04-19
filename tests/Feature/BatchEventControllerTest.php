<?php

namespace Tests\Feature;

use App\Models\BatchEvent;
use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BatchEventControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected FlockBatch $batch;

    protected FlockBatch $otherBatch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->premium()->create();
        $this->otherUser = User::factory()->premium()->create();
        $this->batch = FlockBatch::factory()->create(['user_id' => $this->user->id]);
        $this->otherBatch = FlockBatch::factory()->create(['user_id' => $this->otherUser->id]);
    }

    private function validEventData(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-04-10',
            'type' => 'health_check',
            'description' => 'Routine inspection',
        ], $overrides);
    }

    // === Store ===

    public function test_user_can_add_event_to_own_batch(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('app.batches.events.store', $this->batch), $this->validEventData());

        $response->assertRedirect();
        $this->assertDatabaseHas('batch_events', [
            'batch_id' => $this->batch->id,
            'type' => 'health_check',
        ]);
    }

    public function test_user_can_add_event_via_htmx(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.batches.events.store', $this->batch), $this->validEventData());

        $response->assertOk();
        $response->assertViewIs('batches.partials.timeline-event-row');
        $response->assertHeader('HX-Trigger');
        $this->assertStringContainsString('flock:changed', $response->headers->get('HX-Trigger', ''));
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('app.batches.events.store', $this->batch), []);

        $response->assertSessionHasErrors(['date', 'type', 'description']);
    }

    public function test_store_validates_event_type_enum(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('app.batches.events.store', $this->batch), $this->validEventData([
                'type' => 'invalid_type',
            ]));

        $response->assertSessionHasErrors(['type']);
    }

    public function test_user_cannot_add_event_to_another_users_batch(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('app.batches.events.store', $this->otherBatch), $this->validEventData());

        $response->assertForbidden();
    }

    public function test_store_sets_user_id_on_event(): void
    {
        $this->actingAs($this->user)
            ->post(route('app.batches.events.store', $this->batch), $this->validEventData());

        $this->assertDatabaseHas('batch_events', [
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
        ]);
    }

    // === Update ===

    public function test_user_can_update_own_batch_event(): void
    {
        $event = BatchEvent::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('app.batches.events.update', [$this->batch, $event]), $this->validEventData([
                'description' => 'Updated inspection',
            ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('batch_events', [
            'id' => $event->id,
            'description' => 'Updated inspection',
        ]);
    }

    public function test_user_can_update_batch_event_via_htmx(): void
    {
        $event = BatchEvent::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['HX-Request' => 'true'])
            ->put(route('app.batches.events.update', [$this->batch, $event]), $this->validEventData());

        $response->assertOk();
        $response->assertViewIs('batches.partials.timeline-event-row');
    }

    public function test_user_cannot_update_another_users_batch_event(): void
    {
        $event = BatchEvent::factory()->create([
            'batch_id' => $this->otherBatch->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('app.batches.events.update', [$this->otherBatch, $event]), $this->validEventData());

        $response->assertForbidden();
    }

    public function test_user_cannot_update_event_via_wrong_batch(): void
    {
        $batch2 = FlockBatch::factory()->create(['user_id' => $this->user->id]);
        $event = BatchEvent::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('app.batches.events.update', [$batch2, $event]), $this->validEventData());

        $response->assertNotFound();
    }

    // === Destroy ===

    public function test_user_can_delete_own_batch_event(): void
    {
        $event = BatchEvent::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('app.batches.events.destroy', [$this->batch, $event]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('batch_events', ['id' => $event->id]);
    }

    public function test_user_can_delete_batch_event_via_htmx(): void
    {
        $event = BatchEvent::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete(route('app.batches.events.destroy', [$this->batch, $event]));

        $response->assertOk();
        $this->assertDatabaseMissing('batch_events', ['id' => $event->id]);
    }

    public function test_user_cannot_delete_another_users_batch_event(): void
    {
        $event = BatchEvent::factory()->create([
            'batch_id' => $this->otherBatch->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('app.batches.events.destroy', [$this->otherBatch, $event]));

        $response->assertForbidden();
    }

    public function test_user_cannot_delete_event_via_wrong_batch(): void
    {
        $batch2 = FlockBatch::factory()->create(['user_id' => $this->user->id]);
        $event = BatchEvent::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('app.batches.events.destroy', [$batch2, $event]));

        $response->assertNotFound();
    }
}
