<?php

namespace App\Enums;

enum StatusAktif: string
{
    case Aktif = 'aktif';
    case Nonaktif = 'nonaktif';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}