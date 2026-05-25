<?php

namespace App\Enums;

enum ChickenGoal: string
{
    case Hobby = 'hobby';
    case Business = 'business';

    public function label(): string
    {
        return match ($this) {
            self::Hobby => __('savings.goals.hobby'),
            self::Business => __('savings.goals.business'),
        };
    }
}
