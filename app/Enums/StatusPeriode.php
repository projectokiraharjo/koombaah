<?php

namespace App\Enums;

enum StatusPeriode: string
{
    case Terbuka = 'terbuka';
    case Ditutup = 'ditutup';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badge(): string
    {
        return $this === self::Ditutup ? 'secondary' : 'outline';
    }
}