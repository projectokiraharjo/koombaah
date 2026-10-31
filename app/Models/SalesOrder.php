<?php

namespace App\Models;

use App\Enums\MetodePembayaran;
use App\Enums\StatusOrder;
use App\Enums\StatusPembayaran;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;   

class SalesOrder extends Model
{
    protected $fillable = [
        'nomor_order', 'partner_id', 'tanggal_order', 'metode_pembayaran', 'tanggal_jatuh_tempo',
        'status', 'status_pembayaran', 'total_nilai_order', 'catatan', 'dicatat_oleh','siap_kirim_pada',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_order' => 'date',
            'tanggal_jatuh_tempo' => 'date',
            'status' => StatusOrder::class,
            'metode_pembayaran' => MetodePembayaran::class,
            'status_pembayaran' => StatusPembayaran::class,
            'total_nilai_order' => 'decimal:2',
            'siap_kirim_pada' => 'datetime',
        ];
    }

    /** SO-2026-0001, berurutan per tahun */
    public static function generateNomor(string $tanggal): string
    {
        $prefix = 'SO-' . substr($tanggal, 0, 4) . '-';
        $terakhir = static::where('nomor_order', 'like', $prefix . '%')->orderByDesc('nomor_order')->value('nomor_order');
        $nomor = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);
    }

    public function bisaDiedit(): bool
    {
        return $this->status === StatusOrder::Baru;
    }

    public function bisaDibatalkan(): bool
    {
        return in_array($this->status, [StatusOrder::Baru, StatusOrder::Dikonfirmasi, StatusOrder::Diproses, StatusOrder::SiapKirim], true);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function deliveryItems(): HasMany
    {
        return $this->hasMany(DeliveryScheduleItem::class);
    }

    /** Order siap kirim yang belum masuk jadwal pengiriman aktif */
    public function scopeBisaDijadwalkan(Builder $query): void
    {
        $query->where('status', StatusOrder::SiapKirim->value)
            ->whereDoesntHave('deliveryItems', fn ($q) => $q->where('status_pengiriman_item', 'menunggu'));
    }
}