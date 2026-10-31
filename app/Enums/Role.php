<?php

namespace App\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case AdminKeuangan = 'admin_keuangan';

    /** Daftar modul sistem: slug => nama tampilan */
    public const MODULES = [
        'dashboard'  => 'Dashboard',
        'inventory'  => 'BOM & Inventory',
        'purchasing' => 'Purchasing',
        'produksi'   => 'Produksi',
        'sales'      => 'Sales & Order',
        'distribusi' => 'Distribusi',
        'keuangan'   => 'Keuangan',
        'sdm'        => 'SDM & Payroll',
        'pengaturan' => 'Pengaturan',
    ];

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::AdminKeuangan => 'Admin Keuangan',
        };
    }

    /** Peta akses sesuai PRD A.4 — ubah di sini kalau aturan berubah */
    public function modules(): array
    {
        return match ($this) {
            self::Owner => array_keys(self::MODULES),
            self::Admin => ['dashboard', 'inventory', 'purchasing', 'produksi', 'sales', 'distribusi'],
            self::AdminKeuangan => ['dashboard', 'keuangan'],
        };
    }

    public function canAccess(string $module): bool
    {
        return in_array($module, $this->modules(), true);
    }

    /** Bagian dashboard yang boleh dilihat (PRD B.8.7) */
    public function ringkasan(): array
    {
        return match ($this) {
            self::Owner => ['stok', 'produksi', 'penjualan', 'keuangan', 'distribusi', 'sdm', 'supplier'],
            self::Admin => ['stok', 'produksi', 'penjualan', 'distribusi', 'supplier'],
            self::AdminKeuangan => ['stok', 'produksi', 'penjualan', 'distribusi', 'keuangan'],
        };
    }
}