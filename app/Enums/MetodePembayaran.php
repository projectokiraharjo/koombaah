<?php

namespace App\Enums;

enum MetodePembayaran: string
{
    case Tunai = 'tunai';
    case Kredit = 'kredit';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}