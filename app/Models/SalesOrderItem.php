<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['sales_order_id', 'finished_product_id', 'jumlah_dipesan', 'harga_per_satuan', 'subtotal'];

    protected function casts(): array
    {
        return ['jumlah_dipesan' => 'decimal:3', 'harga_per_satuan' => 'decimal:2', 'subtotal' => 'decimal:2'];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinishedProduct::class, 'finished_product_id');
    }
}