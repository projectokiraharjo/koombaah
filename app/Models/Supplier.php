<?php

namespace App\Models;

use App\Enums\StatusAktif;
use App\Models\Concerns\HasKodeOtomatis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasKodeOtomatis;

    protected static string $kodePrefix = 'SUP';
    protected static string $kodeKolom = 'kode_supplier';

    protected $fillable = ['kode_supplier', 'nama_supplier', 'kontak_person', 'no_telepon', 'alamat', 'status'];

    protected function casts(): array
    {
        return ['status' => StatusAktif::class];
    }

    /** Bahan baku yang menjadikan supplier ini sebagai supplier utama */
    public function bahanUtama(): HasMany
    {
        return $this->hasMany(RawMaterial::class, 'supplier_utama_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}