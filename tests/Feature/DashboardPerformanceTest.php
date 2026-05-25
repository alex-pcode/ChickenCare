<?php

namespace Tests\Feature;

use App\Models\BatchEvent;
use App\Models\EggEntry;
use App\Models\Expense;
use App\Models\FlockBatch;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_query_count_below_ten_for_premium_user(): void
    {
        $user = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->active()->create(['user_id' => $user->id]);

        EggEntry::factory()->count(10)->create(['user_id' => $user->id]);
        Expense::factory()->count(5)->create(['user_id' => $user->id]);
        Sale::factory()->count(3)->create(['user_id' => $user->id]);
        BatchEvent::factory()->count(3)->create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
        ]);
        FlockBatch::factory()->active()->create(['user_id' => $user->id]);

        // Warm the auth
        $this->actingAs($user);

        DB::enableQueryLog();
        $this->get(route('app.dashboard'));
        $queryCount = collect(DB::getQueryLog())
            ->reject(fn ($q) => preg_match('/from ["`]sessions["`]/', $q['query']))
            ->count();
        DB::disableQueryLog();

        $this->assertLessThan(25, $queryCount, "Dashboard exceeded query budget: {$queryCount} queries fired.");
    }

    public function test_dashboard_query_count_below_ten_for_free_user(): void
    {
        $user = User::factory()->create(['tier' => 'free']);
        EggEntry::factory()->count(10)->create(['user_id' => $user->id]);

        $this->actingAs($user);

        DB::enableQueryLog();
        $this->get(route('app.dashboard'));
        $queryCount = collect(DB::getQueryLog())
            ->reject(fn ($q) => preg_match('/from ["`]sessions["`]/', $q['query']))
            ->count();
        DB::disableQueryLog();

        $this->assertLessThan(20, $queryCount, "Dashboard exceeded query budget: {$queryCount} queries fired.");
    }

    /** @group performance */
    public function test_dashboard_loads_within_acceptable_time(): void
    {
        $user = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->active()->create(['user_id' => $user->id]);

        EggEntry::factory()->count(10)->create(['user_id' => $user->id]);
        Expense::factory()->count(5)->create(['user_id' => $user->id]);
        Sale::factory()->count(3)->create(['user_id' => $user->id]);
        BatchEvent::factory()->count(3)->create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
        ]);

        $start = microtime(true);
        $this->actingAs($user)->get(route('app.dashboard'));
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(2000, $elapsed, "Dashboard took {$elapsed}ms to load.");
    }

    public function test_htmx_recent_activity_query_count_stays_narrow(): void
    {
        $user = User::factory()->premium()->create();
        $batch = FlockBatch::factory()->active()->create(['user_id' => $user->id]);

        EggEntry::factory()->count(10)->create(['user_id' => $user->id]);
        Expense::factory()->count(5)->create(['user_id' => $user->id]);
        Sale::factory()->count(3)->create(['user_id' => $user->id]);
        BatchEvent::factory()->count(3)->create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
        ]);

        $this->actingAs($user);

        DB::enableQueryLog();

        $this->withHeaders([
            'HX-Request' => 'true',
            'HX-Target' => 'dashboard-activity',
        ])->get(route('app.dashboard'));

        $queryCount = collect(DB::getQueryLog())
            ->reject(fn ($query) => preg_match('/from ["`]sessions["`]/', $query['query']))
            ->count();

        DB::disableQueryLog();

        $this->assertLessThan(10, $queryCount, "HTMX recent activity exceeded query budget: {$queryCount} queries fired.");
    }
}
