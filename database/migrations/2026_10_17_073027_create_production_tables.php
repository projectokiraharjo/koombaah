<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_batch', 25)->unique();
            $table->foreignId('finished_product_id')->constrained('finished_products')->restrictOnDelete();
            $table->foreignId('bom_header_id')->constrained('bom_headers')->restrictOnDelete();
            $table->decimal('jumlah_target', 15, 3);
            $table->decimal('jumlah_aktual', 15, 3)->nullable();
            $table->decimal('jumlah_lolos_qc', 15, 3)->nullable();
            $table->decimal('jumlah_reject_qc', 15, 3)->nullable();
            $table->date('tanggal_mulai')->index();
            $table->date('tanggal_selesai')->nullable();
            $table->string('status', 20)->default('sedang_berjalan')->index();
            $table->string('status_qc', 20)->nullable();
            $table->text('catatan_qc')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('production_batch_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->restrictOnDelete();
            $table->decimal('jumlah_terpakai', 15, 3);
            // Penyesuaian skema: harga bahan SAAT batch dimulai -> dasar HPP historis (PRD B.6.4-D, B.6.7)
            $table->decimal('harga_per_satuan', 15, 2);

            $table->unique(['production_batch_id', 'raw_material_id']);
        });

        DB::statement('ALTER TABLE production_batches ADD CONSTRAINT pb_target_positif CHECK (jumlah_target > 0)');
        // B.3.5: jumlah_aktual = jumlah_lolos_qc + jumlah_reject_qc
        DB::statement('ALTER TABLE production_batches ADD CONSTRAINT pb_hasil_valid CHECK (
            jumlah_aktual IS NULL OR (jumlah_lolos_qc >= 0 AND jumlah_reject_qc >= 0
            AND jumlah_aktual = jumlah_lolos_qc + jumlah_reject_qc))');
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batch_materials');
        Schema::dropIfExists('production_batches');
    }
};