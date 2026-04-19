<?php

namespace Tests\Feature;

use App\Models\EggEntry;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SavingsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_premium_user_can_view_savings_page(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/savings');

        $response->assertStatus(200);
        $response->assertViewIs('savings.index');
        $response->assertViewHas('summary');
        $response->assertViewHas('period');
        $response->assertViewHas('analysis');
        $response->assertViewHas('lifetime');
    }

    public function test_free_user_cannot_access_savings(): void
    {
        $user = User::factory()->create(['tier' => 'free']);

        $response = $this->actingAs($user)->get('/app/savings');

        $response->assertRedirect(route('app.dashboard'));
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/app/savings');

        $response->assertRedirect(route('login'));
    }

    public function test_savings_page_shows_financial_data(): void
    {
        Carbon::setTestNow('2026-04-15');
        $user = User::factory()->premium()->hobby()->withEggPrice(0.50)->create();

        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-10',
            'count' => 24,
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-05',
            'amount' => 50.00,
        ]);

        $response = $this->actingAs($user)->get('/app/savings');

        $response->assertStatus(200);
        $response->assertViewHas('summary');
        $summary = $response->viewData('summary');
        $this->assertSame(24, $summary['totalEggs']);
        $this->assertEqualsWithDelta(50.00, $summary['totalExpenses'], 0.01);

        Carbon::setTestNow();
    }

    public function test_savings_page_shows_empty_state(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/savings');

        $response->assertStatus(200);
        $summary = $response->viewData('summary');
        $this->assertSame(0, $summary['totalEggs']);
    }

    public function test_savings_period_filter_works(): void
    {
        Carbon::setTestNow('2026-04-15');
        $user = User::factory()->premium()->hobby()->withEggPrice(0.30)->create();

        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'count' => 10,
        ]);
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-10',
            'count' => 5,
        ]);

        // Month filter should only show April
        $response = $this->actingAs($user)->get('/app/savings?period=month');
        $response->assertStatus(200);
        $summary = $response->viewData('summary');
        $this->assertSame(5, $summary['totalEggs']);

        // Year filter should show both
        $response = $this->actingAs($user)->get('/app/savings?period=year');
        $summary = $response->viewData('summary');
        $this->assertSame(15, $summary['totalEggs']);

        Carbon::setTestNow();
    }

    public function test_htmx_request_returns_partial(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/savings?period=month');

        $response->assertStatus(200);
        $response->assertViewIs('savings.partials.financial-summary');
    }

    public function test_custom_period_defaults(): void
    {
        Carbon::setTestNow('2026-04-15');
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/savings?period=custom');

        $response->assertStatus(200);
        $period = $response->viewData('period');
        $this->assertSame('custom', $period->key);
        $this->assertNotNull($period->from);
        $this->assertNotNull($period->to);

        Carbon::setTestNow();
    }

    public function test_goal_aware_hobby_copy(): void
    {
        $user = User::factory()->premium()->hobby()->create();

        $response = $this->actingAs($user)->get('/app/savings');

        $response->assertStatus(200);
        $response->assertSee('Financial Summary');
        $response->assertSee('You Saved');
        $response->assertSee('Net Savings');
    }

    public function test_goal_aware_business_copy(): void
    {
        $user = User::factory()->premium()->business()->create();

        $response = $this->actingAs($user)->get('/app/savings');

        $response->assertStatus(200);
        $response->assertSee('Business Performance');
        $response->assertSee('You Earned');
        $response->assertSee('Net Profit');
    }

    public function test_all_period_includes_all_data(): void
    {
        Carbon::setTestNow('2026-04-15');
        $user = User::factory()->premium()->hobby()->withEggPrice(0.30)->create();

        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2025-01-15',
            'count' => 10,
        ]);
        EggEntry::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-10',
            'count' => 5,
        ]);

        $response = $this->actingAs($user)->get('/app/savings?period=all');
        $response->assertStatus(200);
        $summary = $response->viewData('summary');
        $this->assertSame(15, $summary['totalEggs']);

        Carbon::setTestNow();
    }
}
