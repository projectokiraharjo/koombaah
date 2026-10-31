<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('kode_supplier', 20)->unique();
            $table->string('nama_supplier', 150)->unique();
            $table->string('kontak_person', 100)->nullable();
            $table->string('no_telepon', 30)->nullable();
            $table->text('alamat')->nullable();
            $table->string('status', 20)->default('aktif')->index();
            $table->timestamps();
        });

        // Sambungkan FK yang ditunda sejak Modul 1
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->foreign('supplier_utama_id')->references('id')->on('suppliers')->nullOnDelete();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_po', 20)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->date('tanggal_po');
            $table->string('metode_pembayaran', 10)->default('tunai');
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->decimal('total_nilai_po', 15, 2)->default(0);
            $table->string('status_pembayaran', 20)->default('belum_dibayar');
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->restrictOnDelete();
            $table->decimal('jumlah_dipesan', 15, 3);
            $table->decimal('jumlah_diterima', 15, 3)->default(0);
            $table->decimal('harga_per_satuan', 15, 2);
            $table->decimal('subtotal', 15, 2);

            $table->unique(['purchase_order_id', 'raw_material_id']);
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->date('tanggal_terima');
            $table->foreignId('diterima_oleh')->constrained('users')->restrictOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->restrictOnDelete();
            $table->decimal('jumlah_diterima', 15, 3);
        });

        // PRD B.2.7: jumlah diterima tidak boleh melebihi jumlah dipesan — dijaga juga oleh database
        DB::statement('ALTER TABLE purchase_order_items ADD CONSTRAINT poi_jumlah_valid
            CHECK (jumlah_dipesan > 0 AND jumlah_diterima >= 0 AND jumlah_diterima <= jumlah_dipesan)');
        DB::statement('ALTER TABLE goods_receipt_items ADD CONSTRAINT gri_jumlah_positif CHECK (jumlah_diterima > 0)');
        // Jika suatu saat kredit dipakai, jatuh tempo wajib diisi (B.2.5)
        DB::statement("ALTER TABLE purchase_orders ADD CONSTRAINT po_jatuh_tempo_kredit
            CHECK (metode_pembayaran <> 'kredit' OR tanggal_jatuh_tempo IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::table('raw_materials', fn (Blueprint $table) => $table->dropForeign(['supplier_utama_id']));
        Schema::dropIfExists('suppliers');
    }
};