<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomDetail extends Model
{
    public $timestamps = false;

    protected $fillable = ['bom_header_id', 'raw_material_id', 'takaran', 'satuan'];

    protected function casts(): array
    {
        return ['takaran' => 'decimal:3'];
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(BomHeader::class, 'bom_header_id');
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }
}