<?php

declare(strict_types=1);

namespace App\Actions\Jenis;

use App\Models\Variety;
use App\Support\BaseAction;

/**
 * Sesuai Menu: "Tambah Jenis Barang"
 */
class TambahJenis extends BaseAction
{
    public function jalankan(): void
    {
        $this->command->info("Tambah Jenis Barang");
        
        $variety = new Variety();
        $variety->code = $this->tanyaKodeUnik();
        $variety->name = $this->tanyaNama();

        if ($variety->save()) {
            $this->command->notify("Success", "data berhasil disimpan");
        } else {
            $this->command->notify("Failed", "data gagal disimpan");
        }
    }

    private function tanyaKodeUnik(): int
    {
        do {
            $code = $this->tanyaAngka("Masukkan Kode Jenis :");
            if (Variety::withTrashed()->where('code', $code)->exists()) {
                $this->command->error("Kode '{$code}' sudah pernah digunakan (mungkin di data terhapus). Silakan gunakan kode lain.");
            } else {
                return $code;
            }
        } while (true);
    }

    private function tanyaNama(): string
    {
        do {
            $name = $this->command->ask("Masukkan Nama Jenis : ");
            if (empty(trim($name))) {
                $this->command->error("Nama tidak boleh kosong!");
            } else {
                return $name;
            }
        } while (true);
    }
}
