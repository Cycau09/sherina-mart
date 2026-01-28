<?php

declare(strict_types=1);

namespace App\Actions\Barang;

use App\Models\Product;
use App\Support\BaseAction;
use function Laravel\Prompts\select;

/**
 * Sesuai Menu: "Daftar Barang"
 */
class DaftarBarang extends BaseAction
{
    public function jalankan(): void
    {
        $this->command->info("Daftar Barang");
        $products = Product::with(['category', 'variety'])->get();

        if ($products->isEmpty()) {
            $this->command->error("Belum ada data Barang.");
            return;
        }

        $options = ['search' => '🔍 Cari Barang (Nama/Kode/Barcode)'];
        foreach ($products as $prod) {
            $label = "[{$prod->code}] {$prod->name} - " . $this->formatRupiah($prod->price) . " (Stok: {$prod->stock})";
            $options[$prod->id] = $label;
        }
        $options['back'] = "⬅ Kembali ke Menu Utama";

        while (true) {
            $selectedId = select(
                label: 'Pilih atau Cari Barang:',
                options: $options,
                scroll: 10
            );

            if ($selectedId === 'search') {
                $selectedId = $this->cariBarang($products);
                if ($selectedId === null) continue;
            }

            if ($selectedId === null || $selectedId === 'back' || $selectedId === '') {
                $this->command->info("Kembali ke Menu Utama...");
                return;
            }

            $this->tampilkanDetail($products->firstWhere('id', $selectedId));

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

    private function cariBarang($products): ?string
    {
        $query = $this->command->ask("Masukkan Nama/Kode/Barcode :");
        if (empty($query)) return null;

        $filtered = $products->filter(function ($p) use ($query) {
            return str_contains(strtolower((string)$p->name), strtolower($query)) ||
                str_contains(strtolower((string)$p->code), strtolower($query));
        });

        if ($filtered->isEmpty()) {
            $this->command->error("Barang '{$query}' tidak ditemukan.");
            return null;
        }

        $searchSubOptions = [];
        foreach ($filtered as $f) {
            $searchSubOptions[$f->id] = "[{$f->code}] {$f->name} (Stok: {$f->stock})";
        }
        $searchSubOptions['back'] = '<< Kembali';
        $selectedId = select(label: 'Hasil Pencarian:', options: $searchSubOptions);
        return ($selectedId === 'back' || $selectedId === null) ? null : $selectedId;
    }

    private function tampilkanDetail($product): void
    {
        $catName = $product->category ? $product->category->name : '-';
        $varName = $product->variety ? $product->variety->name : '-';

        $this->command->line("");
        $this->command->info("=== DETAIL BARANG ===");
        $this->command->line("Kode     : " . $product->code);
        $this->command->line("Nama     : " . $product->name);
        $this->command->line("Harga    : " . $this->formatRupiah($product->price));
        $this->command->line("Stok     : " . $product->stock);
        $this->command->line("Kategori : " . $catName);
        $this->command->line("Jenis    : " . $varName);
        $this->command->line("Dibuat   : " . $product->created_at);
        $this->command->line("Diubah   : " . $product->updated_at);
        $this->command->line("=====================");
    }
}
