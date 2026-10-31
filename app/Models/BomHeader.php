<?php

namespace App\Models;

use App\Enums\StatusAktif;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BomHeader extends Model
{
    const UPDATED_AT = null; // PRD: hanya created_at — versi BOM tidak pernah diedit

    protected $fillable = ['finished_product_id', 'versi', 'status', 'catatan_perubahan', 'created_by'];

    protected function casts(): array
    {
        return ['status' => StatusAktif::class, 'versi' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinishedProduct::class, 'finished_product_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(BomDetail::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}