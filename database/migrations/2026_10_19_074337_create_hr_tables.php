<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('kode_karyawan', 20)->unique();
            $table->string('nama_lengkap', 150);
            $table->string('posisi', 100);
            $table->string('no_telepon', 30)->nullable();
            $table->date('tanggal_mulai_kerja');
            $table->date('tanggal_berhenti')->nullable();
            $table->string('status', 20)->default('aktif')->index();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->decimal('gaji_pokok', 15, 2);
            $table->decimal('tunjangan', 15, 2)->default(0);
            $table->date('berlaku_sejak');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['employee_id', 'berlaku_sejak']);
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->date('periode_bulan')->unique();
            $table->decimal('total_gaji_agregat', 15, 2)->default(0);
            $table->string('status', 10)->default('draft');
            $table->foreignId('diproses_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamp('diproses_pada');
        });

        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->decimal('gaji_pokok', 15, 2);
            $table->decimal('tunjangan', 15, 2);
            $table->decimal('bonus_produksi', 15, 2)->default(0);
            $table->decimal('lembur', 15, 2)->default(0);
            $table->decimal('thr', 15, 2)->default(0);
            $table->decimal('potongan', 15, 2)->default(0);
            $table->decimal('total_diterima', 15, 2);

            $table->unique(['payroll_run_id', 'employee_id']);
        });

        DB::statement("ALTER TABLE employees ADD CONSTRAINT emp_berhenti_valid CHECK (
            (tanggal_berhenti IS NULL OR tanggal_berhenti >= tanggal_mulai_kerja)
            AND (status <> 'nonaktif' OR tanggal_berhenti IS NOT NULL))");
        DB::statement('ALTER TABLE employee_salary_components ADD CONSTRAINT esc_valid CHECK (gaji_pokok >= 0 AND tunjangan >= 0)');
        DB::statement('ALTER TABLE payroll_runs ADD CONSTRAINT pr_awal_bulan CHECK (EXTRACT(DAY FROM periode_bulan) = 1)');
        // B.7.5: total_diterima = pokok + tunjangan + bonus + lembur + THR − potongan
        DB::statement('ALTER TABLE payroll_details ADD CONSTRAINT pd_valid CHECK (
            gaji_pokok >= 0 AND tunjangan >= 0 AND bonus_produksi >= 0 AND lembur >= 0 AND thr >= 0 AND potongan >= 0
            AND total_diterima = gaji_pokok + tunjangan + bonus_produksi + lembur + thr - potongan
            AND total_diterima >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('employee_salary_components');
        Schema::dropIfExists('employees');
    }
};