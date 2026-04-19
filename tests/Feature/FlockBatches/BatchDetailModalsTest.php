<?php

namespace Tests\Feature\FlockBatches;

use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchDetailModalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_composition_modal_renders_with_aria_attributes(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('app.batches.composition-modal', $batch))
            ->assertOk()
            ->assertSee('aria-modal')
            ->assertSee('role="dialog"', false)
            ->assertSee('Edit Batch Composition');
    }

    public function test_laying_date_modal_renders_with_aria_attributes(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('app.batches.laying-date-modal', $batch))
            ->assertOk()
            ->assertSee('aria-modal')
            ->assertSee('Set Laying Date');
    }

    public function test_laying_date_modal_shows_warning_for_roosters_batch(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($user)->create(['type' => 'roosters']);

        $this->actingAs($user)
            ->get(route('app.batches.laying-date-modal', $batch))
            ->assertOk()
            ->assertSee('roosters')
            ->assertSee('unusual for this type');
    }
}
