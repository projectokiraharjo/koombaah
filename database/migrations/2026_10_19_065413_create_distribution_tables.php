<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('kode_jadwal', 20)->unique();
            $table->string('wilayah_ring', 10)->index();
            $table->string('nama_kurir', 100); // teks, bukan akun sistem (A.4)
            $table->date('tanggal_rencana_kirim')->index();
            $table->date('tanggal_aktual_kirim')->nullable();
            $table->decimal('estimasi_biaya_distribusi', 15, 2)->nullable();
            $table->string('status', 20)->default('dijadwalkan')->index();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('delivery_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_schedule_id')->constrained('delivery_schedules')->cascadeOnDelete();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->restrictOnDelete();
            $table->string('status_pengiriman_item', 20)->default('menunggu')->index();
            $table->text('catatan')->nullable();
            $table->timestamp('waktu_terkirim')->nullable();

            $table->unique(['delivery_schedule_id', 'sales_order_id']);
        });

        // Satu order hanya boleh ada di SATU jadwal aktif pada satu waktu
        DB::statement("CREATE UNIQUE INDEX dsi_order_satu_aktif ON delivery_schedule_items (sales_order_id) WHERE status_pengiriman_item = 'menunggu'");
        DB::statement("ALTER TABLE delivery_schedules ADD CONSTRAINT ds_ring_valid CHECK (wilayah_ring IN ('ring_1', 'ring_2', 'ring_3'))");
        DB::statement('ALTER TABLE delivery_schedules ADD CONSTRAINT ds_biaya_valid CHECK (estimasi_biaya_distribusi IS NULL OR estimasi_biaya_distribusi >= 0)');

        // Penyesuaian skema: waktu order menjadi siap_kirim -> dasar "waktu rata-rata pengiriman" (US 6, B.8.2)
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->timestamp('siap_kirim_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', fn (Blueprint $table) => $table->dropColumn('siap_kirim_pada'));
        Schema::dropIfExists('delivery_schedule_items');
        Schema::dropIfExists('delivery_schedules');
    }
};