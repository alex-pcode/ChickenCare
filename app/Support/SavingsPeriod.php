<?php

namespace App\Support;

use Carbon\Carbon;

final class SavingsPeriod
{
    public function __construct(
        public readonly string $key,
        public readonly ?Carbon $from,
        public readonly ?Carbon $to,
    ) {}

    public static function month(): self
    {
        return new self('month', now()->startOfMonth(), now()->endOfMonth());
    }

    public static function year(): self
    {
        return new self('year', now()->startOfYear(), now()->endOfYear());
    }

    public static function custom(?string $from, ?string $to): self
    {
        return new self(
            'custom',
            $from ? Carbon::parse($from)->startOfDay() : now()->subMonths(3)->startOfDay(),
            $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay(),
        );
    }

    public static function all(): self
    {
        return new self('all', null, null);
    }

    public static function fromRequest(string $period = 'month', ?string $from = null, ?string $to = null): self
    {
        return match ($period) {
            'year' => self::year(),
            'custom' => self::custom($from, $to),
            'all' => self::all(),
            default => self::month(),
        };
    }

    public function includesStartupCosts(): bool
    {
        return $this->key === 'all';
    }

    public function label(): string
    {
        return match ($this->key) {
            'month' => __('savings.periods.month'),
            'year' => __('savings.periods.year'),
            'custom' => __('savings.periods.custom'),
            'all' => __('savings.periods.all'),
            default => __('savings.periods.month'),
        };
    }
}
