<?php

namespace App\Models;

use App\Enums\KategoriBiaya;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalExpense extends Model
{
    protected $fillable = ['periode_bulan', 'kategori', 'nominal', 'catatan', 'dicatat_oleh'];

    protected function casts(): array
    {
        return [
            'periode_bulan' => 'date',
            'kategori' => KategoriBiaya::class,
            'nominal' => 'decimal:2',
        ];
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}