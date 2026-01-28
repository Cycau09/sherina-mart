<?php

declare(strict_types=1);

namespace App\Actions\Barang;

use App\Models\Product;
use App\Models\Category;
use App\Models\Variety;
use App\Support\BaseAction;
use function Laravel\Prompts\select;

/**
 * Sesuai Menu: "Ubah Barang"
 */
class UbahBarang extends BaseAction
{
    public function jalankan(): void
    {
        $this->command->info("Ubah Barang");
        
        $code = $this->tanyaAngka("Masukkan Kode Barang yang akan diubah :");
        $product = Product::where('code', $code)->first();

        if (!$product) {
            $this->command->error("Barang dengan kode {$code} tidak ditemukan!");
            return;
        }

        $categories = Category::all()->pluck('name', 'id')->toArray();
        $product->category_id = select(label: 'Pilih Kategori Barang:', options: $categories);

        $varieties = Variety::all()->pluck('name', 'id')->toArray();
        $product->variety_id = select(label: 'Pilih Jenis Barang:', options: $varieties);

        $product->code = $this->tanyaKodeBaru($product->code);
        $product->name = $this->command->ask("Masukkan Nama Barang : ", $product->name);
        $product->price = $this->tanyaAngka("Masukkan Harga Barang (harus > 0) :", 1, (string)$product->price);
        $product->stock = $this->tanyaAngka("Masukkan Jumlah Stok (harus >= 0) :", 0, (string)$product->stock);

        if ($product->save()) {
            $this->command->notify("Success", "data berhasil diubah");
        } else {
            $this->command->notify("Failed", "data gagal diubah");
        }
    }

    private function tanyaKodeBaru(string $kodeSekarang): string
    {
        do {
            $kodeBaru = $this->command->ask("Masukkan Kode Barang Baru :", $kodeSekarang);
            if (empty($kodeBaru)) {
                $this->command->error("Kode tidak boleh kosong!");
                continue;
            }
            if ($kodeBaru != $kodeSekarang && Product::withTrashed()->where('code', $kodeBaru)->exists()) {
                $this->command->error("Kode '{$kodeBaru}' sudah digunakan oleh produk lain! Silakan masukkan kode yang berbeda.");
            } else {
                return $kodeBaru;
            }
        } while (true);
    }
}
