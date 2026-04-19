<?php

namespace Tests\Unit\Enums;

use App\Enums\BatchEventType;
use PHPUnit\Framework\TestCase;

class BatchEventTypeTest extends TestCase
{
    public function test_has_eleven_cases(): void
    {
        $this->assertCount(11, BatchEventType::cases());
    }

    public function test_label_returns_correct_string_for_all_cases(): void
    {
        $expected = [
            [BatchEventType::HealthCheck,    'Health Check'],
            [BatchEventType::Vaccination,    'Vaccination'],
            [BatchEventType::Relocation,     'Relocation'],
            [BatchEventType::Breeding,       'Breeding'],
            [BatchEventType::LayingStart,    'Laying Start'],
            [BatchEventType::BroodingStart,  'Brooding Start'],
            [BatchEventType::BroodingStop,   'Brooding Stop'],
            [BatchEventType::ProductionNote, 'Production Note'],
            [BatchEventType::FlockAdded,     'Flock Added'],
            [BatchEventType::FlockLoss,      'Flock Loss'],
            [BatchEventType::Other,          'Other'],
        ];

        foreach ($expected as [$case, $label]) {
            $this->assertSame($label, $case->label());
        }
    }

    public function test_icon_returns_emoji_for_all_cases(): void
    {
        foreach (BatchEventType::cases() as $case) {
            $this->assertNotEmpty($case->icon(), "icon() returned empty for {$case->value}");
        }
    }

    public function test_values_returns_array_of_all_eleven_values(): void
    {
        $values = BatchEventType::values();
        $this->assertCount(11, $values);
        $this->assertContains('health_check', $values);
        $this->assertContains('other', $values);
    }
}
