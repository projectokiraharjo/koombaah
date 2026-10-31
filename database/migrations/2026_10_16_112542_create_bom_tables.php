<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bom_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_product_id')->constrained('finished_products')->restrictOnDelete();
            $table->unsignedInteger('versi');
            $table->string('status', 20)->default('aktif');
            $table->text('catatan_perubahan')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['finished_product_id', 'versi']);
        });

        // PRD B.1.7: hanya SATU versi aktif per produk — dijaga langsung oleh PostgreSQL
        DB::statement("CREATE UNIQUE INDEX bom_headers_satu_aktif_per_produk ON bom_headers (finished_product_id) WHERE status = 'aktif'");

        Schema::create('bom_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_header_id')->constrained('bom_headers')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->restrictOnDelete();
            $table->decimal('takaran', 15, 3);
            $table->string('satuan', 20); // snapshot satuan bahan baku

            $table->unique(['bom_header_id', 'raw_material_id']); // 1 bahan tidak boleh muncul 2x dalam 1 resep
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_details');
        Schema::dropIfExists('bom_headers');
    }
};