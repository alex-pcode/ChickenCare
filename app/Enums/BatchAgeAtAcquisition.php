<?php

namespace App\Enums;

enum BatchAgeAtAcquisition: string
{
    case Chick = 'chick';
    case Juvenile = 'juvenile';
    case Adult = 'adult';

    public function label(): string
    {
        return match ($this) {
            self::Chick => __('batches.age.chick'),
            self::Juvenile => __('batches.age.juvenile'),
            self::Adult => __('batches.age.adult'),
        };
    }
}
