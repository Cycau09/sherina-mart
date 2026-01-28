<?php

declare(strict_types=1);

namespace App\Actions\Barang;

use App\Models\Product;
use App\Models\SaleTransaction;
use App\Support\BaseAction;

/**
 * Sesuai Menu: "Hapus Barang"
 */
class HapusBarang extends BaseAction
{
    public function jalankan(): void
    {
        $this->command->info("Hapus Barang");
        
        $code = $this->tanyaAngka("Masukkan Kode Barang yang akan dihapus :");
        $product = Product::where('code', $code)->first();

        if (!$product) {
            $this->command->error("Barang dengan kode {$code} tidak ditemukan!");
            return;
        }

        if (!$this->command->confirm("Apakah Anda yakin ingin menghapus barang '{$product->name}'?")) {
            $this->command->info("Penghapusan dibatalkan.");
            return;
        }

        // CEK INTEGRITAS DATA
        $trxCount = SaleTransaction::where('product_id', $product->id)->count();
        if ($trxCount > 0) {
            $this->command->error("GAGAL: Barang tidak dapat dihapus karena memiliki riwayat {$trxCount} transaksi penjualan.");
            $this->command->line("Data barang diperlukan untuk laporan penjualan.");
            return;
        }

        if ($product->delete()) {
            $this->command->notify("Success", "data berhasil dihapus");
        } else {
            $this->command->notify("Failed", "data gagal dihapus");
        }
    }
}
