<?php

namespace App\Models;

use App\Enums\Satuan;
use App\Enums\StatusAktif;
use App\Models\Concerns\HasKodeOtomatis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

class FinishedProduct extends Model
{
    use HasKodeOtomatis;

    protected static string $kodePrefix = 'PRD';
    protected static string $kodeKolom = 'kode_produk';

    // stok_saat_ini SENGAJA tidak ada di sini (PRD B.1.7)
    protected $fillable = [
        'kode_produk', 'nama_produk', 'kategori', 'satuan_jual',
        'harga_jual_per_satuan', 'stok_minimum', 'status',
    ];

    protected function casts(): array
    {
        return [
            'satuan_jual' => Satuan::class,
            'status' => StatusAktif::class,
            'harga_jual_per_satuan' => 'decimal:2',
            'stok_saat_ini' => 'decimal:3',
            'stok_minimum' => 'decimal:3',
        ];
    }

    public function boms(): HasMany
    {
        return $this->hasMany(BomHeader::class);
    }

    /** Resep yang sedang berlaku (maksimal satu — PRD B.1.7) */
    public function activeBom(): HasOne
    {
        return $this->hasOne(BomHeader::class)->where('status', 'aktif');
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'item');
    }
    
    public function isLowStock(): bool
    {
        return $this->status === StatusAktif::Aktif
            && (float) $this->stok_saat_ini < (float) $this->stok_minimum;
    }
        /** Item aktif dengan stok di bawah stok minimum (PRD B.8.5) */
    public function scopeMenipis(Builder $query): void
    {
        $query->where('status', 'aktif')->whereColumn('stok_saat_ini', '<', 'stok_minimum');
    }
    public function sudahDipakai(): bool
    {
        return $this->boms()->exists() || $this->stockMovements()->exists();
    }
}