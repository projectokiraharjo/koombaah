<?php

namespace App\Models;

use App\Enums\StatusPengirimanItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryScheduleItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['delivery_schedule_id', 'sales_order_id', 'status_pengiriman_item', 'catatan', 'waktu_terkirim'];

    protected function casts(): array
    {
        return [
            'status_pengiriman_item' => StatusPengirimanItem::class,
            'waktu_terkirim' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DeliverySchedule::class, 'delivery_schedule_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }
}