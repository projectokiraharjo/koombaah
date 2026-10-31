<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('kode_mitra', 20)->unique();
            $table->string('nama_usaha', 150);
            $table->string('nama_pemilik', 100)->nullable();
            $table->string('no_telepon', 30)->nullable();
            $table->text('alamat');
            $table->string('wilayah_ring', 10)->index();
            $table->text('catatan')->nullable(); // PRD A.6: catatan bebas pendampingan mitra
            $table->string('status', 20)->default('aktif')->index();
            $table->timestamps();
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_order', 20)->unique();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->date('tanggal_order')->index();
            $table->string('metode_pembayaran', 10)->default('tunai');
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->string('status', 20)->default('baru')->index();
            $table->string('status_pembayaran', 20)->default('belum_dibayar');
            $table->decimal('total_nilai_order', 15, 2)->default(0);
            $table->text('catatan')->nullable(); // catatan order + alasan pembatalan
            $table->foreignId('dicatat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('finished_product_id')->constrained('finished_products')->restrictOnDelete();
            $table->decimal('jumlah_dipesan', 15, 3);
            $table->decimal('harga_per_satuan', 15, 2); // snapshot harga jual (B.4.7)
            $table->decimal('subtotal', 15, 2);

            $table->unique(['sales_order_id', 'finished_product_id']);
        });

        DB::statement("ALTER TABLE partners ADD CONSTRAINT partners_ring_valid CHECK (wilayah_ring IN ('ring_1', 'ring_2', 'ring_3'))");
        DB::statement('ALTER TABLE sales_order_items ADD CONSTRAINT soi_jumlah_valid CHECK (jumlah_dipesan > 0 AND harga_per_satuan >= 0)');
        DB::statement("ALTER TABLE sales_orders ADD CONSTRAINT so_jatuh_tempo_kredit
            CHECK (metode_pembayaran <> 'kredit' OR tanggal_jatuh_tempo IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('partners');
    }
};