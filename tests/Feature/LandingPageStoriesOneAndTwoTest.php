<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LandingPageStoriesOneAndTwoTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_landing_page_renders_story_one_and_two_sections_for_guests(): void
    {
        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('class="landing"', false);
        $response->assertSee('Turn Chicken Chaos', false);
        $response->assertSee('Stop Flying Blind with Your Flock', false);
        $response->assertSee('Who is this', false);
        $response->assertSee(route('costs'), false);
        $response->assertSee(route('login'), false);
        $response->assertSee(route('register'), false);
        $response->assertSee('aria-label="Open fullscreen dashboard screenshot"', false);
    }

    public function test_landing_page_shows_dashboard_link_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('landing'));

        $response->assertOk();
        $response->assertSee(route('app.dashboard'), false);
        $response->assertDontSee('>Login<', false);
        $response->assertDontSee('>Get Started<', false);
    }

    public function test_costs_page_renders_public_pricing_content(): void
    {
        $response = $this->get(route('costs'));

        $response->assertOk();
        $response->assertSee('Know Your', false);
        $response->assertSee('Choose Your Plan', false);
        $response->assertSee(route('register'), false);
    }
}
