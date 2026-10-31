<?php

namespace App\Models;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPO;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'nomor_po', 'supplier_id', 'tanggal_po', 'metode_pembayaran', 'tanggal_jatuh_tempo',
        'status', 'total_nilai_po', 'status_pembayaran', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_po' => 'date',
            'tanggal_jatuh_tempo' => 'date',
            'status' => StatusPO::class,
            'metode_pembayaran' => MetodePembayaran::class,
            'status_pembayaran' => StatusPembayaran::class,
            'total_nilai_po' => 'decimal:2',
        ];
    }

    /** PO-2026-0001, berurutan per tahun */
    public static function generateNomor(string $tanggal): string
    {
        $prefix = 'PO-' . substr($tanggal, 0, 4) . '-';
        $terakhir = static::where('nomor_po', 'like', $prefix . '%')->orderByDesc('nomor_po')->value('nomor_po');
        $nomor = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);
    }

    public function bisaDiedit(): bool
    {
        return $this->status === StatusPO::Draft;
    }

    public function bisaDiterima(): bool
    {
        return in_array($this->status, [StatusPO::Dipesan, StatusPO::SebagianDiterima], true);
    }

    public function bisaDibatalkan(): bool
    {
        return in_array($this->status, [StatusPO::Draft, StatusPO::Dipesan], true);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}