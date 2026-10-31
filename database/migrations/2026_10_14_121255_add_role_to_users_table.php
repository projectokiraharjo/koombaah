<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // enum di PostgreSQL = CHECK constraint: nilai di luar 3 role ditolak database
            $table->enum('role', ['owner', 'admin', 'admin_keuangan'])->default('admin');
            $table->boolean('is_active')->default(true);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};