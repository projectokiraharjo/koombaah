<?php

namespace App\Models;

use App\Enums\StatusPeriode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialPeriod extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'periode_bulan', 'status', 'total_pendapatan', 'total_hpp', 'total_biaya_operasional', 'laba_rugi',
        'ditutup_oleh', 'ditutup_pada', 'dibuka_kembali_oleh', 'dibuka_kembali_pada', 'alasan_buka_kembali',
    ];

    protected function casts(): array
    {
        return [
            'periode_bulan' => 'date',
            'status' => StatusPeriode::class,
            'total_pendapatan' => 'decimal:2',
            'total_hpp' => 'decimal:2',
            'total_biaya_operasional' => 'decimal:2',
            'laba_rugi' => 'decimal:2',
            'ditutup_pada' => 'datetime',
            'dibuka_kembali_pada' => 'datetime',
        ];
    }

    public function penutup(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditutup_oleh');
    }

    public function pembuka(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuka_kembali_oleh');
    }
}