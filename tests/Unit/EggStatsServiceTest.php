<?php

namespace Tests\Unit;

use App\Models\EggEntry;
use App\Models\User;
use App\Services\EggStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EggStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private EggStatsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EggStatsService;
    }

    public function test_returns_all_zeros_when_no_entries(): void
    {
        $user = User::factory()->create();

        $stats = $this->service->getStats($user);

        $this->assertEquals(0, $stats['totalEggs']);
        $this->assertEquals(0, $stats['averageDaily']);
        $this->assertEquals(0, $stats['thisWeekTotal']);
        $this->assertEquals(0, $stats['previousWeekTotal']);
        $this->assertEquals(0, $stats['thisMonthTotal']);
        $this->assertEquals(0, $stats['previousMonthTotal']);
        $this->assertEquals(0, $stats['proteinLbs']);
        $this->assertNull($stats['layRate']);
        $this->assertNull($stats['layingHens']);
    }

    public function test_computes_total_and_average_across_multiple_days(): void
    {
        $user = User::factory()->create();

        EggEntry::factory()->create(['user_id' => $user->id, 'date' => '2026-04-01', 'count' => 4]);
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => '2026-04-02', 'count' => 6]);
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => '2026-04-02', 'count' => 2]);

        $stats = $this->service->getStats($user);

        $this->assertEquals(12, $stats['totalEggs']);
        // 12 eggs / 2 distinct days = 6.0
        $this->assertEquals(6.0, $stats['averageDaily']);
    }

    public function test_week_scoping_with_current_and_previous_week(): void
    {
        $user = User::factory()->create();

        // Current week entry
        $thisWeekDate = now()->startOfWeek()->addDay();
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => $thisWeekDate->format('Y-m-d'),
            'count' => 5,
        ]);

        // Previous week entry
        $prevWeekDate = now()->subWeek()->startOfWeek()->addDay();
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => $prevWeekDate->format('Y-m-d'),
            'count' => 3,
        ]);

        $stats = $this->service->getStats($user);

        $this->assertEquals(5, $stats['thisWeekTotal']);
        $this->assertEquals(3, $stats['previousWeekTotal']);
    }

    public function test_month_scoping_with_current_and_previous_month(): void
    {
        $user = User::factory()->create();

        // Current month entry
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => now()->startOfMonth()->addDays(2)->format('Y-m-d'),
            'count' => 10,
        ]);

        // Previous month entry
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subMonth()->startOfMonth()->addDays(2)->format('Y-m-d'),
            'count' => 7,
        ]);

        $stats = $this->service->getStats($user);

        $this->assertEquals(10, $stats['thisMonthTotal']);
        $this->assertEquals(7, $stats['previousMonthTotal']);
    }

    public function test_protein_calculation(): void
    {
        $user = User::factory()->create();

        EggEntry::factory()->create(['user_id' => $user->id, 'date' => '2026-04-01', 'count' => 80]);

        $stats = $this->service->getStats($user);

        // 80 * 0.125 = 10
        $this->assertEquals(10, $stats['proteinLbs']);
    }

    public function test_lay_rate_returns_null_placeholder(): void
    {
        $user = User::factory()->create();

        EggEntry::factory()->create(['user_id' => $user->id, 'date' => '2026-04-01', 'count' => 5]);

        $stats = $this->service->getStats($user);

        $this->assertNull($stats['layRate']);
        $this->assertNull($stats['layingHens']);
    }

    public function test_does_not_include_other_users_entries(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        EggEntry::factory()->create(['user_id' => $userA->id, 'date' => '2026-04-01', 'count' => 5]);
        EggEntry::factory()->create(['user_id' => $userB->id, 'date' => '2026-04-01', 'count' => 100]);

        $stats = $this->service->getStats($userA);

        $this->assertEquals(5, $stats['totalEggs']);
    }
}
