<?php

declare(strict_types=1);

namespace App\Actions\Jenis;

use App\Models\Variety;
use App\Models\Product;
use App\Support\BaseAction;

/**
 * Sesuai Menu: "Hapus Jenis Barang"
 */
class HapusJenis extends BaseAction
{
    public function jalankan(): void
    {
        $this->command->info("Hapus Jenis Barang");
        
        $code = $this->tanyaAngka("Masukkan Kode Jenis yang akan dihapus :");
        $variety = Variety::where('code', $code)->first();

        if (!$variety) {
            $this->command->error("Jenis dengan kode {$code} tidak ditemukan!");
            return;
        }

        if (!$this->command->confirm("Apakah Anda yakin ingin menghapus jenis '{$variety->name}'?")) {
            $this->command->info("Penghapusan dibatalkan.");
            return;
        }

        $jumlahBarang = Product::where('variety_id', $variety->id)->count();
        if ($jumlahBarang > 0) {
            $this->command->error("GAGAL: Jenis tidak dapat dihapus karena masih memiliki {$jumlahBarang} barang.");
            $this->command->line("Silakan hapus atau ubah jenis barang terlebih dahulu.");
            return;
        }

        if ($variety->delete()) {
            $this->command->notify("Success", "data berhasil dihapus");
        } else {
            $this->command->notify("Failed", "data gagal dihapus");
        }
    }
}
