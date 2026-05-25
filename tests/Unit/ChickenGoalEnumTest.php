<?php

namespace Tests\Unit;

use App\Enums\ChickenGoal;
use Tests\TestCase;

class ChickenGoalEnumTest extends TestCase
{
    public function test_hobby_case_has_correct_value(): void
    {
        $this->assertEquals('hobby', ChickenGoal::Hobby->value);
    }

    public function test_business_case_has_correct_value(): void
    {
        $this->assertEquals('business', ChickenGoal::Business->value);
    }

    public function test_hobby_label(): void
    {
        $this->assertEquals('Hobby', ChickenGoal::Hobby->label());
    }

    public function test_business_label(): void
    {
        $this->assertEquals('Business', ChickenGoal::Business->label());
    }

    public function test_can_be_created_from_value(): void
    {
        $this->assertEquals(ChickenGoal::Hobby, ChickenGoal::from('hobby'));
        $this->assertEquals(ChickenGoal::Business, ChickenGoal::from('business'));
    }

    public function test_cases_count(): void
    {
        $this->assertCount(2, ChickenGoal::cases());
    }
}
