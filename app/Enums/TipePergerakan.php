<?php

namespace App\Enums;

enum TipePergerakan: string
{
    case Masuk = 'masuk';
    case Keluar = 'keluar';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}