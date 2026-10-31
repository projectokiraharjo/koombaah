<?php

namespace App\Models;

use App\Enums\StatusPayroll;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    public $timestamps = false;

    protected $fillable = ['periode_bulan', 'total_gaji_agregat', 'status', 'diproses_oleh', 'diproses_pada'];

    protected function casts(): array
    {
        return [
            'periode_bulan' => 'date',
            'total_gaji_agregat' => 'decimal:2',
            'status' => StatusPayroll::class,
            'diproses_pada' => 'datetime',
        ];
    }

    public function isFinal(): bool
    {
        return $this->status === StatusPayroll::Final;
    }

    public function details(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }
}