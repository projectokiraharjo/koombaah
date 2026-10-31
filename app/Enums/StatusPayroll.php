<?php

namespace App\Enums;

enum StatusPayroll: string
{
    case Draft = 'draft';
    case Final = 'final';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badge(): string
    {
        return $this === self::Final ? 'secondary' : 'outline';
    }
}