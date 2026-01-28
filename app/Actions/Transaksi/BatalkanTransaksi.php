<?php

declare(strict_types=1);

namespace App\Actions\Transaksi;

use App\Models\SaleTransaction;
use App\Support\BaseAction;
use function Laravel\Prompts\select;

/**
 * Action untuk membatalkan transaksi (Void)
 * Sesuai Submenu: "Batalkan Transaksi (Void)"
 */
class BatalkanTransaksi extends BaseAction
{
    public function jalankan(): void
    {
        $this->command->info("Mode Pembatalan Transaksi");
        $transactions = SaleTransaction::with('product')->latest()->take(10)->get();

        if ($transactions->isEmpty()) {
            $this->command->error("Belum ada transaksi yang dapat dibatalkan.");
            return;
        }

        // Siapkan pilihan transaksi dengan fitur pencarian
        $choices = ['search' => '🔍 Cari Transaksi (Nama Produk)'];
        foreach ($transactions as $t) {
            $time = $t->created_at->format('d/m H:i');
            $productName = $t->product ? $t->product->name : 'Produk Terhapus';
            $label = "[{$time}] {$productName} - {$this->formatRupiah($t->price)} x {$t->quantity}";
            $choices[$t->id] = $label;
        }
        $choices['cancel'] = 'Batal & Kembali';

        $selectedId = select(
            label: 'Pilih atau Cari Transaksi untuk dibatalkan:',
            options: $choices,
            scroll: 10
        );

        // Fitur pencarian transaksi
        if ($selectedId === 'search') {
            $selectedId = $this->cariTransaksi();
            if ($selectedId === null) return;
        }

        if ($selectedId === 'cancel' || $selectedId === null) {
            return;
        }

        // Proses pembatalan transaksi
        $this->prosesBatalkan($selectedId);
    }

    private function cariTransaksi(): ?string
    {
        $query = $this->command->ask("Masukkan Nama Produk :");
        if (empty($query)) return null;

        $foundTrx = SaleTransaction::with(['product' => function($q) { $q->withTrashed(); }])
            ->whereHas('product', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })->latest()->take(20)->get();

        if ($foundTrx->isEmpty()) {
            $this->command->error("Transaksi dengan produk '{$query}' tidak ditemukan.");
            return null;
        }

        $searchChoices = [];
        foreach ($foundTrx as $ft) {
            $time = $ft->created_at->format('d/m H:i');
            $name = $ft->product ? $ft->product->name : 'Produk Terhapus';
            $searchChoices[$ft->id] = "[{$time}] {$name} - {$this->formatRupiah($ft->price)} x {$ft->quantity}";
        }
        $searchChoices['back'] = '<< Kembali';
        
        $selectedId = select(label: 'Hasil Pencarian (20 Terakhir):', options: $searchChoices);
        return ($selectedId === 'back' || $selectedId === null) ? null : $selectedId;
    }

    private function prosesBatalkan(string $transactionId): void
    {
        $trx = SaleTransaction::find($transactionId);
        $productName = $trx->product ? $trx->product->name : 'Produk Terhapus';
        
        if ($this->command->confirm("Yakin batalkan transaksi {$productName}?", true)) {
            // Kembalikan stok jika produk masih ada
            if ($trx->product) {
                $trx->product->stock += $trx->quantity;
                $trx->product->save();
            }
            $trx->delete();
            $this->command->info("Transaksi berhasil dibatalkan (Void). Stok dikembalikan.");
        }
    }
}
