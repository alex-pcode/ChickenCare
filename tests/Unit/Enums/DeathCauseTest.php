<?php

namespace Tests\Unit\Enums;

use App\Enums\DeathCause;
use PHPUnit\Framework\TestCase;

class DeathCauseTest extends TestCase
{
    public function test_has_seven_cases(): void
    {
        $this->assertCount(7, DeathCause::cases());
    }

    public function test_unknown_label(): void
    {
        $this->assertSame('Unknown', DeathCause::Unknown->label());
    }

    public function test_predator_label(): void
    {
        $this->assertSame('Predator Attack', DeathCause::Predator->label());
    }

    public function test_badge_color_returns_string(): void
    {
        foreach (DeathCause::cases() as $case) {
            $this->assertIsString($case->badgeColor());
        }
    }
}
