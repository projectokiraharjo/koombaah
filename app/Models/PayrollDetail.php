<?php

namespace App\Models;

use App\Models\Concerns\HanyaOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDetail extends Model
{
    use HanyaOwner; // rincian gaji per karyawan: Owner only

    public $timestamps = false;

    protected $fillable = [
        'payroll_run_id', 'employee_id', 'gaji_pokok', 'tunjangan',
        'bonus_produksi', 'lembur', 'thr', 'potongan', 'total_diterima',
    ];

    protected function casts(): array
    {
        return [
            'gaji_pokok' => 'decimal:2',
            'tunjangan' => 'decimal:2',
            'bonus_produksi' => 'decimal:2',
            'lembur' => 'decimal:2',
            'thr' => 'decimal:2',
            'potongan' => 'decimal:2',
            'total_diterima' => 'decimal:2',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}