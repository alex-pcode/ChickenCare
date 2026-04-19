<?php

namespace Tests\Feature;

use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FlockBatchControllerTest extends TestCase
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

    private function validBatchData(array $overrides = []): array
    {
        return array_merge([
            'batch_name'         => 'Spring Layers',
            'breed'              => 'Rhode Island Red',
            'acquisition_date'   => '2026-03-01',
            'hens_count'         => 8,
            'roosters_count'     => 1,
            'chicks_count'       => 1,
            'brooding_count'     => 0,
            'age_at_acquisition' => 'adult',
            'source'             => 'Local breeder',
            'cost'               => 150.00,
        ], $overrides);
    }

    private function validUpdateData(array $overrides = []): array
    {
        return array_merge([
            'batch_name'         => 'Spring Layers',
            'breed'              => 'Rhode Island Red',
            'acquisition_date'   => '2026-03-01',
            'current_count'      => 10,
            'hens_count'         => 8,
            'roosters_count'     => 1,
            'chicks_count'       => 1,
            'brooding_count'     => 0,
            'type'               => 'mixed',
            'age_at_acquisition' => 'adult',
            'source'             => 'Local breeder',
            'cost'               => 150.00,
        ], $overrides);
    }

    // === Index ===

    public function test_premium_user_can_view_batch_list(): void
    {
        FlockBatch::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get('/app/batches');

        $response->assertOk();
        $response->assertViewIs('batches.index');
    }

    public function test_free_user_is_blocked_from_batches(): void
    {
        $freeUser = User::factory()->create();

        $response = $this->actingAs($freeUser)->get('/app/batches');

        $response->assertRedirect(route('app.dashboard'));
    }

    public function test_index_shows_only_active_batches_by_default(): void
    {
        FlockBatch::factory()->active()->create(['user_id' => $this->user->id, 'batch_name' => 'Active Batch']);
        FlockBatch::factory()->archived()->create(['user_id' => $this->user->id, 'batch_name' => 'Archived Batch']);

        $response = $this->actingAs($this->user)->get('/app/batches');

        $response->assertSee('Active Batch');
        $response->assertDontSee('Archived Batch');
    }

    public function test_index_shows_archived_batches_with_filter(): void
    {
        FlockBatch::factory()->active()->create(['user_id' => $this->user->id, 'batch_name' => 'Active Batch']);
        FlockBatch::factory()->archived()->create(['user_id' => $this->user->id, 'batch_name' => 'Archived Batch']);

        $response = $this->actingAs($this->user)->get('/app/batches?filter=archived');

        $response->assertSee('Archived Batch');
        $response->assertDontSee('Active Batch');
    }

    public function test_index_shows_all_batches_with_filter(): void
    {
        FlockBatch::factory()->active()->create(['user_id' => $this->user->id, 'batch_name' => 'Active Batch']);
        FlockBatch::factory()->archived()->create(['user_id' => $this->user->id, 'batch_name' => 'Archived Batch']);

        $response = $this->actingAs($this->user)->get('/app/batches?filter=all');

        $response->assertSee('Active Batch');
        $response->assertSee('Archived Batch');
    }

    public function test_index_paginates_batches(): void
    {
        FlockBatch::factory()->count(20)->active()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get('/app/batches');

        $response->assertOk();
        $response->assertViewHas('batches', function ($batches) {
            return $batches->count() === 15 && $batches->total() === 20;
        });
    }

    public function test_htmx_request_returns_batches_table_partial(): void
    {
        FlockBatch::factory()->count(20)->active()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/batches?page=2');

        $response->assertOk();
        $response->assertViewIs('batches.partials.batches-table');
    }

    public function test_user_only_sees_own_batches(): void
    {
        FlockBatch::factory()->create(['user_id' => $this->user->id, 'batch_name' => 'My Batch']);
        FlockBatch::factory()->create(['user_id' => $this->otherUser->id, 'batch_name' => 'Other Batch']);

        $response = $this->actingAs($this->user)->get('/app/batches');

        $response->assertSee('My Batch');
        $response->assertDontSee('Other Batch');
    }

    // === Create / Store ===

    public function test_premium_user_can_view_create_form(): void
    {
        $response = $this->actingAs($this->user)->get('/app/batches/create');

        $response->assertOk();
        $response->assertViewIs('batches.create');
    }

    public function test_premium_user_can_create_batch(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/app/batches', $this->validBatchData());

        $batch = FlockBatch::where('user_id', $this->user->id)->first();
        $response->assertRedirect(route('app.batches.show', $batch));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('flock_batches', [
            'user_id'    => $this->user->id,
            'batch_name' => 'Spring Layers',
        ]);
    }

    public function test_store_derives_initial_count_and_current_count(): void
    {
        $this->actingAs($this->user)
            ->post('/app/batches', $this->validBatchData([
                'hens_count'     => 10,
                'roosters_count' => 3,
                'chicks_count'   => 0,
                'brooding_count' => 2,
            ]));

        $this->assertDatabaseHas('flock_batches', [
            'user_id'       => $this->user->id,
            'initial_count' => 15,
            'current_count' => 15,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/app/batches', []);

        $response->assertSessionHasErrors([
            'batch_name', 'breed', 'acquisition_date',
            'hens_count', 'roosters_count', 'chicks_count', 'brooding_count',
            'age_at_acquisition', 'source',
        ]);
    }

    public function test_store_validates_zero_birds(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/app/batches', $this->validBatchData([
                'hens_count'     => 0,
                'roosters_count' => 0,
                'chicks_count'   => 0,
                'brooding_count' => 0,
            ]));

        $response->assertSessionHasErrors(['hens_count']);
    }

    public function test_store_validates_age_enum(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/app/batches', $this->validBatchData(['age_at_acquisition' => 'invalid']));

        $response->assertSessionHasErrors(['age_at_acquisition']);
    }

    // === Show ===

    public function test_user_can_view_own_batch(): void
    {
        $batch = FlockBatch::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get("/app/batches/{$batch->id}");

        $response->assertOk();
        $response->assertViewIs('batches.show');
        $response->assertViewHas('batch');
    }

    public function test_user_cannot_view_another_users_batch(): void
    {
        $batch = FlockBatch::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)->get("/app/batches/{$batch->id}");

        $response->assertForbidden();
    }

    public function test_show_renders_story_5_sections(): void
    {
        $batch = FlockBatch::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get("/app/batches/{$batch->id}");

        $response->assertOk();
        $response->assertSee('Batch Composition');
        $response->assertSee('Batch Details');
        $response->assertSee('Add Timeline Event');
        $response->assertSee('Deaths');
        $response->assertSee('Back to Batches');
    }

    // === Edit / Update ===

    public function test_user_can_view_edit_form(): void
    {
        $batch = FlockBatch::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get("/app/batches/{$batch->id}/edit");

        $response->assertOk();
        $response->assertViewIs('batches.edit');
    }

    public function test_user_can_update_own_batch(): void
    {
        $batch = FlockBatch::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->put("/app/batches/{$batch->id}", $this->validUpdateData([
                'batch_name' => 'Updated Batch',
            ]));

        $response->assertRedirect(route('app.batches.show', $batch));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('flock_batches', [
            'id'         => $batch->id,
            'batch_name' => 'Updated Batch',
        ]);
    }

    public function test_user_cannot_update_another_users_batch(): void
    {
        $batch = FlockBatch::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->put("/app/batches/{$batch->id}", $this->validUpdateData());

        $response->assertForbidden();
    }

    public function test_update_validates_required_fields(): void
    {
        $batch = FlockBatch::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->put("/app/batches/{$batch->id}", []);

        $response->assertSessionHasErrors(['batch_name', 'breed', 'type']);
    }

    // === Destroy (archive) ===

    public function test_user_can_archive_own_batch(): void
    {
        $batch = FlockBatch::factory()->active()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete("/app/batches/{$batch->id}");

        $response->assertRedirect(route('app.batches.index'));
        $this->assertDatabaseHas('flock_batches', [
            'id'        => $batch->id,
            'is_active' => false,
        ]);
    }

    public function test_user_can_archive_batch_via_htmx(): void
    {
        $batch = FlockBatch::factory()->active()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete("/app/batches/{$batch->id}");

        $response->assertOk();
        $this->assertDatabaseHas('flock_batches', [
            'id'        => $batch->id,
            'is_active' => false,
        ]);
    }

    public function test_user_cannot_archive_another_users_batch(): void
    {
        $batch = FlockBatch::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)->delete("/app/batches/{$batch->id}");

        $response->assertForbidden();
    }

    public function test_archived_batch_still_exists_in_database(): void
    {
        $batch = FlockBatch::factory()->active()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)->delete("/app/batches/{$batch->id}");

        $this->assertDatabaseHas('flock_batches', ['id' => $batch->id]);
        $this->assertNotNull(FlockBatch::find($batch->id));
    }
}
