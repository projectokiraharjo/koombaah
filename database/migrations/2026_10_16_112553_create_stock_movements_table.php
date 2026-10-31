<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->morphs('item'); // item_type ('raw_material' / 'finished_product') + item_id
            $table->string('tipe_pergerakan', 10);   // masuk / keluar
            $table->decimal('jumlah', 15, 3);
            $table->string('referensi_tipe', 30);    // purchasing / produksi / sales / penyesuaian_manual
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('dilakukan_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['referensi_tipe', 'referensi_id']);
        });

        // Lapis pengaman di level database
        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_jumlah_positif CHECK (jumlah > 0)');
        DB::statement('ALTER TABLE raw_materials ADD CONSTRAINT raw_materials_stok_tidak_negatif CHECK (stok_saat_ini >= 0)');
        DB::statement('ALTER TABLE finished_products ADD CONSTRAINT finished_products_stok_tidak_negatif CHECK (stok_saat_ini >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE raw_materials DROP CONSTRAINT IF EXISTS raw_materials_stok_tidak_negatif');
        DB::statement('ALTER TABLE finished_products DROP CONSTRAINT IF EXISTS finished_products_stok_tidak_negatif');
        Schema::dropIfExists('stock_movements');
    }
};