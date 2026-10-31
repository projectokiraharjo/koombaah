<?php

namespace App\Enums;

/** Wilayah operasional Koombaah (PRD A.1 & C.3) */
enum WilayahRing: string
{
    case Ring1 = 'ring_1';
    case Ring2 = 'ring_2';
    case Ring3 = 'ring_3';

    public function label(): string
    {
        return match ($this) {
            self::Ring1 => 'Ring 1',
            self::Ring2 => 'Ring 2',
            self::Ring3 => 'Ring 3',
        };
    }

    public function area(): string
    {
        return match ($this) {
            self::Ring1 => 'Pamulang, Ciputat, Sawangan',
            self::Ring2 => 'Pondok Aren, Cinere',
            self::Ring3 => 'Serpong/BSD',
        };
    }
}