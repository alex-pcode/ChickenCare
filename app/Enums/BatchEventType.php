<?php

namespace App\Enums;

enum BatchEventType: string
{
    case HealthCheck    = 'health_check';
    case Vaccination    = 'vaccination';
    case Relocation     = 'relocation';
    case Breeding       = 'breeding';
    case LayingStart    = 'laying_start';
    case BroodingStart  = 'brooding_start';
    case BroodingStop   = 'brooding_stop';
    case ProductionNote = 'production_note';
    case FlockAdded     = 'flock_added';
    case FlockLoss      = 'flock_loss';
    case Other          = 'other';

    public function label(): string
    {
        return match ($this) {
            self::HealthCheck    => 'Health Check',
            self::Vaccination    => 'Vaccination',
            self::Relocation     => 'Relocation',
            self::Breeding       => 'Breeding',
            self::LayingStart    => 'Laying Start',
            self::BroodingStart  => 'Brooding Start',
            self::BroodingStop   => 'Brooding Stop',
            self::ProductionNote => 'Production Note',
            self::FlockAdded     => 'Flock Added',
            self::FlockLoss      => 'Flock Loss',
            self::Other          => 'Other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::HealthCheck    => '🩺',
            self::Vaccination    => '💉',
            self::Relocation     => '🏠',
            self::Breeding       => '💕',
            self::LayingStart    => '🥚',
            self::BroodingStart  => '🪺',
            self::BroodingStop   => '🐔',
            self::ProductionNote => '📝',
            self::FlockAdded     => '🎉',
            self::FlockLoss      => '💔',
            self::Other          => '📋',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
