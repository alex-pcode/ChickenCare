<?php

namespace App\Enums;

enum ChickenGoal: string
{
    case Hobby = 'hobby';
    case Business = 'business';

    public function label(): string
    {
        return match ($this) {
            self::Hobby => 'Hobby',
            self::Business => 'Business',
        };
    }
}
