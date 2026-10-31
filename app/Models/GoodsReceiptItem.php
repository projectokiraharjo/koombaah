<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['goods_receipt_id', 'purchase_order_item_id', 'jumlah_diterima'];

    protected function casts(): array
    {
        return ['jumlah_diterima' => 'decimal:3'];
    }

    public function poItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }
}