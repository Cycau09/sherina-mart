<?php

declare(strict_types=1);

namespace App\Actions\Barang;

use App\Models\Product;
use App\Models\Category;
use App\Models\Variety;
use App\Support\BaseAction;
use function Laravel\Prompts\select;

/**
 * Sesuai Menu: "Tambah Barang"
 */
class TambahBarang extends BaseAction
{
    public function jalankan(): void
    {
        $this->command->info("Tambah Barang");
        
        $product = new Product();

        // Pilih Kategori
        $categories = Category::all()->pluck('name', 'id')->toArray();
        if (empty($categories)) {
            $this->command->error("Belum ada Kategori! Silakan tambahkan Kategori terlebih dahulu.");
            return;
        }
        $product->category_id = select(label: 'Pilih Kategori Barang:', options: $categories);

        // Pilih Jenis
        $varieties = Variety::all()->pluck('name', 'id')->toArray();
        if (empty($varieties)) {
            $this->command->error("Belum ada Jenis Barang! Silakan tambahkan Jenis terlebih dahulu.");
            return;
        }
        $product->variety_id = select(label: 'Pilih Jenis Barang:', options: $varieties);

        // Input Data
        $product->code = $this->tanyaKodeUnik();
        $product->name = $this->command->ask("Masukkan Nama Barang : ");
        $product->price = $this->tanyaAngka("Masukkan Harga Barang (harus > 0) :", 1);
        $product->stock = $this->tanyaAngka("Masukkan Jumlah Stok (harus >= 0) :", 0);

        if ($product->save()) {
            $this->command->notify("Success", "data berhasil disimpan");
        } else {
            $this->command->notify("Failed", "data gagal disimpan");
        }
    }

    private function tanyaKodeUnik(): string
    {
        do {
            $code = $this->command->ask("Masukkan Kode Barang :");
            if (empty($code)) {
                $this->command->error("Kode tidak boleh kosong!");
                continue;
            }
            if (Product::withTrashed()->where('code', $code)->exists()) {
                $this->command->error("Kode '{$code}' sudah digunakan (mungkin di data terhapus)! Silakan masukkan kode lain.");
            } else {
                return $code;
            }
        } while (true);
    }
}
