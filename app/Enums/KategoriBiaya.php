<?php

namespace App\Enums;

/** Kategori biaya operasional (PRD B.6.5 & B.6.7) */
enum KategoriBiaya: string
{
    case BebanGaji = 'beban_gaji';
    case Sewa = 'sewa';
    case Listrik = 'listrik';
    case Distribusi = 'distribusi';
    case MaintenanceAlat = 'maintenance_alat';
    case InternetPulsa = 'internet_pulsa';
    case Marketing = 'marketing';
    case TransportasiNonDistribusi = 'transportasi_non_distribusi';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::BebanGaji => 'Beban Gaji (agregat)',
            self::Sewa => 'Sewa Tempat',
            self::Listrik => 'Listrik',
            self::Distribusi => 'Distribusi',
            self::MaintenanceAlat => 'Maintenance Alat Produksi',
            self::InternetPulsa => 'Internet / Pulsa',
            self::Marketing => 'Marketing / Iklan',
            self::TransportasiNonDistribusi => 'Transportasi Non-Distribusi',
            self::Lainnya => 'Lainnya',
        };
    }

    /** Kategori yang boleh diisi manual — Distribusi (Modul 5) & Beban Gaji (payroll Modul 7) tercatat otomatis */
    public static function manual(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn ($k) => ! in_array($k, [self::Distribusi, self::BebanGaji], true),
        ));
    }
}