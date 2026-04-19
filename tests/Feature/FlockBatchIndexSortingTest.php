<?php

namespace Tests\Feature;

use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FlockBatchIndexSortingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_index_returns_200_for_premium_user(): void
    {
        $user = User::factory()->premium()->create();
        $response = $this->actingAs($user)->get(route('app.batches.index'));
        $response->assertStatus(200);
    }

    public function test_index_shows_active_batches(): void
    {
        $user = User::factory()->premium()->create();
        FlockBatch::factory()->create(['user_id' => $user->id, 'batch_name' => 'Test Batch', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('app.batches.index'));
        $response->assertSee('Test Batch');
    }

    public function test_index_shows_empty_state_when_no_batches(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get(route('app.batches.index'));
        $response->assertSee('No Batches Yet');
    }

    public function test_index_sorts_by_valid_column(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get(route('app.batches.index', ['sort' => 'batch_name', 'dir' => 'asc']));
        $response->assertStatus(200);
    }

    public function test_index_ignores_invalid_sort_column(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get(route('app.batches.index', ['sort' => 'evil_column', 'dir' => 'asc']));
        $response->assertStatus(200);
    }

    public function test_index_does_not_show_other_users_batches(): void
    {
        $user  = User::factory()->premium()->create();
        $other = User::factory()->premium()->create();
        FlockBatch::factory()->create(['user_id' => $other->id, 'batch_name' => 'Other Batch']);

        $response = $this->actingAs($user)->get(route('app.batches.index'));
        $response->assertDontSee('Other Batch');
    }

    public function test_htmx_request_returns_batches_table_partial(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get(route('app.batches.index', ['sort' => 'batch_name', 'dir' => 'asc']));

        $response->assertStatus(200);
        $response->assertViewIs('batches.partials.batches-table');
    }
}
