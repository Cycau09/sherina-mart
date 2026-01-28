<?php

declare(strict_types=1);

namespace App\Actions\Kategori;

use App\Models\Category;
use App\Models\Product;
use App\Support\BaseAction;

/**
 * Action untuk menghapus kategori
 * Sesuai Menu: "Hapus Kategori Barang"
 */
class HapusKategori extends BaseAction
{
    public function jalankan(): void
    {
        $this->command->info("Hapus Kategori Barang");
        
        $code = $this->tanyaAngka("Masukkan Kode Kategori yang akan dihapus :");
        $category = Category::where('code', $code)->first();

        if (!$category) {
            $this->command->error("Kategori dengan kode {$code} tidak ditemukan!");
            return;
        }

        if (!$this->command->confirm("Apakah Anda yakin ingin menghapus kategori '{$category->name}'?")) {
            $this->command->info("Penghapusan dibatalkan.");
            return;
        }

        // CEK INTEGRITAS DATA
        $jumlahBarang = Product::where('category_id', $category->id)->count();
        if ($jumlahBarang > 0) {
            $this->command->error("GAGAL: Kategori tidak dapat dihapus karena masih memiliki {$jumlahBarang} barang.");
            $this->command->line("Silakan hapus atau ubah kategori barang terlebih dahulu.");
            return;
        }

        if ($category->delete()) {
            $this->command->notify("Success", "data berhasil dihapus");
        } else {
            $this->command->notify("Failed", "data gagal dihapus");
        }
    }
}
