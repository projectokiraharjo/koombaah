<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finished_products', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produk', 20)->unique();
            $table->string('nama_produk', 100)->unique();
            $table->string('kategori', 50)->index();
            $table->string('satuan_jual', 20);
            $table->decimal('harga_jual_per_satuan', 15, 2)->default(0);
            $table->decimal('stok_saat_ini', 15, 3)->default(0);
            $table->decimal('stok_minimum', 15, 3)->default(0);
            $table->string('status', 20)->default('aktif')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finished_products');
    }
};