<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageStoriesThreeAndFourTest extends TestCase
{
    public function test_landing_page_renders_story_three_and_four_sections(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Everything You Need to', false);
        $response->assertSee('Trusted by 25 Chickens', false);
        $response->assertSee('Head of Laying Operations', false);
        $response->assertSee('Choose Your Plan', false);
        $response->assertSee('$5/month', false);
        $response->assertSee('A Message from Your Chickens', false);
        $response->assertSee('Yes, My Chickens Deserve Recognition! 🐓', false);
        $response->assertSee('id="pricing"', false);
        $response->assertSee(route('register'), false);
    }

    public function test_landing_page_has_story_three_alpine_component_hooks(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('x-data="featureCarousel(2)"', false);
        $response->assertSee('x-data="fullscreenModal()"', false);
        $response->assertSee('@open-fullscreen.window="openModal($event.detail)"', false);
        $response->assertSee('@keydown.tab.window="trapFocus($event)"', false);
        $response->assertSee('role="button"', false);
        $response->assertSee('@keydown.enter.prevent="$dispatch(\'open-fullscreen\'', false);
    }
}
