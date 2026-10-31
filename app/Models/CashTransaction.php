<?php

namespace App\Models;

use App\Enums\KategoriKas;
use App\Enums\TipePergerakan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'tipe', 'kategori', 'nominal', 'tanggal_transaksi', 'referensi_tipe', 'referensi_id', 'catatan', 'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tipe' => TipePergerakan::class, // masuk / keluar
            'kategori' => KategoriKas::class,
            'nominal' => 'decimal:2',
            'tanggal_transaksi' => 'date',
        ];
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}