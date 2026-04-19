<?php

namespace Tests\Unit\Views;

use Tests\TestCase;

class ExpenseIndexHeroTest extends TestCase
{
    public function test_expenses_scss_contains_all_keyframe_names(): void
    {
        $scss = file_get_contents(base_path('resources/scss/features/_expenses.scss'));

        $this->assertStringContainsString('hero-coin-entrance', $scss, 'SCSS should contain hero-coin-entrance keyframe');
        $this->assertStringContainsString('hero-coin-wobble', $scss, 'SCSS should contain hero-coin-wobble keyframe');
        $this->assertStringContainsString('hero-badge-pop', $scss, 'SCSS should contain hero-badge-pop keyframe');
        $this->assertStringContainsString('hero-welcome-slide-in', $scss, 'SCSS should contain hero-welcome-slide-in keyframe');
    }

    public function test_expenses_scss_contains_reduced_motion_media_query(): void
    {
        $scss = file_get_contents(base_path('resources/scss/features/_expenses.scss'));

        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $scss, 'SCSS should contain prefers-reduced-motion media query');
        $this->assertStringContainsString('.expenses-hero__image--animated', $scss, 'SCSS should reference .expenses-hero__image--animated in reduced motion');
    }

    public function test_hero_partial_exists_and_contains_required_strings(): void
    {
        $heroView = view('expenses.partials.hero')->render();

        $this->assertStringContainsString('chicken-coin.webp', $heroView, 'Hero should contain chicken-coin.webp');
        $this->assertStringContainsString('💰 Expense Tracker', $heroView, 'Hero should contain badge text');
        $this->assertStringContainsString('Track every expense!', $heroView, 'Hero should contain welcome text');
    }
}
