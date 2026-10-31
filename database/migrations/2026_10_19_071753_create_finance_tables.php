<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('tipe', 10);                   // masuk / keluar
            $table->string('kategori', 30)->index();
            $table->decimal('nominal', 15, 2);
            $table->date('tanggal_transaksi')->index();
            $table->string('referensi_tipe', 30)->nullable(); // sales_order / purchase_order / delivery_schedule / null
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('dicatat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['referensi_tipe', 'referensi_id']);
        });

        Schema::create('operational_expenses', function (Blueprint $table) {
            $table->id();
            $table->date('periode_bulan')->index();       // selalu tanggal 1, mis. 2026-09-01
            $table->string('kategori', 40);
            $table->decimal('nominal', 15, 2);
            $table->text('catatan')->nullable();
            $table->foreignId('dicatat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('production_batch_hpp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->unique()->constrained('production_batches')->restrictOnDelete();
            $table->decimal('total_biaya_bahan_baku', 15, 2);
            $table->decimal('jumlah_lolos_qc', 15, 3);
            $table->decimal('hpp_per_satuan', 15, 2);
            $table->timestamp('dihitung_pada');
        });

        Schema::create('financial_periods', function (Blueprint $table) {
            $table->id();
            $table->date('periode_bulan')->unique();
            $table->string('status', 20)->default('terbuka');
            $table->decimal('total_pendapatan', 15, 2)->nullable();
            $table->decimal('total_hpp', 15, 2)->nullable();
            $table->decimal('total_biaya_operasional', 15, 2)->nullable();
            $table->decimal('laba_rugi', 15, 2)->nullable();
            $table->foreignId('ditutup_oleh')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('ditutup_pada')->nullable();
            $table->foreignId('dibuka_kembali_oleh')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('dibuka_kembali_pada')->nullable();
            $table->text('alasan_buka_kembali')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement("ALTER TABLE cash_transactions ADD CONSTRAINT ct_valid CHECK (nominal > 0 AND tipe IN ('masuk', 'keluar'))");
        // Kas penjualan per order & biaya distribusi per jadwal hanya boleh tercatat SEKALI
        DB::statement("CREATE UNIQUE INDEX ct_satu_per_referensi ON cash_transactions (referensi_tipe, referensi_id, kategori)
            WHERE referensi_tipe IN ('sales_order', 'delivery_schedule')");
        DB::statement('ALTER TABLE operational_expenses ADD CONSTRAINT oe_valid CHECK (nominal > 0 AND EXTRACT(DAY FROM periode_bulan) = 1)');
        DB::statement('ALTER TABLE production_batch_hpp ADD CONSTRAINT hpp_valid CHECK (jumlah_lolos_qc > 0)');
        DB::statement('ALTER TABLE financial_periods ADD CONSTRAINT fp_awal_bulan CHECK (EXTRACT(DAY FROM periode_bulan) = 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_periods');
        Schema::dropIfExists('production_batch_hpp');
        Schema::dropIfExists('operational_expenses');
        Schema::dropIfExists('cash_transactions');
    }
};