<?php

namespace Tests\Feature\FlockBatches;

use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_route_returns_page_for_authenticated_owner(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->get(route('app.batches.show', $batch));

        $response->assertOk();
        $response->assertViewIs('batches.show');
        $response->assertSee($batch->batch_name);
        $response->assertSee('Batch Composition');
        $response->assertSee('Batch Details');
        $response->assertSee('Add Timeline Event');
        $response->assertSee('Deaths');
    }

    public function test_show_route_returns_403_for_wrong_user(): void
    {
        $owner = User::factory()->premium()->create();
        $other = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($owner)->create();

        $this->actingAs($other)
            ->get(route('app.batches.show', $batch))
            ->assertForbidden();
    }

    public function test_show_route_returns_404_when_batch_missing(): void
    {
        $user = User::factory()->premium()->create();

        $this->actingAs($user)
            ->get('/app/batches/999999')
            ->assertNotFound();
    }

    public function test_add_timeline_event_happy_path(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.batches.events.store', $batch), [
                'date'        => today()->format('Y-m-d'),
                'type'        => 'health_check',
                'description' => 'Annual health inspection',
            ]);

        $response->assertOk();
        $response->assertHeader('HX-Trigger');
        $this->assertStringContainsString('flock:changed', $response->headers->get('HX-Trigger'));
        $this->assertDatabaseHas('batch_events', [
            'batch_id'    => $batch->id,
            'type'        => 'health_check',
            'description' => 'Annual health inspection',
        ]);
    }

    public function test_add_timeline_event_validation_failure_missing_fields(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($user)->create();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('app.batches.events.store', $batch), [])
            ->assertUnprocessable();
    }

    public function test_add_timeline_event_validation_failure_invalid_type(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($user)->create();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('app.batches.events.store', $batch), [
                'date'        => today()->format('Y-m-d'),
                'type'        => 'invalid_type',
                'description' => 'Test',
            ])
            ->assertUnprocessable();
    }

    public function test_add_timeline_event_validation_failure_future_date(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($user)->create();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('app.batches.events.store', $batch), [
                'date'        => today()->addDay()->format('Y-m-d'),
                'type'        => 'other',
                'description' => 'Future event',
            ])
            ->assertUnprocessable();
    }

    public function test_edit_composition_updates_counts_and_recalculates_type(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($user)->create([
            'hens_count'     => 5,
            'roosters_count' => 0,
            'chicks_count'   => 0,
            'brooding_count' => 0,
            'current_count'  => 5,
            'type'           => 'hens',
        ]);

        $this->actingAs($user)
            ->patch(route('app.batches.composition', $batch), [
                'hens_count'     => 3,
                'roosters_count' => 2,
                'chicks_count'   => 0,
                'brooding_count' => 0,
            ])
            ->assertOk();

        $batch->refresh();
        $this->assertEquals(3, $batch->hens_count);
        $this->assertEquals(2, $batch->roosters_count);
        $this->assertEquals(5, $batch->current_count);
        $this->assertEquals('mixed', $batch->type);
    }

    public function test_edit_composition_rejects_all_zero_counts(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($user)->create();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('app.batches.composition', $batch), [
                'hens_count'     => 0,
                'roosters_count' => 0,
                'chicks_count'   => 0,
                'brooding_count' => 0,
            ])
            ->assertUnprocessable();
    }

    public function test_set_laying_date_happy_path(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($user)->create(['actual_laying_start_date' => null]);

        $this->actingAs($user)
            ->patch(route('app.batches.laying-date', $batch), [
                'actual_laying_start_date' => today()->format('Y-m-d'),
            ])
            ->assertOk();

        $this->assertEquals(today()->format('Y-m-d'), $batch->fresh()->actual_laying_start_date->format('Y-m-d'));
    }

    public function test_clear_laying_date_with_empty_submission(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($user)->create([
            'actual_laying_start_date' => today()->subMonths(2),
        ]);

        $this->actingAs($user)
            ->patch(route('app.batches.laying-date', $batch), [
                'actual_laying_start_date' => '',
            ])
            ->assertOk();

        $this->assertNull($batch->fresh()->actual_laying_start_date);
    }

    public function test_laying_date_cannot_be_in_the_future(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($user)->create();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('app.batches.laying-date', $batch), [
                'actual_laying_start_date' => today()->addDay()->format('Y-m-d'),
            ])
            ->assertUnprocessable();
    }

    public function test_all_mutation_routes_emit_flock_changed_trigger(): void
    {
        $user  = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->for($user)->create();

        $r1 = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.batches.events.store', $batch), [
                'date'        => today()->format('Y-m-d'),
                'type'        => 'other',
                'description' => 'Test',
            ]);
        $this->assertStringContainsString('flock:changed', $r1->headers->get('HX-Trigger', ''));

        $r2 = $this->actingAs($user)->patch(route('app.batches.composition', $batch), [
            'hens_count'     => 1,
            'roosters_count' => 0,
            'chicks_count'   => 0,
            'brooding_count' => 0,
        ]);
        $this->assertStringContainsString('flock:changed', $r2->headers->get('HX-Trigger', ''));

        $r3 = $this->actingAs($user)->patch(route('app.batches.laying-date', $batch), [
            'actual_laying_start_date' => today()->format('Y-m-d'),
        ]);
        $this->assertStringContainsString('flock:changed', $r3->headers->get('HX-Trigger', ''));
    }
}
