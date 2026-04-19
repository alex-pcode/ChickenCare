<?php

namespace Tests\Feature;

use App\Enums\ChickenGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SavingsPreferencesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_can_update_savings_preferences(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->patch(route('app.savings.preferences.update'), [
            'egg_price' => 0.50,
            'chicken_goal' => 'business',
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertEquals('0.50', $user->egg_price);
        $this->assertEquals(ChickenGoal::Business, $user->chicken_goal);
    }

    public function test_validates_egg_price_minimum(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->patch(route('app.savings.preferences.update'), [
            'egg_price' => -1,
            'chicken_goal' => 'hobby',
        ]);

        $response->assertSessionHasErrors('egg_price');
    }

    public function test_validates_egg_price_maximum(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->patch(route('app.savings.preferences.update'), [
            'egg_price' => 1000.00,
            'chicken_goal' => 'hobby',
        ]);

        $response->assertSessionHasErrors('egg_price');
    }

    public function test_validates_chicken_goal_must_be_valid(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->patch(route('app.savings.preferences.update'), [
            'egg_price' => 0.30,
            'chicken_goal' => 'invalid',
        ]);

        $response->assertSessionHasErrors('chicken_goal');
    }

    public function test_unauthenticated_user_cannot_update_preferences(): void
    {
        $response = $this->patch(route('app.savings.preferences.update'), [
            'egg_price' => 0.30,
            'chicken_goal' => 'hobby',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_free_user_cannot_update_preferences(): void
    {
        $user = User::factory()->create(['tier' => 'free']);

        $response = $this->actingAs($user)->patch(route('app.savings.preferences.update'), [
            'egg_price' => 0.30,
            'chicken_goal' => 'hobby',
        ]);

        $response->assertRedirect(route('app.dashboard'));
    }

    public function test_defaults_when_never_set(): void
    {
        $user = User::factory()->premium()->create();

        $user->refresh();
        $this->assertEquals('0.30', $user->egg_price);
        $this->assertEquals(ChickenGoal::Hobby, $user->chicken_goal);
    }

    public function test_htmx_update_returns_no_redirect(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->patch(route('app.savings.preferences.update'), [
                'egg_price' => 0.75,
                'chicken_goal' => 'business',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('0.75', $user->fresh()->egg_price);
    }
}
