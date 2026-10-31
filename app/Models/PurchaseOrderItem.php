<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'purchase_order_id', 'raw_material_id', 'jumlah_dipesan', 'jumlah_diterima', 'harga_per_satuan', 'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_dipesan' => 'decimal:3',
            'jumlah_diterima' => 'decimal:3',
            'harga_per_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function sisa(): float
    {
        return round((float) $this->jumlah_dipesan - (float) $this->jumlah_diterima, 3);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }
}