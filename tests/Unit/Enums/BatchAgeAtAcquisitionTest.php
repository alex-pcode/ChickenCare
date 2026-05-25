<?php

namespace Tests\Unit\Enums;

use App\Enums\BatchAgeAtAcquisition;
use Tests\TestCase;

class BatchAgeAtAcquisitionTest extends TestCase
{
    public function test_chick_label(): void
    {
        $this->assertStringContainsString('Chick', BatchAgeAtAcquisition::Chick->label());
        $this->assertStringContainsString('0-8 weeks', BatchAgeAtAcquisition::Chick->label());
    }

    public function test_juvenile_label(): void
    {
        $this->assertStringContainsString('Juvenile', BatchAgeAtAcquisition::Juvenile->label());
        $this->assertStringContainsString('8-18 weeks', BatchAgeAtAcquisition::Juvenile->label());
    }

    public function test_adult_label(): void
    {
        $this->assertStringContainsString('Adult', BatchAgeAtAcquisition::Adult->label());
        $this->assertStringContainsString('18+ weeks', BatchAgeAtAcquisition::Adult->label());
    }

    public function test_has_three_cases(): void
    {
        $this->assertCount(3, BatchAgeAtAcquisition::cases());
    }
}
