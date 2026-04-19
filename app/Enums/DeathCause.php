<?php

namespace App\Enums;

enum DeathCause: string
{
    case Unknown  = 'unknown';
    case Predator = 'predator';
    case Disease  = 'disease';
    case Age      = 'age';
    case Injury   = 'injury';
    case Culled   = 'culled';
    case Other    = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Unknown  => 'Unknown',
            self::Predator => 'Predator Attack',
            self::Disease  => 'Disease / Illness',
            self::Age      => 'Old Age',
            self::Injury   => 'Injury',
            self::Culled   => 'Culled',
            self::Other    => 'Other',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Unknown  => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
            self::Predator => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            self::Disease  => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
            self::Age      => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            self::Injury   => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
            self::Culled   => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
            self::Other    => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        };
    }
}
