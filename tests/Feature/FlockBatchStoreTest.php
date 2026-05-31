<?php

namespace Tests\Feature;

use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FlockBatchStoreTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function validPayload(): array
    {
        return [
            'batch_name' => 'Test Batch',
            'breed' => 'Rhode Island Red',
            'hens_count' => 10,
            'brooding_count' => 0,
            'roosters_count' => 0,
            'chicks_count' => 0,
            'age_at_acquisition' => 'adult',
            'acquisition_date' => now()->format('Y-m-d'),
            'source' => 'Local Farm',
            'cost' => 50.00,
        ];
    }

    public function test_store_creates_batch_for_premium_user(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->post(route('app.batches.store'), $this->validPayload());

        $batch = FlockBatch::where('user_id', $user->id)->first();
        $this->assertNotNull($batch);
        $response->assertRedirect(route('app.batches.show', $batch));
        $this->assertDatabaseHas('flock_batches', [
            'user_id' => $user->id,
            'batch_name' => 'Test Batch',
        ]);
    }

    public function test_store_derives_type_as_hens_for_hen_only_batch(): void
    {
        $user = User::factory()->premium()->create();

        $this->actingAs($user)->post(route('app.batches.store'), $this->validPayload());

        $this->assertDatabaseHas('flock_batches', [
            'user_id' => $user->id,
            'type' => 'hens',
        ]);
    }

    public function test_store_derives_type_as_mixed_for_mixed_batch(): void
    {
        $user = User::factory()->premium()->create();
        $payload = array_merge($this->validPayload(), ['roosters_count' => 2]);

        $this->actingAs($user)->post(route('app.batches.store'), $payload);

        $this->assertDatabaseHas('flock_batches', ['user_id' => $user->id, 'type' => 'mixed']);
    }

    public function test_store_sets_initial_count_and_current_count(): void
    {
        $user = User::factory()->premium()->create();
        $payload = array_merge($this->validPayload(), ['hens_count' => 5, 'roosters_count' => 2, 'chicks_count' => 3]);

        $this->actingAs($user)->post(route('app.batches.store'), $payload);

        $batch = FlockBatch::where('user_id', $user->id)->first();
        $this->assertEquals(10, $batch->initial_count);
        $this->assertEquals(10, $batch->current_count);
    }

    public function test_store_creates_acquired_timeline_event(): void
    {
        $user = User::factory()->premium()->create();

        $this->actingAs($user)->post(route('app.batches.store'), $this->validPayload());

        $batch = FlockBatch::where('user_id', $user->id)->first();

        $this->assertDatabaseHas('batch_events', [
            'batch_id' => $batch->id,
            'user_id' => $user->id,
            'type' => 'flock_added',
            'affected_count' => 10,
            'description' => 'Acquired 10 birds from Local Farm',
        ]);

        $event = $batch->batchEvents()->first();
        $this->assertEquals(
            $batch->acquisition_date->toDateString(),
            $event->date->toDateString(),
        );
    }

    public function test_store_creates_flock_acquisition_event(): void
    {
        $user = User::factory()->premium()->create();

        $this->actingAs($user)->post(route('app.batches.store'), $this->validPayload());

        $batch = FlockBatch::where('user_id', $user->id)->first();
        $profile = $user->fresh()->flockProfile;

        $this->assertNotNull($profile);
        $this->assertDatabaseHas('flock_events', [
            'flock_profile_id' => $profile->id,
            'type' => 'acquisition',
            'affected_birds' => 10,
            'description' => 'Acquired 10 birds from Local Farm',
        ]);

        $event = $profile->flockEvents()->first();
        $this->assertEquals(
            $batch->acquisition_date->toDateString(),
            $event->date->toDateString(),
        );
    }

    public function test_store_reuses_existing_flock_profile_for_acquisition_event(): void
    {
        $user = User::factory()->premium()->create();
        $profile = $user->flockProfile()->create();

        $this->actingAs($user)->post(route('app.batches.store'), $this->validPayload());

        $this->assertEquals(1, $user->fresh()->flockProfile()->count());
        $this->assertEquals(1, $profile->flockEvents()->count());
    }

    public function test_store_logs_cost_as_birds_expense(): void
    {
        $user = User::factory()->premium()->create();

        $this->actingAs($user)->post(route('app.batches.store'), $this->validPayload());

        $batch = FlockBatch::where('user_id', $user->id)->first();

        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'category' => 'Birds',
            'amount' => '50.00',
            'description' => 'Purchase of batch "Test Batch" (10 birds from Local Farm)',
        ]);

        $expense = $user->expenses()->first();
        $this->assertEquals(
            $batch->acquisition_date->toDateString(),
            $expense->date->toDateString(),
        );
    }

    public function test_store_does_not_log_expense_when_cost_is_zero(): void
    {
        $user = User::factory()->premium()->create();
        $payload = array_merge($this->validPayload(), ['cost' => 0]);

        $this->actingAs($user)->post(route('app.batches.store'), $payload);

        $this->assertDatabaseMissing('expenses', ['user_id' => $user->id]);
    }

    public function test_store_does_not_log_expense_when_cost_omitted(): void
    {
        $user = User::factory()->premium()->create();
        $payload = $this->validPayload();
        unset($payload['cost']);

        $this->actingAs($user)->post(route('app.batches.store'), $payload);

        $this->assertDatabaseMissing('expenses', ['user_id' => $user->id]);
    }

    public function test_store_fails_validation_when_zero_birds(): void
    {
        $user = User::factory()->premium()->create();
        $payload = array_merge($this->validPayload(), ['hens_count' => 0]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.batches.store'), $payload);

        $response->assertStatus(422);
    }

    public function test_store_fails_validation_for_future_acquisition_date(): void
    {
        $user = User::factory()->premium()->create();
        $payload = array_merge($this->validPayload(), ['acquisition_date' => now()->addDays(5)->format('Y-m-d')]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.batches.store'), $payload);

        $response->assertStatus(422);
    }

    public function test_store_htmx_redirect_emits_hx_location(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.batches.store'), $this->validPayload());

        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('HX-Redirect'));
    }
}
