<?php

namespace App\Models;

use App\Models\Concerns\HanyaOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryComponent extends Model
{
    use HanyaOwner; // data gaji: Owner only

    const UPDATED_AT = null;

    protected $fillable = ['employee_id', 'gaji_pokok', 'tunjangan', 'berlaku_sejak'];

    protected function casts(): array
    {
        return [
            'gaji_pokok' => 'decimal:2',
            'tunjangan' => 'decimal:2',
            'berlaku_sejak' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}