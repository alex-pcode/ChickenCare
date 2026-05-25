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
            self::Birds => __('expenses.categories.birds'),
            self::Feed => __('expenses.categories.feed'),
            self::Equipment => __('expenses.categories.equipment'),
            self::Veterinary => __('expenses.categories.veterinary'),
            self::Maintenance => __('expenses.categories.maintenance'),
            self::Supplies => __('expenses.categories.supplies'),
            self::StartUp => __('expenses.categories.start_up'),
            self::Other => __('expenses.categories.other'),
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
