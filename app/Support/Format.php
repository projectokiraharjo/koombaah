<?php

namespace App\Support;

class Format
{
    /** 1234.500 -> "1.234,5" */
    public static function angka($nilai, int $desimal = 3): string
    {
        $teks = number_format((float) $nilai, $desimal, ',', '.');

        return $desimal > 0 ? rtrim(rtrim($teks, '0'), ',') : $teks;
    }

    /** 28000 -> "Rp 28.000" */
    public static function rupiah($nilai): string
    {
        return 'Rp ' . self::angka($nilai, 2);
    }
    /** 12500000 -> "12,5 jt" (untuk label grafik) */
    public static function ringkas($nilai): string
    {
        $n = (float) $nilai;
        $abs = abs($n);
        $tanda = $n < 0 ? '−' : '';

        return $tanda . match (true) {
            $abs >= 1_000_000_000 => self::angka($abs / 1_000_000_000, 1) . ' M',
            $abs >= 1_000_000 => self::angka($abs / 1_000_000, 1) . ' jt',
            $abs >= 1_000 => self::angka($abs / 1_000, 0) . ' rb',
            default => self::angka($abs, 0),
        };
    }
}