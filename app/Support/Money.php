<?php

namespace App\Support;

final class Money
{
    public static function usd(float|int|string $amount): string
    {
        return '$' . number_format((float) $amount, 2, '.', ',');
    }
}

