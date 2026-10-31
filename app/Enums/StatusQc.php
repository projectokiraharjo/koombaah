<?php

namespace App\Enums;

enum StatusQc: string
{
    case LolosPenuh = 'lolos_penuh';
    case SebagianLolos = 'sebagian_lolos';
    case RejectPenuh = 'reject_penuh';

    public function label(): string
    {
        return match ($this) {
            self::LolosPenuh => 'Lolos Penuh',
            self::SebagianLolos => 'Sebagian Lolos',
            self::RejectPenuh => 'Reject Penuh',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::LolosPenuh => 'secondary',
            self::SebagianLolos => 'outline',
            self::RejectPenuh => 'destructive',
        };
    }

    /** B.3.4 langkah 7: status QC dihitung otomatis dari jumlah lolos vs reject */
    public static function dariHasil(float $lolos, float $reject): self
    {
        return match (true) {
            $reject == 0.0 => self::LolosPenuh,
            $lolos == 0.0 => self::RejectPenuh,
            default => self::SebagianLolos,
        };
    }
}