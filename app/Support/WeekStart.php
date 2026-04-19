<?php

namespace App\Support;

use Carbon\Carbon;

final class WeekStart
{
    public static function from(Carbon $date): Carbon
    {
        return $date->copy()->startOfWeek(Carbon::MONDAY);
    }
}
