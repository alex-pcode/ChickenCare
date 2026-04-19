<?php

namespace Tests\Feature;

use App\Models\DeathRecord;
use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DeathRecordControllerTest extends TestCase
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
        $this->batch = FlockBatch::factory()->create([
            'user_id' => $this->user->id,
            'initial_count' => 10,
            'current_count' => 10,
        ]);
        $this->otherBatch = FlockBatch::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);
    }

    private function validDeathData(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-04-10',
            'count' => 2,
            'cause' => 'predator',
            'description' => 'Predator attack overnight',
        ], $overrides);
    }

    // === Store ===

    public function test_user_can_add_death_record_to_own_batch(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('app.batches.deaths.store', $this->batch), $this->validDeathData());

        $response->assertRedirect();
        $this->assertDatabaseHas('death_records', [
            'batch_id' => $this->batch->id,
            'count' => 2,
            'cause' => 'predator',
        ]);
    }

    public function test_user_can_add_death_record_via_htmx(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.batches.deaths.store', $this->batch), $this->validDeathData());

        $response->assertOk();
        $response->assertViewIs('batches.partials.deaths-form');
        $response->assertHeader('HX-Trigger');
        $this->assertStringContainsString('flock:changed', $response->headers->get('HX-Trigger', ''));
    }

    public function test_store_decrements_batch_current_count(): void
    {
        $this->actingAs($this->user)
            ->post(route('app.batches.deaths.store', $this->batch), $this->validDeathData([
                'count' => 3,
            ]));

        $this->assertEquals(7, $this->batch->fresh()->current_count);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('app.batches.deaths.store', $this->batch), []);

        $response->assertSessionHasErrors(['date', 'count', 'cause', 'description']);
    }

    public function test_store_validates_count_minimum(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('app.batches.deaths.store', $this->batch), $this->validDeathData([
                'count' => 0,
            ]));

        $response->assertSessionHasErrors(['count']);
    }

    public function test_store_validates_count_does_not_exceed_current_count(): void
    {
        $batch = FlockBatch::factory()->create([
            'user_id' => $this->user->id,
            'current_count' => 5,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('app.batches.deaths.store', $batch), $this->validDeathData([
                'count' => 6,
            ]));

        $response->assertSessionHasErrors(['count']);
    }

    public function test_store_validates_cause_enum(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('app.batches.deaths.store', $this->batch), $this->validDeathData([
                'cause' => 'invalid_cause',
            ]));

        $response->assertSessionHasErrors(['cause']);
    }

    public function test_user_cannot_add_death_to_another_users_batch(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('app.batches.deaths.store', $this->otherBatch), $this->validDeathData());

        $response->assertForbidden();
    }

    public function test_store_sets_user_id_on_death_record(): void
    {
        $this->actingAs($this->user)
            ->post(route('app.batches.deaths.store', $this->batch), $this->validDeathData());

        $this->assertDatabaseHas('death_records', [
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
        ]);
    }

    // === Update ===

    public function test_user_can_update_own_death_record(): void
    {
        $death = DeathRecord::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
            'count' => 2,
        ]);
        $this->batch->update(['current_count' => 8]); // simulate decrement from store

        $response = $this->actingAs($this->user)
            ->put(route('app.batches.deaths.update', [$this->batch, $death]), $this->validDeathData([
                'count' => 2,
                'description' => 'Updated description',
            ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('death_records', [
            'id' => $death->id,
            'description' => 'Updated description',
        ]);
    }

    public function test_update_adjusts_batch_current_count(): void
    {
        // Start: current_count=10, death.count=3 → effective current=7
        $death = DeathRecord::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
            'count' => 3,
        ]);
        $this->batch->update(['current_count' => 7]);

        // Update death to count=5 → should decrement by additional 2 → current_count=5
        $this->actingAs($this->user)
            ->put(route('app.batches.deaths.update', [$this->batch, $death]), $this->validDeathData([
                'count' => 5,
            ]));

        $this->assertEquals(5, $this->batch->fresh()->current_count);
    }

    public function test_update_adjusts_count_when_reducing(): void
    {
        // Start: current_count=5, death.count=5
        $death = DeathRecord::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
            'count' => 5,
        ]);
        $this->batch->update(['current_count' => 5]);

        // Update death to count=2 → should restore 3 → current_count=8
        $this->actingAs($this->user)
            ->put(route('app.batches.deaths.update', [$this->batch, $death]), $this->validDeathData([
                'count' => 2,
            ]));

        $this->assertEquals(8, $this->batch->fresh()->current_count);
    }

    public function test_update_validates_new_count_against_available(): void
    {
        // current_count=2, death.count=3 → max allowed = 2+3 = 5
        $death = DeathRecord::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
            'count' => 3,
        ]);
        $this->batch->update(['current_count' => 2]);

        $response = $this->actingAs($this->user)
            ->put(route('app.batches.deaths.update', [$this->batch, $death]), $this->validDeathData([
                'count' => 6, // exceeds 5
            ]));

        $response->assertSessionHasErrors(['count']);
    }

    public function test_user_cannot_update_another_users_death_record(): void
    {
        $death = DeathRecord::factory()->create([
            'batch_id' => $this->otherBatch->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('app.batches.deaths.update', [$this->otherBatch, $death]), $this->validDeathData());

        $response->assertForbidden();
    }

    public function test_user_cannot_update_death_via_wrong_batch(): void
    {
        $batch2 = FlockBatch::factory()->create(['user_id' => $this->user->id]);
        $death = DeathRecord::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('app.batches.deaths.update', [$batch2, $death]), $this->validDeathData());

        $response->assertNotFound();
    }

    // === Destroy ===

    public function test_user_can_delete_own_death_record(): void
    {
        $death = DeathRecord::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
            'count' => 3,
        ]);
        $this->batch->update(['current_count' => 7]);

        $response = $this->actingAs($this->user)
            ->delete(route('app.batches.deaths.destroy', [$this->batch, $death]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('death_records', ['id' => $death->id]);
    }

    public function test_delete_restores_batch_current_count(): void
    {
        $death = DeathRecord::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
            'count' => 3,
        ]);
        $this->batch->update(['current_count' => 7]);

        $this->actingAs($this->user)
            ->delete(route('app.batches.deaths.destroy', [$this->batch, $death]));

        $this->assertEquals(10, $this->batch->fresh()->current_count);
    }

    public function test_user_can_delete_death_record_via_htmx(): void
    {
        $death = DeathRecord::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete(route('app.batches.deaths.destroy', [$this->batch, $death]));

        $response->assertOk();
        $this->assertDatabaseMissing('death_records', ['id' => $death->id]);
    }

    public function test_user_cannot_delete_another_users_death_record(): void
    {
        $death = DeathRecord::factory()->create([
            'batch_id' => $this->otherBatch->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('app.batches.deaths.destroy', [$this->otherBatch, $death]));

        $response->assertForbidden();
    }

    public function test_user_cannot_delete_death_via_wrong_batch(): void
    {
        $batch2 = FlockBatch::factory()->create(['user_id' => $this->user->id]);
        $death = DeathRecord::factory()->create([
            'batch_id' => $this->batch->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('app.batches.deaths.destroy', [$batch2, $death]));

        $response->assertNotFound();
    }
}
