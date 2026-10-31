<?php

namespace App\Models;

use App\Enums\StatusAktif;
use App\Enums\WilayahRing;
use App\Models\Concerns\HasKodeOtomatis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    use HasKodeOtomatis;

    protected static string $kodePrefix = 'MTR';
    protected static string $kodeKolom = 'kode_mitra';

    protected $fillable = [
        'kode_mitra', 'nama_usaha', 'nama_pemilik', 'no_telepon', 'alamat', 'wilayah_ring', 'catatan', 'status',
    ];

    protected function casts(): array
    {
        return ['wilayah_ring' => WilayahRing::class, 'status' => StatusAktif::class];
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }
}