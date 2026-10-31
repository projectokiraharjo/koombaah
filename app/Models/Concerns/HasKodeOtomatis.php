<?php

namespace App\Models\Concerns;

/**
 * Membuat kode berurutan: BB-001, BB-002, ...
 * Model pemakai wajib punya: protected static string $kodePrefix & $kodeKolom
 */
trait HasKodeOtomatis
{
    public static function generateKode(): string
    {
        $kolom = static::$kodeKolom;
        $prefix = static::$kodePrefix . '-';

        $terakhir = static::where($kolom, 'like', $prefix . '%')->orderByDesc('id')->value($kolom);
        $nomor = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $nomor, 3, '0', STR_PAD_LEFT);
    }
}