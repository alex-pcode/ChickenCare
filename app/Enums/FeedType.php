<?php

namespace App\Enums;

enum FeedType: string
{
    case BabyChicks = 'Baby chicks';
    case BigChicks = 'Big chicks';
    case Both = 'Both';

    public function label(): string
    {
        return $this->value;
    }
}
