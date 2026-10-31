<?php

namespace App\Models;

use App\Enums\ReferensiStok;
use App\Enums\TipePergerakan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'tipe_pergerakan', 'jumlah', 'referensi_tipe', 'referensi_id', 'catatan', 'dilakukan_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tipe_pergerakan' => TipePergerakan::class,
            'referensi_tipe' => ReferensiStok::class,
            'jumlah' => 'decimal:3',
        ];
    }

    // Audit trail (PRD C.1): log stok tidak boleh diedit/dihapus. Koreksi = buat penyesuaian baru.
    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilakukan_oleh');
    }
}