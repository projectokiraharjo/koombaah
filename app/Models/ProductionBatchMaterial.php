<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionBatchMaterial extends Model
{
    public $timestamps = false;

    protected $fillable = ['production_batch_id', 'raw_material_id', 'jumlah_terpakai', 'harga_per_satuan'];

    protected function casts(): array
    {
        return ['jumlah_terpakai' => 'decimal:3', 'harga_per_satuan' => 'decimal:2'];
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }
}