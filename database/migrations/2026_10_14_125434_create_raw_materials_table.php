<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();
            $table->string('kode_bahan', 20)->unique();
            $table->string('nama_bahan', 100)->unique();
            $table->string('satuan', 20);
            $table->decimal('harga_per_satuan_terakhir', 15, 2)->default(0);
            $table->decimal('stok_saat_ini', 15, 3)->default(0);
            $table->decimal('stok_minimum', 15, 3)->default(0);
            // FK ke suppliers ditambahkan di Modul 2 (tabel suppliers belum ada)
            $table->unsignedBigInteger('supplier_utama_id')->nullable()->index();
            $table->string('status', 20)->default('aktif')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_materials');
    }
};