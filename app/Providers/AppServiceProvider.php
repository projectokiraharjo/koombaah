<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\FinishedProduct;
use App\Models\RawMaterial;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::morphMap([
        'raw_material' => RawMaterial::class,
        'finished_product' => FinishedProduct::class,
        ]);
        // Satu gate per modul: Gate 'inventory', 'keuangan', 'sdm', dst.
        foreach (array_keys(Role::MODULES) as $module) {
            Gate::define($module, fn (User $user) => $user->is_active && $user->role->canAccess($module));
        }

        // PRD B.1.7: perubahan BOM hanya oleh Owner
        Gate::define('bom.ubah', fn (User $user) => $user->is_active && $user->isOwner());
        // PRD B.6.7: hanya Owner yang dapat menutup & membuka kembali periode
        Gate::define('periode.kelola', fn (User $user) => $user->is_active && $user->isOwner());
        // PRD B.8.7: bagian dashboard disesuaikan per role — dicek di server
        foreach (['stok', 'produksi', 'penjualan', 'keuangan', 'distribusi', 'sdm', 'supplier'] as $bagian) {
            Gate::define("ringkasan.{$bagian}", fn (User $user) => $user->is_active && in_array($bagian, $user->role->ringkasan(), true));
        }
    }
}