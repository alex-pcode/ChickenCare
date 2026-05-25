<?php

namespace App\Enums;

enum FeedType: string
{
    case BabyChicks = 'Baby chicks';
    case BigChicks = 'Big chicks';
    case Both = 'Both';

    public function label(): string
    {
        return match ($this) {
            self::BabyChicks => __('feed.types.baby_chicks'),
            self::BigChicks => __('feed.types.big_chicks'),
            self::Both => __('feed.types.both'),
        };
    }
}
