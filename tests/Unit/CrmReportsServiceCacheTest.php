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

    public function test_clear_cache_for_user_bumps_cache_version(): void
    {
        $user = User::factory()->create();
        $service = app(CrmReportsService::class);

        $service->revenueOverview($user, 'month');

        $this->assertSame(1, Cache::get("crm_cache_version_{$user->id}", 1));

        $service->clearCacheForUser($user);

        $this->assertSame(2, Cache::get("crm_cache_version_{$user->id}"));
    }

    public function test_clear_cache_does_not_affect_other_users_versions(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $service = app(CrmReportsService::class);

        $service->revenueOverview($user1, 'month');
        $service->revenueOverview($user2, 'month');

        $service->clearCacheForUser($user1);

        $this->assertSame(2, Cache::get("crm_cache_version_{$user1->id}"));
        $this->assertSame(1, Cache::get("crm_cache_version_{$user2->id}", 1));
    }
}
