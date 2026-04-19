<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SavingsEdgeCaseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_savings_page_renders_for_brand_new_user_with_no_data(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get('/app/savings');

        $response->assertStatus(200);
        $response->assertSee('Financial Summary');
        $response->assertSee('You Got');
    }

    public function test_savings_shows_negative_net_when_expenses_exceed_egg_value(): void
    {
        $user = User::factory()->premium()->hobby()->withEggPrice(0.30)->create();
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 500.00, 'date' => now()]);

        $response = $this->actingAs($user)->get('/app/savings');

        $response->assertStatus(200);
        $response->assertSee('egg value to cover costs');
    }

    public function test_savings_shows_no_egg_data_fallback_when_no_eggs_recorded(): void
    {
        $user = User::factory()->premium()->create();
        Expense::factory()->create(['user_id' => $user->id, 'amount' => 50.00, 'date' => now()]);

        $response = $this->actingAs($user)->get('/app/savings');

        $response->assertStatus(200);
        $response->assertSee('No egg production data available');
    }
}
