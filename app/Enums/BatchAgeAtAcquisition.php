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
            self::Chick    => 'Chick (0–8 weeks)',
            self::Juvenile => 'Juvenile (8–18 weeks)',
            self::Adult    => 'Adult (18+ weeks)',
        };
    }
}
