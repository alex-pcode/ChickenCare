<?php

namespace Tests\Unit;

use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FlockBatchCurrentCountTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function createBatch(int $currentCount = 10): FlockBatch
    {
        return FlockBatch::factory()->create([
            'user_id' => User::factory()->premium()->create()->id,
            'initial_count' => 10,
            'current_count' => $currentCount,
        ]);
    }

    public function test_decrement_reduces_current_count(): void
    {
        $batch = $this->createBatch(10);

        $batch->decrement('current_count', 3);

        $this->assertEquals(7, $batch->fresh()->current_count);
    }

    public function test_increment_restores_current_count(): void
    {
        $batch = $this->createBatch(7);

        $batch->increment('current_count', 3);

        $this->assertEquals(10, $batch->fresh()->current_count);
    }

    public function test_decrement_to_zero_is_valid(): void
    {
        $batch = $this->createBatch(5);

        $batch->decrement('current_count', 5);

        $this->assertEquals(0, $batch->fresh()->current_count);
    }

    public function test_multiple_decrements_accumulate(): void
    {
        $batch = $this->createBatch(10);

        $batch->decrement('current_count', 2);
        $batch->decrement('current_count', 3);

        $this->assertEquals(5, $batch->fresh()->current_count);
    }

    public function test_update_death_reverses_and_reapplies(): void
    {
        $batch = $this->createBatch(7); // after initial death of 3

        // Simulate update: increment old count, decrement new count
        $batch->increment('current_count', 3); // restore old death
        $batch->decrement('current_count', 5); // apply new death

        $this->assertEquals(5, $batch->fresh()->current_count);
    }
}
