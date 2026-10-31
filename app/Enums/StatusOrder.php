<?php

namespace App\Enums;

enum StatusOrder: string
{
    case Baru = 'baru';
    case Dikonfirmasi = 'dikonfirmasi';
    case Diproses = 'diproses';
    case SiapKirim = 'siap_kirim';
    case Terkirim = 'terkirim';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Dikonfirmasi => 'Dikonfirmasi',
            self::Diproses => 'Diproses',
            self::SiapKirim => 'Siap Kirim',
            self::Terkirim => 'Terkirim',
            self::Selesai => 'Selesai',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Baru => 'outline',
            self::Dikonfirmasi, self::Diproses => 'default',
            self::SiapKirim, self::Terkirim, self::Selesai => 'secondary',
            self::Dibatalkan => 'destructive',
        };
    }

    /** Status di mana stok produk jadi sudah dipotong */
    public function stokSudahDipotong(): bool
    {
        return in_array($this, [self::Dikonfirmasi, self::Diproses, self::SiapKirim, self::Terkirim, self::Selesai], true);
    }

    /** Langkah lanjut yang boleh dilakukan Admin secara manual (US 4) */
    public function berikutnyaManual(): ?self
    {
        return match ($this) {
            self::Dikonfirmasi => self::Diproses,
            self::Diproses => self::SiapKirim,
            default => null,
        };
    }

    /** Urutan alur untuk tampilan stepper */
    public static function alur(): array
    {
        return [self::Baru, self::Dikonfirmasi, self::Diproses, self::SiapKirim, self::Terkirim, self::Selesai];
    }

    /** Status yang dihitung sebagai penjualan nyata (untuk riwayat & ringkasan) */
    public static function terhitung(): array
    {
        return array_map(fn ($s) => $s->value, array_filter(self::cases(), fn ($s) => $s->stokSudahDipotong()));
    }
}