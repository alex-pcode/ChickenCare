<?php

namespace Tests\Unit\Enums;

use App\Enums\BatchAgeAtAcquisition;
use PHPUnit\Framework\TestCase;

class BatchAgeAtAcquisitionTest extends TestCase
{
    public function test_chick_label(): void
    {
        $this->assertSame('Chick (0–8 weeks)', BatchAgeAtAcquisition::Chick->label());
    }

    public function test_juvenile_label(): void
    {
        $this->assertSame('Juvenile (8–18 weeks)', BatchAgeAtAcquisition::Juvenile->label());
    }

    public function test_adult_label(): void
    {
        $this->assertSame('Adult (18+ weeks)', BatchAgeAtAcquisition::Adult->label());
    }

    public function test_has_three_cases(): void
    {
        $this->assertCount(3, BatchAgeAtAcquisition::cases());
    }
}
