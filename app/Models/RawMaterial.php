<?php

namespace App\Models;

use App\Enums\Satuan;
use App\Enums\StatusAktif;
use App\Models\Concerns\HasKodeOtomatis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterial extends Model
{
    use HasKodeOtomatis;

    protected static string $kodePrefix = 'BB';
    protected static string $kodeKolom = 'kode_bahan';

    // stok_saat_ini SENGAJA tidak ada di sini: hanya berubah lewat StockService (PRD B.1.7)
    protected $fillable = [
        'kode_bahan', 'nama_bahan', 'satuan', 'harga_per_satuan_terakhir',
        'stok_minimum', 'supplier_utama_id', 'status',
    ];

    protected function casts(): array
    {
        return [
            'satuan' => Satuan::class,
            'status' => StatusAktif::class,
            'harga_per_satuan_terakhir' => 'decimal:2',
            'stok_saat_ini' => 'decimal:3',
            'stok_minimum' => 'decimal:3',
        ];
    }

    public function bomDetails(): HasMany
    {
        return $this->hasMany(BomDetail::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'item');
    }

    public function supplierUtama(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_utama_id');
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

    /** Sudah dipakai di resep atau punya riwayat stok -> satuan dikunci */
    public function sudahDipakai(): bool
    {
        return $this->bomDetails()->exists() || $this->stockMovements()->exists();
    }
}