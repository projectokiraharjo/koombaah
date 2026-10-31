<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionBatchHpp extends Model
{
    protected $table = 'production_batch_hpp';
    public $timestamps = false;

    protected $fillable = ['production_batch_id', 'total_biaya_bahan_baku', 'jumlah_lolos_qc', 'hpp_per_satuan', 'dihitung_pada'];

    protected function casts(): array
    {
        return [
            'total_biaya_bahan_baku' => 'decimal:2',
            'jumlah_lolos_qc' => 'decimal:3',
            'hpp_per_satuan' => 'decimal:2',
            'dihitung_pada' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }
}