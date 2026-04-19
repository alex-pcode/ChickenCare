<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\CrmReportsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CrmReportsServiceCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_clear_cache_for_user_forgets_revenue_keys(): void
    {
        $user = User::factory()->create();
        $service = app(CrmReportsService::class);

        // Populate cache
        $service->revenueOverview($user, 'month');
        $service->revenueOverview($user, 'year');
        $service->revenueOverview($user, 'all');

        // Verify cache is populated
        $this->assertNotNull(Cache::get("crm_revenue_{$user->id}_month__"));
        $this->assertNotNull(Cache::get("crm_revenue_{$user->id}_year__"));
        $this->assertNotNull(Cache::get("crm_revenue_{$user->id}_all__"));

        // Clear cache
        $service->clearCacheForUser($user);

        // Verify cache is cleared
        $this->assertNull(Cache::get("crm_revenue_{$user->id}_month__"));
        $this->assertNull(Cache::get("crm_revenue_{$user->id}_year__"));
        $this->assertNull(Cache::get("crm_revenue_{$user->id}_all__"));
    }

    public function test_clear_cache_does_not_affect_other_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $service = app(CrmReportsService::class);

        $service->revenueOverview($user1, 'month');
        $service->revenueOverview($user2, 'month');

        $service->clearCacheForUser($user1);

        $this->assertNull(Cache::get("crm_revenue_{$user1->id}_month__"));
        $this->assertNotNull(Cache::get("crm_revenue_{$user2->id}_month__"));
    }
}
