<?php

namespace Tests\Unit;

use App\Support\WeekStart;
use Carbon\Carbon;
use Tests\TestCase;

class WeekStartTest extends TestCase
{
    public function testMondayReturnsMonday(): void
    {
        $monday = Carbon::create(2026, 4, 13); // Monday

        $result = WeekStart::from($monday);

        $this->assertTrue($result->isMonday());
        $this->assertSame('2026-04-13', $result->toDateString());
    }

    public function testWednesdayReturnsPreviousMonday(): void
    {
        $wednesday = Carbon::create(2026, 4, 15); // Wednesday

        $result = WeekStart::from($wednesday);

        $this->assertTrue($result->isMonday());
        $this->assertSame('2026-04-13', $result->toDateString());
    }

    public function testSundayReturnsPreviousMonday(): void
    {
        $sunday = Carbon::create(2026, 4, 19); // Sunday

        $result = WeekStart::from($sunday);

        $this->assertTrue($result->isMonday());
        $this->assertSame('2026-04-13', $result->toDateString());
    }
}
