<?php

declare(strict_types=1);

namespace App\Actions\Kategori;

use App\Models\Category;
use App\Support\BaseAction;

/**
 * Action untuk mengubah kategori yang sudah ada
 * Sesuai Menu: "Ubah Kategori Barang"
 */
class UbahKategori extends BaseAction
{
    public function jalankan(): void
    {
        $this->command->info("Ubah Kategori Barang");
        
        $code = $this->tanyaAngka("Masukkan Kode Kategori yang akan diubah :");
        $category = Category::where('code', $code)->first();

        if (!$category) {
            $this->command->error("Kategori dengan kode {$code} tidak ditemukan!");
            return;
        }

        $category->code = $this->tanyaKodeBaru($category->code);
        $category->name = $this->tanyaNamaBaru($category->name);

        if ($category->save()) {
            $this->command->notify("Success", "data berhasil diubah");
        } else {
            $this->command->notify("Failed", "data gagal diubah");
        }
    }

    private function tanyaKodeBaru(int $kodeSekarang): int
    {
        do {
            $kodeBaru = $this->tanyaAngka("Masukkan Kode Kategori Baru :", 0, (string)$kodeSekarang);
            if ($kodeBaru != $kodeSekarang && Category::withTrashed()->where('code', $kodeBaru)->exists()) {
                $this->command->error("Kode '{$kodeBaru}' sudah digunakan! Silakan masukkan kode yang berbeda.");
            } else {
                return $kodeBaru;
            }
        } while (true);
    }

    private function tanyaNamaBaru(string $namaSekarang): string
    {
        do {
            $nama = $this->command->ask("Masukkan Nama Kategori : ", $namaSekarang);
            if (empty(trim($nama))) {
                $this->command->error("Nama tidak boleh kosong!");
            } else {
                return $nama;
            }
        } while (true);
    }
}
