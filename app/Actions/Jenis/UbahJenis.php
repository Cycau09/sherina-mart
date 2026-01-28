<?php

declare(strict_types=1);

namespace App\Actions\Jenis;

use App\Models\Variety;
use App\Support\BaseAction;

/**
 * Sesuai Menu: "Ubah Jenis Barang"
 */
class UbahJenis extends BaseAction
{
    public function jalankan(): void
    {
        $this->command->info("Ubah Jenis Barang");
        
        $code = $this->tanyaAngka("Masukkan Kode Jenis yang akan diubah :");
        $variety = Variety::where('code', $code)->first();

        if (!$variety) {
            $this->command->error("Jenis dengan kode {$code} tidak ditemukan!");
            return;
        }

        $variety->code = $this->tanyaKodeBaru($variety->code);
        $variety->name = $this->tanyaNamaBaru($variety->name);

        if ($variety->save()) {
            $this->command->notify("Success", "data berhasil diubah");
        } else {
            $this->command->notify("Failed", "data gagal diubah");
        }
    }

    private function tanyaKodeBaru(int $kodeSekarang): int
    {
        do {
            $kodeBaru = $this->tanyaAngka("Masukkan Kode Jenis Baru :", 0, (string)$kodeSekarang);
            if ($kodeBaru != $kodeSekarang && Variety::withTrashed()->where('code', $kodeBaru)->exists()) {
                $this->command->error("Kode '{$kodeBaru}' sudah digunakan! Silakan masukkan kode yang berbeda.");
            } else {
                return $kodeBaru;
            }
        } while (true);
    }

    private function tanyaNamaBaru(string $namaSekarang): string
    {
        do {
            $nama = $this->command->ask("Masukkan Nama Jenis : ", $namaSekarang);
            if (empty(trim($nama))) {
                $this->command->error("Nama tidak boleh kosong!");
            } else {
                return $nama;
            }
        } while (true);
    }
}
