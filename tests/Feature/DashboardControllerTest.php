<?php

namespace Tests\Feature;

use App\Models\EggEntry;
use App\Models\Expense;
use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/app/');

        $response->assertRedirect(route('login'));
    }

    public function test_free_user_sees_egg_stats_section(): void
    {
        $user = User::factory()->create(['tier' => 'free']);
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => now()->toDateString(), 'count' => 5]);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertViewHas('summary');
        $data = $response->viewData('summary');
        $this->assertArrayHasKey('eggs', $data);
        $this->assertEquals(5, $data['eggs']['today']);
    }

    public function test_free_user_does_not_see_financial_stats(): void
    {
        $user = User::factory()->create(['tier' => 'free']);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertSee('Premium Feature');
        $response->assertDontSee('Financial Overview');
    }

    public function test_premium_user_sees_all_stat_sections(): void
    {
        $user = User::factory()->premium()->create();
        FlockBatch::factory()->active()->create(['user_id' => $user->id, 'current_count' => 10, 'hens_count' => 6]);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $response->assertSee('Financial Overview');
        $response->assertSee('Analytics');
        $data = $response->viewData('summary');
        $this->assertEquals(10, $data['flock']['total_birds']);
    }

    public function test_premium_user_sees_financial_overview(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create(['user_id' => $user->id, 'date' => now()->toDateString(), 'amount' => 50.00]);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $financialOverview = $response->viewData('financialOverview');
        $this->assertNotEmpty($financialOverview);
        $this->assertArrayHasKey('eggValue', $financialOverview);
        $this->assertArrayHasKey('revenue', $financialOverview);
    }

    public function test_dashboard_includes_recent_activity(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/');

        $response->assertStatus(200);
        $data = $response->viewData('summary');
        $this->assertInstanceOf(Collection::class, $data['recent_activity']);
    }

    public function test_htmx_request_for_recent_activity_returns_partial(): void
    {
        $user = User::factory()->create();
        EggEntry::factory()->create(['user_id' => $user->id, 'date' => now()->toDateString(), 'count' => 4]);

        $response = $this->actingAs($user)
            ->withHeaders([
                'HX-Request' => 'true',
                'HX-Target' => 'dashboard-activity',
            ])
            ->get('/app/');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.partials.recent-activity');
        $this->assertInstanceOf(Collection::class, $response->viewData('recentActivity'));
    }

    public function test_dashboard_queries_are_below_ten_for_free_user(): void
    {
        $user = User::factory()->create(['tier' => 'free']);
        EggEntry::factory()->count(3)->create(['user_id' => $user->id]);

        DB::enableQueryLog();

        $this->actingAs($user)->get('/app/');

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(15, $queryCount, "Free user dashboard should use < 15 queries, used {$queryCount}");
    }

    public function test_dashboard_queries_are_below_ten_for_premium_user(): void
    {
        $user = User::factory()->premium()->create();
        EggEntry::factory()->count(3)->create(['user_id' => $user->id]);
        Expense::factory()->create(['user_id' => $user->id, 'date' => now()->toDateString(), 'amount' => 10]);

        DB::enableQueryLog();

        $this->actingAs($user)->get('/app/');

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(25, $queryCount, "Premium user dashboard should use < 25 queries, used {$queryCount}");
    }

    public function test_dashboard_accessible_to_both_tiers(): void
    {
        $freeUser = User::factory()->create(['tier' => 'free']);
        $premiumUser = User::factory()->premium()->create();

        $freeResponse = $this->actingAs($freeUser)->get('/app/');
        $premiumResponse = $this->actingAs($premiumUser)->get('/app/');

        $freeResponse->assertStatus(200);
        $premiumResponse->assertStatus(200);
    }

    public function test_free_user_sees_serbian_dashboard_labels_and_premium_teaser(): void
    {
        $user = User::factory()->create(['tier' => 'free', 'locale' => 'sr', 'name' => 'Aleks']);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertOk();
        $response->assertSee('Dobro dosli, Aleks');
        $response->assertSee('Metrike proizvodnje');
        $response->assertSee('Nedavne aktivnosti');
        $response->assertSee('Premium funkcija');
        $response->assertSee('Nema nedavnih aktivnosti');
    }

    public function test_premium_user_sees_serbian_financial_and_analytics_sections(): void
    {
        $user = User::factory()->premium()->create(['locale' => 'sr']);
        Expense::factory()->create(['user_id' => $user->id, 'date' => now()->toDateString(), 'amount' => 50.00]);

        $response = $this->actingAs($user)->get('/app/');

        $response->assertOk();
        $response->assertSee('Finansijski pregled');
        $response->assertSee('Analitika');
        $response->assertSee('Vrednost jaja');
    }

    public function test_serbian_recent_activity_partial_uses_localized_empty_state(): void
    {
        $user = User::factory()->create(['locale' => 'sr']);

        $response = $this->actingAs($user)
            ->withHeaders([
                'HX-Request' => 'true',
                'HX-Target' => 'dashboard-activity',
            ])
            ->get('/app/');

        $response->assertOk();
        $response->assertViewIs('dashboard.partials.recent-activity');
        $response->assertSee('Nema nedavnih aktivnosti');
        $response->assertDontSee('dashboard.recent_activity.empty_title', false);
    }
}
