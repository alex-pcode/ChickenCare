<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Birds = 'Birds';
    case Feed = 'Feed';
    case Equipment = 'Equipment';
    case Veterinary = 'Veterinary';
    case Maintenance = 'Maintenance';
    case Supplies = 'Supplies';
    case StartUp = 'Start-up';
    case Other = 'Other';

    public function label(): string
    {
        return match ($this) {
            self::Birds => 'Birds',
            self::Feed => 'Feed',
            self::Equipment => 'Equipment',
            self::Veterinary => 'Veterinary',
            self::Maintenance => 'Maintenance',
            self::Supplies => 'Supplies',
            self::StartUp => 'Start-up',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Birds => '#544CE6',
            self::Feed => '#2A2580',
            self::Equipment => '#191656',
            self::Veterinary => '#6B5CE6',
            self::Maintenance => '#4A3DC7',
            self::Supplies => '#8833D7',
            self::StartUp => '#66319E',
            self::Other => '#544CE6',
        };
    }
}
