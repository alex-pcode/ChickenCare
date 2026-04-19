<?php

namespace Tests\Feature;

use App\Models\DeathRecord;
use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FlockBatchDeathRecordControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function validPayload(): array
    {
        return [
            'date'        => now()->format('Y-m-d'),
            'count'       => 2,
            'cause'       => 'predator',
            'description' => 'Fox got into the coop',
        ];
    }

    public function test_store_creates_death_record(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->create(['user_id' => $user->id, 'current_count' => 10]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.batches.deaths.store', $batch), $this->validPayload());

        $response->assertStatus(200);
        $this->assertDatabaseHas('death_records', ['batch_id' => $batch->id, 'count' => 2]);
    }

    public function test_store_decrements_batch_current_count(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->create(['user_id' => $user->id, 'current_count' => 10]);

        $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.batches.deaths.store', $batch), $this->validPayload());

        $this->assertEquals(8, $batch->fresh()->current_count);
    }

    public function test_store_emits_flock_changed_header(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->create(['user_id' => $user->id, 'current_count' => 10]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.batches.deaths.store', $batch), $this->validPayload());

        $this->assertTrue($response->headers->has('HX-Trigger'));
        $this->assertStringContainsString('flock:changed', $response->headers->get('HX-Trigger', ''));
    }

    public function test_store_fails_when_count_exceeds_current_count(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->create(['user_id' => $user->id, 'current_count' => 3]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true', 'Accept' => 'application/json'])
            ->post(
                route('app.batches.deaths.store', $batch),
                array_merge($this->validPayload(), ['count' => 100])
            );

        $response->assertStatus(422);
    }

    public function test_store_prevents_cross_user_batch_access(): void
    {
        $user  = User::factory()->premium()->create();
        $other = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->create(['user_id' => $other->id, 'current_count' => 10]);

        $response = $this->actingAs($user)
            ->post(route('app.batches.deaths.store', $batch), $this->validPayload());

        $response->assertStatus(403);
    }

    public function test_index_returns_death_records_for_batch(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->create(['user_id' => $user->id]);
        DeathRecord::factory()->create(['user_id' => $user->id, 'batch_id' => $batch->id]);

        $response = $this->actingAs($user)->get(route('app.batches.deaths.index', $batch));

        $response->assertStatus(200);
    }

    public function test_index_blocks_cross_user_access(): void
    {
        $user  = User::factory()->premium()->create();
        $other = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)->get(route('app.batches.deaths.index', $batch));

        $response->assertStatus(403);
    }
}
