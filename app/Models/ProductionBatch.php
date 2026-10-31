<?php

namespace App\Models;

use App\Enums\StatusBatch;
use App\Enums\StatusQc;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductionBatch extends Model
{
    protected $fillable = [
        'nomor_batch', 'finished_product_id', 'bom_header_id', 'jumlah_target', 'jumlah_aktual',
        'jumlah_lolos_qc', 'jumlah_reject_qc', 'tanggal_mulai', 'tanggal_selesai',
        'status', 'status_qc', 'catatan_qc', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'status' => StatusBatch::class,
            'status_qc' => StatusQc::class,
            'jumlah_target' => 'decimal:3',
            'jumlah_aktual' => 'decimal:3',
            'jumlah_lolos_qc' => 'decimal:3',
            'jumlah_reject_qc' => 'decimal:3',
        ];
    }

    /** BATCH-2026-0001, berurutan per tahun */
    public static function generateNomor(string $tanggal): string
    {
        $prefix = 'BATCH-' . substr($tanggal, 0, 4) . '-';
        $terakhir = static::where('nomor_batch', 'like', $prefix . '%')->orderByDesc('nomor_batch')->value('nomor_batch');
        $nomor = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);
    }

    public function sedangBerjalan(): bool
    {
        return $this->status === StatusBatch::SedangBerjalan;
    }

    /** User story 3: lama proses produksi (hari) */
    public function durasiHari(): ?int
    {
        return $this->tanggal_selesai ? (int) $this->tanggal_mulai->diffInDays($this->tanggal_selesai) : null;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinishedProduct::class, 'finished_product_id');
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(BomHeader::class, 'bom_header_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProductionBatchMaterial::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function hpp(): HasOne
    {
        return $this->hasOne(ProductionBatchHpp::class);
    }
}