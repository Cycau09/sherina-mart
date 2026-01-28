<?php

declare(strict_types=1);

namespace App\Actions\Jenis;

use App\Models\Variety;
use App\Support\BaseAction;
use function Laravel\Prompts\select;

/**
 * Action untuk menampilkan daftar jenis barang dengan fitur pencarian
 * Sesuai Menu: "Daftar Jenis Barang"
 */
class DaftarJenis extends BaseAction
{
    public function jalankan(): void
    {
        $this->command->info("Daftar Jenis Barang");
        $varieties = Variety::all();

        if ($varieties->isEmpty()) {
            $this->command->error("Belum ada data Jenis Barang.");
            return;
        }

        $options = ['search' => '🔍 Cari Jenis (Nama/Kode)'];
        foreach ($varieties as $var) {
            $options[$var->id] = "[{$var->code}] {$var->name}";
        }
        $options['back'] = "⬅ Kembali ke Menu Utama";

        while (true) {
            $selectedId = select(
                label: 'Pilih atau Cari Jenis Barang:',
                options: $options,
                scroll: 10
            );

            if ($selectedId === 'search') {
                $selectedId = $this->cariJenis($varieties);
                if ($selectedId === null) continue;
            }

            if ($selectedId === null || $selectedId === 'back' || $selectedId === '') {
                $this->command->info("Kembali ke Menu Utama...");
                return;
            }

            $this->tampilkanDetail($varieties->firstWhere('id', $selectedId));

            $nextAction = select(
                label: 'Aksi Selanjutnya:',
                options: ['search' => 'Cari Lagi', 'menu' => 'Kembali ke Menu Utama'],
                default: 'search'
            );

            if ($nextAction === 'menu' || $nextAction === null || $nextAction === '') {
                $this->command->info("Kembali ke Menu Utama...");
                return;
            }
            $this->command->line("");
        }
    }

    private function cariJenis($varieties): ?string
    {
        $query = $this->command->ask("Masukkan Nama atau Kode Jenis :");
        if (empty($query)) return null;

        $filtered = $varieties->filter(function ($v) use ($query) {
            return str_contains(strtolower((string)$v->name), strtolower($query)) ||
                str_contains(strtolower((string)$v->code), strtolower($query));
        });

        if ($filtered->isEmpty()) {
            $this->command->error("Jenis '{$query}' tidak ditemukan.");
            return null;
        }

        $searchSubOptions = [];
        foreach ($filtered as $f) {
            $searchSubOptions[$f->id] = "[{$f->code}] {$f->name}";
        }
        $searchSubOptions['back'] = '<< Kembali';
        $selectedId = select(label: 'Hasil Pencarian:', options: $searchSubOptions);
        return ($selectedId === 'back' || $selectedId === null) ? null : $selectedId;
    }

    private function tampilkanDetail($variety): void
    {
        $this->command->line("");
        $this->command->info("=== DETAIL JENIS BARANG ===");
        $this->command->line("Kode   : " . $variety->code);
        $this->command->line("Nama   : " . $variety->name);
        $this->command->line("Dibuat : " . $variety->created_at);
        $this->command->line("Diubah : " . $variety->updated_at);
        $this->command->line("===========================");
    }
}
