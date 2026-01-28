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
        // ========== MULAI: Tambah Soft Deletes ke Kategori ==========
        Schema::table('categories', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
        // ========== AKHIR: Tambah Soft Deletes ke Kategori ==========

        // ========== MULAI: Tambah Soft Deletes ke Jenis ==========
        Schema::table('varieties', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
        // ========== AKHIR: Tambah Soft Deletes ke Jenis ==========

        // ========== MULAI: Ubah Tipe Data Code & Tambah Soft Deletes ke Produk ==========
        Schema::table('products', function (Blueprint $table) {
            // Ubah tipe data code ke string (agar angka 0 di depan tidak hilang)
            $table->string('code')->change();
            // Tambah index untuk pencarian cepat
            $table->index('code');
            // Tambah Soft Deletes
            $table->softDeletes()->after('updated_at');
        });
        // ========== AKHIR: Ubah Tipe Data Code & Tambah Soft Deletes ke Produk ==========
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropSoftDeletes();
            $table->integer('code')->change();
        });

        Schema::table('varieties', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
