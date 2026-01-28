<?php

declare(strict_types=1);

namespace App\Actions\Kategori;

use App\Models\Category;
use App\Support\BaseAction;
use function Laravel\Prompts\select;

/**
 * Action untuk menampilkan daftar kategori dengan fitur pencarian
 * Sesuai Menu: "Daftar Kategori Barang"
 */
class DaftarKategori extends BaseAction
{
    public function jalankan(): void
    {
        $this->command->info("Daftar Kategori Barang");
        $categories = Category::all();

        if ($categories->isEmpty()) {
            $this->command->error("Belum ada data Kategori.");
            return;
        }

        // ========== MULAI: Submenu Kategori dengan Fitur Pencarian ==========
        $options = ['search' => '🔍 Cari Kategori (Nama/Kode)'];
        foreach ($categories as $cat) {
            $options[$cat->id] = "[{$cat->code}] {$cat->name}";
        }
        $options['back'] = "⬅ Kembali ke Menu Utama";

        while (true) {
            $selectedId = select(
                label: 'Pilih atau Cari Kategori:',
                options: $options,
                scroll: 10
            );

            if ($selectedId === 'search') {
                $selectedId = $this->cariKategori($categories);
                if ($selectedId === null) continue;
            }

            if ($selectedId === null || $selectedId === 'back' || $selectedId === '') {
                $this->command->info("Kembali ke Menu Utama...");
                return;
            }

            // Tampilkan Detail
            $this->tampilkanDetail($categories->firstWhere('id', $selectedId));

            // Prompt Lanjut
            $nextAction = select(
                label: 'Aksi Selanjutnya:',
                options: [
                    'search' => 'Cari Lagi',
                    'menu' => 'Kembali ke Menu Utama'
                ],
                default: 'search'
            );

            if ($nextAction === 'menu' || $nextAction === null || $nextAction === '') {
                $this->command->info("Kembali ke Menu Utama...");
                return;
            }

            $this->command->line("");
        }
        // ========== AKHIR: Submenu Kategori dengan Fitur Pencarian ==========
    }

    private function cariKategori($categories): ?string
    {
        $query = $this->command->ask("Masukkan Nama atau Kode Kategori :");
        if (empty($query)) return null;

        $filtered = $categories->filter(function ($c) use ($query) {
            return str_contains(strtolower((string)$c->name), strtolower($query)) ||
                str_contains(strtolower((string)$c->code), strtolower($query));
        });

        if ($filtered->isEmpty()) {
            $this->command->error("Kategori '{$query}' tidak ditemukan.");
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

    private function tampilkanDetail($category): void
    {
        $this->command->line("");
        $this->command->info("=== DETAIL KATEGORI ===");
        $this->command->line("Kode   : " . $category->code);
        $this->command->line("Nama   : " . $category->name);
        $this->command->line("Dibuat : " . $category->created_at);
        $this->command->line("Diubah : " . $category->updated_at);
        $this->command->line("=======================");
    }
}
