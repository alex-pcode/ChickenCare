<?php

namespace Tests\Feature;

use App\Models\EggEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDataEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function testProductionSectionReturnsData(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => now()->toDateString(), 'count' => 10]);

        $response = $this->actingAs($user)->getJson('/app/dashboard/data?section=production');

        $response->assertOk();
        $response->assertJsonStructure([
            'production' => ['totalEggs', 'dailyAverage', 'last7DaysTotal', 'previous7DaysTotal'],
        ]);
    }

    public function testFinancialSectionRequiresPremium(): void
    {
        $user = User::factory()->create(['tier' => 'free']);

        $response = $this->actingAs($user)->getJson('/app/dashboard/data?section=financial');

        $response->assertOk();
        $response->assertJsonMissing(['financial']);
    }

    public function testAllSectionReturnsEverything(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->getJson('/app/dashboard/data?section=all');

        $response->assertOk();
        $response->assertJsonStructure([
            'production',
            'financial',
            'analytics',
            'onboarding',
        ]);
    }

    public function testUnauthenticatedRedirects(): void
    {
        $response = $this->getJson('/app/dashboard/data');

        $response->assertUnauthorized();
    }
}
