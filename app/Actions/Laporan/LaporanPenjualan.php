<?php

declare(strict_types=1);

namespace App\Actions\Laporan;

use App\Models\SaleTransaction;
use App\Support\BaseAction;
use Illuminate\Support\Facades\DB;
use function Laravel\Prompts\select;

/**
 * Action untuk menampilkan Laporan Penjualan dengan filter dan rekapitulasi
 * Sesuai Menu: "Daftar Penjualan Barang"
 */
class LaporanPenjualan extends BaseAction
{
    public function jalankan(): void
    {
        $this->command->info("Daftar Penjualan Barang");

        // Filter laporan agar tidak lag (untuk data banyak)
        $filter = select(
            label: 'Filter Laporan:',
            options: [
                'today' => '📅 Hari Ini',
                'recent' => '🕒 20 Transaksi Terakhir',
                'all' => '📊 Semua (Bisa berat jika data banyak)',
                'back' => '⬅ Kembali'
            ],
            default: 'today'
        );

        if ($filter === 'back') return;

        // Ambil data dengan filter
        $sales = $this->ambilDataPenjualan($filter);

        if ($sales->isEmpty()) {
            $this->command->info("Belum ada data penjualan untuk filter ini.");
            $this->command->ask("Tekan Enter untuk kembali...");
            return;
        }

        // Tampilkan laporan
        $this->tampilkanTabel($sales);
        $this->tampilkanRekapitulasi();

        // Fitur ekspor
        $this->command->line("");
        if ($this->command->confirm("Apakah Anda ingin mengekspor laporan ini ke file .txt?", true)) {
            $this->eksporLaporan($sales);
        }

        $this->command->ask("Tekan Enter untuk kembali ke menu utama");
    }

    private function ambilDataPenjualan(string $filter)
    {
        $query = SaleTransaction::with(['product' => function($q) { 
            $q->withTrashed()->with('category'); 
        }])->latest();

        if ($filter === 'today') {
            $query->where('created_at', '>=', now()->startOfDay());
        } elseif ($filter === 'recent') {
            $query->limit(20);
        }

        return $query->get();
    }

    private function tampilkanTabel($sales): void
    {
        $headers = ['Kode', 'Nama Produk', 'Harga Satuan', 'Qty', 'Total Bayar', 'Waktu Transaksi'];
        $dataRows = $sales->map(function ($item) {
            return [
                'Kode' => $item->product ? $item->product->code : 'DEL',
                'Nama Produk' => $item->product ? $item->product->name : 'Deleted',
                'Harga Satuan' => $this->formatRupiah($item->price),
                'Qty' => $item->quantity,
                'Total Bayar' => $this->formatRupiah($item->quantity * $item->price),
                'Waktu Transaksi' => $item->created_at->format('d-m-Y H:i:s'),
            ];
        })->toArray();

        $this->command->table($headers, $dataRows);
    }

    private function tampilkanRekapitulasi(): void
    {
        $this->command->line("");
        $this->command->info("--- RINGKASAN REKAPITULASI (Seluruh Waktu) ---");
        
        $today = now()->startOfDay();
        
        // Hitung total pendapatan hari ini
        $todayTotal = SaleTransaction::where('created_at', '>=', $today)
            ->selectRaw('SUM(price * quantity) as total')
            ->value('total') ?? 0;
            
        // Hitung total seluruh pendapatan
        $grandTotal = SaleTransaction::selectRaw('SUM(price * quantity) as total')
            ->value('total') ?? 0;

        $this->command->info("Total Pendapatan Hari Ini: " . $this->formatRupiah((int)$todayTotal));
        $this->command->info("Total Seluruh Pendapatan : " . $this->formatRupiah((int)$grandTotal));

        // Rekap per kategori
        $this->command->line("");
        $this->command->info("Pendapatan per Kategori (Global):");
        
        $catData = DB::table('sale_transactions')
            ->join('products', 'sale_transactions.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as cat_name, sum(sale_transactions.price * sale_transactions.quantity) as total')
            ->groupBy('cat_name')
            ->get();

        foreach ($catData as $item) {
            $this->command->line("- {$item->cat_name}: " . $this->formatRupiah((int)$item->total));
        }
    }

    private function eksporLaporan($sales): void
    {
        $filename = 'laporan_penjualan_' . date('YmdHis') . '.txt';
        $path = database_path('laporan');
        
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $fullPath = $path . DIRECTORY_SEPARATOR . $filename;
        
        // Generate konten laporan
        $content = "========================================\n";
        $content .= "       LAPORAN PENJUALAN SHERINA MART    \n";
        $content .= "       Tanggal Cetak: " . date('d/m/Y H:i:s') . "\n";
        $content .= "========================================\n\n";
        
        $content .= "DETAIL TRANSAKSI:\n";
        $content .= str_pad("Kode", 10) . str_pad("Nama", 20) . str_pad("Harga", 15) . str_pad("Qty", 5) . "Total\n";
        $content .= str_repeat("-", 60) . "\n";
        
        foreach ($sales as $item) {
            $code = $item->product ? $item->product->code : 'DEL';
            $name = $item->product ? $item->product->name : 'Deleted';
            $content .= str_pad((string)$code, 10) . 
                        str_pad(substr($name, 0, 18), 20) . 
                        str_pad($this->formatRupiah($item->price), 15) . 
                        str_pad((string)$item->quantity, 5) . 
                        $this->formatRupiah($item->quantity * $item->price) . "\n";
        }
        
        // Hitung total untuk ekspor
        $grandTotal = SaleTransaction::selectRaw('SUM(price * quantity) as total')->value('total') ?? 0;
        
        $catData = DB::table('sale_transactions')
            ->join('products', 'sale_transactions.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as cat_name, sum(sale_transactions.price * sale_transactions.quantity) as total')
            ->groupBy('cat_name')
            ->get();

        $content .= "\n" . str_repeat("=", 60) . "\n";
        $content .= "RINGKASAN:\n";
        foreach ($catData as $item) {
            $content .= "- {$item->cat_name}: " . $this->formatRupiah((int)$item->total) . "\n";
        }
        $content .= "----------------------------------------\n";
        $content .= "TOTAL SELURUH PENDAPATAN: " . $this->formatRupiah((int)$grandTotal) . "\n";
        $content .= "========================================\n";

        file_put_contents($fullPath, $content);
        $this->command->info("Berhasil! Laporan disimpan di: " . $fullPath);
    }
}
