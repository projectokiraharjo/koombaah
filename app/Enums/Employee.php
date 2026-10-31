<?php

namespace App\Models;

use App\Enums\StatusAktif;
use App\Models\Concerns\HasKodeOtomatis;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasKodeOtomatis;

    protected static string $kodePrefix = 'EMP';
    protected static string $kodeKolom = 'kode_karyawan';

    protected $fillable = [
        'kode_karyawan', 'nama_lengkap', 'posisi', 'no_telepon',
        'tanggal_mulai_kerja', 'tanggal_berhenti', 'status', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai_kerja' => 'date',
            'tanggal_berhenti' => 'date',
            'status' => StatusAktif::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Riwayat komponen gaji, terbaru di atas */
    public function salaryComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class)->orderByDesc('berlaku_sejak');
    }

    public function payrollDetails(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    /** Komponen gaji yang berlaku pada tanggal tertentu */
    public function komponenBerlaku(Carbon $tanggal): ?EmployeeSalaryComponent
    {
        return $this->salaryComponents()->where('berlaku_sejak', '<=', $tanggal->toDateString())->first();
    }

    /** B.7.7: sudah mulai kerja s/d akhir bulan & belum berhenti sebelum awal bulan */
    public function scopeIkutPayroll(Builder $query, Carbon $awal, Carbon $akhir): void
    {
        $query->where('tanggal_mulai_kerja', '<=', $akhir->toDateString())
            ->where(fn ($q) => $q->whereNull('tanggal_berhenti')->orWhere('tanggal_berhenti', '>=', $awal->toDateString()));
    }
}