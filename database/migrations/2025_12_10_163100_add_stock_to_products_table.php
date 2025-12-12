<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // ========== MULAI: Tambah Kolom Stock ==========
            $table->integer('stock')->default(0)->after('price');
            // ========== AKHIR: Tambah Kolom Stock ==========
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // ========== MULAI: Hapus Kolom Stock (Rollback) ==========
            $table->dropColumn('stock');
            // ========== AKHIR: Hapus Kolom Stock (Rollback) ==========
        });
    }
};
