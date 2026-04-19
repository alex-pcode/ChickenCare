<?php

namespace Tests\Unit\Services;

use App\Models\DeathRecord;
use App\Models\FlockBatch;
use App\Models\User;
use App\Services\FlockBatchStatsService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FlockBatchStatsServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private FlockBatchStatsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FlockBatchStatsService;
    }

    public function test_laying_total_sums_correctly(): void
    {
        $user = User::factory()->premium()->create();

        FlockBatch::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'actual_laying_start_date' => '2026-01-01',
            'hens_count' => 10,
            'brooding_count' => 2,
        ]);

        FlockBatch::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'actual_laying_start_date' => '2026-02-01',
            'hens_count' => 8,
            'brooding_count' => 0,
        ]);

        $stats = $this->service->overview($user);

        // (10 - 2) + (8 - 0) = 16
        $this->assertEquals(16, $stats['laying']['total']);
    }

    public function test_laying_excludes_batches_without_laying_date(): void
    {
        $user = User::factory()->premium()->create();

        FlockBatch::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'actual_laying_start_date' => null,
            'hens_count' => 10,
            'brooding_count' => 0,
        ]);

        $stats = $this->service->overview($user);

        $this->assertEquals(0, $stats['laying']['total']);
    }

    public function test_not_laying_counts_batches_without_laying_date_that_have_hens(): void
    {
        $user = User::factory()->premium()->create();

        FlockBatch::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'actual_laying_start_date' => null,
            'hens_count' => 6,
            'brooding_count' => 1,
            'type' => 'hens',
        ]);

        // This one has a laying date — should NOT be in notLaying
        FlockBatch::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'actual_laying_start_date' => '2026-01-01',
            'hens_count' => 5,
            'brooding_count' => 0,
            'type' => 'hens',
        ]);

        $stats = $this->service->overview($user);

        // (6 - 1) = 5
        $this->assertEquals(5, $stats['notLaying']['total']);
        $this->assertEquals(1, $stats['notLaying']['label'][0]);
    }

    public function test_show_brooding_is_true_when_brooding_count_greater_than_zero(): void
    {
        $user = User::factory()->premium()->create();

        FlockBatch::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'brooding_count' => 3,
        ]);

        $stats = $this->service->overview($user);

        $this->assertTrue($stats['showBrooding']);
    }

    public function test_show_brooding_is_false_when_no_brooding(): void
    {
        $user = User::factory()->premium()->create();

        FlockBatch::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'brooding_count' => 0,
        ]);

        $stats = $this->service->overview($user);

        $this->assertFalse($stats['showBrooding']);
    }

    public function test_tab_counts_returns_correct_counts(): void
    {
        $user = User::factory()->premium()->create();

        FlockBatch::factory()->count(3)->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $counts = $this->service->tabCounts($user);

        $this->assertEquals(3, $counts['batches']);
        $this->assertArrayHasKey('deaths', $counts);
        $this->assertNull($counts['addBatch']);
    }

    public function test_metric_display_stats(): void
    {
        $user = User::factory()->premium()->create();

        $batch1 = FlockBatch::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'current_count' => 10,
            'actual_laying_start_date' => '2026-01-01',
        ]);

        FlockBatch::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'current_count' => 5,
            'actual_laying_start_date' => null,
        ]);

        DeathRecord::factory()->create([
            'batch_id' => $batch1->id,
            'count' => 2,
        ]);

        $stats = $this->service->metricDisplayStats($user);

        $this->assertEquals(2, $stats['totalBatches']);
        $this->assertEquals(15, $stats['totalBirds']);
        $this->assertEquals(1, $stats['layingBatches']);
        $this->assertEquals(2, $stats['totalLosses']);
    }
}

