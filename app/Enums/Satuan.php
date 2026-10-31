<?php

namespace App\Enums;

enum Satuan: string
{
    case Kg = 'kg';
    case Gram = 'gram';
    case Liter = 'liter';
    case Ml = 'ml';
    case Pcs = 'pcs';

    public function label(): string
    {
        return match ($this) {
            self::Kg => 'Kilogram (kg)',
            self::Gram => 'Gram',
            self::Liter => 'Liter',
            self::Ml => 'Mililiter (ml)',
            self::Pcs => 'Pcs',
        };
    }
}