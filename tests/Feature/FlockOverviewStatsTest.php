<?php

namespace Tests\Feature;

use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FlockOverviewStatsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_flock_index_passes_overview_stats(): void
    {
        $user = User::factory()->premium()->create();
        FlockBatch::factory()->create([
            'user_id'                  => $user->id,
            'hens_count'               => 10,
            'roosters_count'           => 2,
            'chicks_count'             => 0,
            'brooding_count'           => 0,
            'current_count'            => 12,
            'actual_laying_start_date' => now()->subMonth(),
            'is_active'                => true,
        ]);

        $response = $this->actingAs($user)->get('/app/flock');

        $response->assertOk();
        $response->assertViewHas('overviewStats', function (array $stats) {
            return $stats['laying']['total'] === 10
                && $stats['roosters']['total'] === 2
                && $stats['showBrooding'] === false;
        });
    }

    public function test_flock_index_hides_brooding_card_when_zero(): void
    {
        $user = User::factory()->premium()->create();
        FlockBatch::factory()->create([
            'user_id'        => $user->id,
            'hens_count'     => 5,
            'roosters_count' => 0,
            'chicks_count'   => 0,
            'brooding_count' => 0,
            'current_count'  => 5,
            'is_active'      => true,
        ]);

        $response = $this->actingAs($user)->get('/app/flock');

        $response->assertOk();
        $response->assertDontSee('Brooding');
    }

    public function test_flock_index_shows_brooding_card_when_positive(): void
    {
        $user = User::factory()->premium()->create();
        FlockBatch::factory()->create([
            'user_id'        => $user->id,
            'hens_count'     => 5,
            'roosters_count' => 0,
            'chicks_count'   => 0,
            'brooding_count' => 2,
            'current_count'  => 7,
            'is_active'      => true,
        ]);

        $response = $this->actingAs($user)->get('/app/flock');

        $response->assertOk();
        $response->assertSee('Brooding');
    }
}
