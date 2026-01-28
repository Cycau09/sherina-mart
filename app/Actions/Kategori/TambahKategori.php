<?php

declare(strict_types=1);

namespace App\Actions\Kategori;

use App\Models\Category;
use App\Support\BaseAction;

/**
 * Action untuk menambah kategori baru
 * Sesuai Menu: "Tambah Kategori Barang"
 */
class TambahKategori extends BaseAction
{
    public function jalankan(): void
    {
        $this->command->info("Tambah Kategori Barang");
        
        $category = new Category();
        
        // ========== MULAI: Validasi Kode Unik (Termasuk Data Terhapus) ==========
        $category->code = $this->tanyaKodeUnik();
        // ========== AKHIR: Validasi Kode Unik ==========

        // ========== MULAI: Validasi Nama (Tidak Boleh Kosong) ==========
        $category->name = $this->tanyaNama();
        // ========== AKHIR: Validasi Nama ==========

        if ($category->save()) {
            $this->command->notify("Success", "data berhasil disimpan");
        } else {
            $this->command->notify("Failed", "data gagal disimpan");
        }
    }

    private function tanyaKodeUnik(): int
    {
        do {
            $code = $this->tanyaAngka("Masukkan Kode Kategori :");
            if (Category::withTrashed()->where('code', $code)->exists()) {
                $this->command->error("Kode '{$code}' sudah pernah digunakan (mungkin di data terhapus). Silakan gunakan kode lain.");
            } else {
                return $code;
            }
        } while (true);
    }

    private function tanyaNama(): string
    {
        do {
            $name = $this->command->ask("Masukkan Nama Kategori : ");
            if (empty(trim($name))) {
                $this->command->error("Nama tidak boleh kosong!");
            } else {
                return $name;
            }
        } while (true);
    }
}
