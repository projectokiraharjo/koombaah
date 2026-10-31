<?php

namespace App\Models;

use App\Enums\StatusJadwal;
use App\Enums\WilayahRing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliverySchedule extends Model
{
    protected $fillable = [
        'kode_jadwal', 'wilayah_ring', 'nama_kurir', 'tanggal_rencana_kirim', 'tanggal_aktual_kirim',
        'estimasi_biaya_distribusi', 'status', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'wilayah_ring' => WilayahRing::class,
            'status' => StatusJadwal::class,
            'tanggal_rencana_kirim' => 'date',
            'tanggal_aktual_kirim' => 'date',
            'estimasi_biaya_distribusi' => 'decimal:2',
        ];
    }

    /** DEL-2026-0001, berurutan per tahun */
    public static function generateKode(string $tanggal): string
    {
        $prefix = 'DEL-' . substr($tanggal, 0, 4) . '-';
        $terakhir = static::where('kode_jadwal', 'like', $prefix . '%')->orderByDesc('kode_jadwal')->value('kode_jadwal');
        $nomor = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);
    }

    public function bisaDiubah(): bool
    {
        return $this->status === StatusJadwal::Dijadwalkan;
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryScheduleItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}