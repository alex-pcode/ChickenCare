<?php

namespace Tests\Unit;

use App\Support\SavingsPeriod;
use Carbon\Carbon;
use Tests\TestCase;

class SavingsPeriodTest extends TestCase
{
    public function test_month_returns_current_month_range(): void
    {
        Carbon::setTestNow('2026-04-15');

        $period = SavingsPeriod::month();

        $this->assertSame('month', $period->key);
        $this->assertSame('2026-04-01', $period->from->format('Y-m-d'));
        $this->assertSame('2026-04-30', $period->to->format('Y-m-d'));

        Carbon::setTestNow();
    }

    public function test_year_returns_current_year_range(): void
    {
        Carbon::setTestNow('2026-04-15');

        $period = SavingsPeriod::year();

        $this->assertSame('year', $period->key);
        $this->assertSame('2026-01-01', $period->from->format('Y-m-d'));
        $this->assertSame('2026-12-31', $period->to->format('Y-m-d'));

        Carbon::setTestNow();
    }

    public function test_custom_parses_provided_dates(): void
    {
        $period = SavingsPeriod::custom('2026-01-01', '2026-03-31');

        $this->assertSame('custom', $period->key);
        $this->assertSame('2026-01-01', $period->from->format('Y-m-d'));
        $this->assertSame('2026-03-31', $period->to->format('Y-m-d'));
    }

    public function test_custom_defaults_when_null(): void
    {
        Carbon::setTestNow('2026-04-15');

        $period = SavingsPeriod::custom(null, null);

        $this->assertSame('custom', $period->key);
        $this->assertSame('2026-01-15', $period->from->format('Y-m-d'));
        $this->assertSame('2026-04-15', $period->to->format('Y-m-d'));

        Carbon::setTestNow();
    }

    public function test_all_returns_null_dates(): void
    {
        $period = SavingsPeriod::all();

        $this->assertSame('all', $period->key);
        $this->assertNull($period->from);
        $this->assertNull($period->to);
    }

    public function test_from_request_month(): void
    {
        $period = SavingsPeriod::fromRequest('month');

        $this->assertSame('month', $period->key);
    }

    public function test_from_request_year(): void
    {
        $period = SavingsPeriod::fromRequest('year');

        $this->assertSame('year', $period->key);
    }

    public function test_from_request_custom(): void
    {
        $period = SavingsPeriod::fromRequest('custom', '2026-01-01', '2026-06-30');

        $this->assertSame('custom', $period->key);
        $this->assertSame('2026-01-01', $period->from->format('Y-m-d'));
        $this->assertSame('2026-06-30', $period->to->format('Y-m-d'));
    }

    public function test_from_request_all(): void
    {
        $period = SavingsPeriod::fromRequest('all');

        $this->assertSame('all', $period->key);
        $this->assertNull($period->from);
    }

    public function test_from_request_defaults_to_month(): void
    {
        $period = SavingsPeriod::fromRequest('invalid');

        $this->assertSame('month', $period->key);
    }

    public function test_includes_startup_costs_only_for_all(): void
    {
        $this->assertTrue(SavingsPeriod::all()->includesStartupCosts());
        $this->assertFalse(SavingsPeriod::month()->includesStartupCosts());
        $this->assertFalse(SavingsPeriod::year()->includesStartupCosts());
        $this->assertFalse(SavingsPeriod::custom('2026-01-01', '2026-03-31')->includesStartupCosts());
    }

    public function test_label_returns_correct_strings(): void
    {
        $this->assertSame('This Month', SavingsPeriod::month()->label());
        $this->assertSame('This Year', SavingsPeriod::year()->label());
        $this->assertSame('Custom Period', SavingsPeriod::custom('2026-01-01', '2026-03-31')->label());
        $this->assertSame('All Time', SavingsPeriod::all()->label());
    }
}
