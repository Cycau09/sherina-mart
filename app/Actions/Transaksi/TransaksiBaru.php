<?php

declare(strict_types=1);

namespace App\Actions\Transaksi;

use App\Models\Product;
use App\Models\SaleTransaction;
use App\Support\BaseAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use function Laravel\Prompts\select;

/**
 * Action untuk Transaksi Pembelian Baru (Kasir) dengan Keranjang Belanja
 * Sesuai Submenu: "Transaksi Pembelian Baru"
 */
class TransaksiBaru extends BaseAction
{
    private array $cart = [];
    private int $cartTotal = 0;
    private string $cartTitle = "Keranjang Belanja (Kosong)";

    public function jalankan(): void
    {
        // Loop keranjang belanja sampai checkout atau batal
        while (true) {
            $this->command->line("");
            $this->command->info($this->cartTitle);

            $action = select(
                label: 'Aksi Keranjang:',
                options: [
                    'add' => '+ Tambah Barang (Daftar)',
                    'search' => '🔍 Cari Barang (Nama/Kode)',
                    'view' => '🛒 Lihat Keranjang',
                    'edit' => '⚙️ Kelola Keranjang (Ubah/Hapus Item)',
                    'checkout' => '💰 Bayar / Checkout 💰',
                    'cancel' => '⬅ Batal & Kembali',
                ],
                default: 'add'
            );

            // Handle aksi sesuai pilihan
            if ($action === 'cancel') {
                if ($this->handleCancel()) return;
            } elseif ($action === 'add') {
                $this->handleTambahDariDaftar();
            } elseif ($action === 'search') {
                $this->handleCariBarang();
            } elseif ($action === 'view') {
                $this->handleLihatKeranjang();
            } elseif ($action === 'edit') {
                $this->handleKelolaKeranjang();
            } elseif ($action === 'checkout') {
                if ($this->handleCheckout()) return;
            }
        }
    }

    private function handleCancel(): bool
    {
        if (count($this->cart) > 0) {
            if (!$this->command->confirm("Keranjang tidak kosong. Yakin ingin membatalkan transaksi?", false)) {
                return false;
            }
        }
        return true; // Kembali ke menu utama
    }

    private function handleTambahDariDaftar(): void
    {
        $products = Product::where('stock', '>', 0)->get();

        if ($products->isEmpty()) {
            $this->command->error("Tidak ada barang tersedia (Stok Habis).");
            return;
        }

        $prodOptions = [];
        foreach ($products as $p) {
            $prodOptions[$p->id] = "[{$p->code}] {$p->name} - {$this->formatRupiah($p->price)} (Stok: {$p->stock})";
        }
        $prodOptions['back'] = '<< Kembali';

        $prodId = select(
            label: 'Pilih Barang:',
            options: $prodOptions,
            scroll: 10
        );

        if ($prodId === 'back' || $prodId === null) {
            return;
        }

        $this->tambahKeKeranjang($products->find($prodId));
    }

    private function handleCariBarang(): void
    {
        $query = $this->command->ask("Masukkan Nama atau Kode Barang :");
        if (empty($query)) return;

        $products = Product::where('stock', '>', 0)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%");
            })->get();

        if ($products->isEmpty()) {
            $this->command->error("Barang dengan kata kunci '{$query}' tidak ditemukan atau stok habis.");
            return;
        }

        $prodOptions = [];
        foreach ($products as $p) {
            $prodOptions[$p->id] = "[{$p->code}] {$p->name} - {$this->formatRupiah($p->price)} (Stok: {$p->stock})";
        }
        $prodOptions['back'] = '<< Kembali';

        $prodId = select(label: 'Hasil Pencarian:', options: $prodOptions, scroll: 10);
        if ($prodId === 'back' || $prodId === null) return;

        $this->tambahKeKeranjang($products->find($prodId));
    }

    private function tambahKeKeranjang($product): void
    {
        $qty = $this->tanyaAngka("Jumlah beli (Stok: {$product->stock}):", 1);

        if ($qty > $product->stock) {
            $this->command->error("Stok tidak cukup! Tersedia: {$product->stock}");
            return;
        }

        $subtotal = $product->price * $qty;
        $this->cart[] = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => $qty,
            'subtotal' => $subtotal
        ];

        // CATATAN PENTING: Stok TIDAK dikurangi di sini!
        // Stok hanya dikurangi saat checkout berhasil (menggunakan DB Transaction)

        $this->cartTotal += $subtotal;
        $this->updateCartTitle();
        $this->command->info("Berhasil ditambahkan ke keranjang.");
    }

    private function handleLihatKeranjang(): void
    {
        if (empty($this->cart)) {
            $this->command->info("Keranjang masih kosong.");
        } else {
            $headers = ['Nama', 'Harga', 'Qty', 'Subtotal'];
            $data = array_map(function ($item) {
                return [
                    $item['product_name'],
                    $this->formatRupiah($item['price']),
                    $item['quantity'],
                    $this->formatRupiah($item['subtotal'])
                ];
            }, $this->cart);
            $this->command->table($headers, $data);
            $this->command->info("Total Sementara: " . $this->formatRupiah($this->cartTotal));
        }
    }

    private function handleKelolaKeranjang(): void
    {
        if (empty($this->cart)) {
            $this->command->error("Keranjang masih kosong!");
            return;
        }

        $editOptions = [];
        foreach ($this->cart as $index => $item) {
            $editOptions[$index] = "{$item['product_name']} (Qty: {$item['quantity']}) - Subtotal: {$this->formatRupiah($item['subtotal'])}";
        }
        $editOptions['back'] = '<< Kembali';

        $selectedIndex = select(label: 'Pilih Item untuk Dikelola:', options: $editOptions, scroll: 10);
        if ($selectedIndex === 'back' || $selectedIndex === null) return;

        $subAction = select(
            label: 'Aksi Item:',
            options: [
                'change' => 'Ubah Jumlah (Qty)',
                'remove' => 'Hapus dari Keranjang',
                'back' => 'Kembali'
            ]
        );

        if ($subAction === 'back') return;

        if ($subAction === 'remove') {
            $this->hapusDariKeranjang($selectedIndex);
        }

        if ($subAction === 'change') {
            $this->ubahJumlahItem($selectedIndex);
        }

        $this->updateCartTitle();
    }

    private function hapusDariKeranjang(int $index): void
    {
        $this->cartTotal -= $this->cart[$index]['subtotal'];
        $this->command->info("{$this->cart[$index]['product_name']} dihapus dari keranjang.");
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart); // Re-index array
    }

    private function ubahJumlahItem(int $index): void
    {
        $item = $this->cart[$index];
        $prod = Product::find($item['product_id']);
        $newQty = $this->tanyaAngka("Masukkan Jumlah Baru (Stok: {$prod->stock}):", 1);

        if ($newQty > $prod->stock) {
            $this->command->error("Gagal! Stok tidak cukup. Tersedia: {$prod->stock}");
        } else {
            $this->cartTotal -= $item['subtotal'];
            $this->cart[$index]['quantity'] = $newQty;
            $this->cart[$index]['subtotal'] = $item['price'] * $newQty;
            $this->cartTotal += $this->cart[$index]['subtotal'];
            $this->command->info("Jumlah berhasil diubah.");
        }
    }

    private function handleCheckout(): bool
    {
        if (empty($this->cart)) {
            $this->command->error("Keranjang kosong!");
            return false;
        }

        $this->command->info("=== CHECKOUT ===");
        $this->command->info("Total Tagihan: " . $this->formatRupiah($this->cartTotal));

        // Input pembayaran
        $payment = $this->inputPembayaran();
        $kembali = $payment - $this->cartTotal;
        $this->command->info("Kembalian: " . $this->formatRupiah($kembali));

        $saleId = (string) Str::uuid();

        // Simpan transaksi ke database (dengan DB Transaction untuk keamanan)
        if (!$this->simpanTransaksi($saleId)) {
            return false; // Gagal, kembali ke keranjang
        }

        // Tampilkan dan simpan struk
        $this->tampilkanStruk($saleId, $payment, $kembali);

        return true; // Selesai, kembali ke menu utama
    }

    private function inputPembayaran(): int
    {
        $payment = 0;
        while ($payment < $this->cartTotal) {
            $payment = $this->tanyaAngka("Masukkan Jumlah Uang Tunai:", 0);
            if ($payment < $this->cartTotal) {
                $this->command->error("Uang kurang! Kurang: " . $this->formatRupiah($this->cartTotal - $payment));
            }
        }
        return $payment;
    }

    private function simpanTransaksi(string $saleId): bool
    {
        // DATABASE TRANSACTION - Menjamin atomicity (semua sukses atau rollback)
        // Stok HANYA dikurangi di checkout, bukan saat add to cart
        try {
            DB::beginTransaction();

            foreach ($this->cart as $item) {
                // Cek stok sekali lagi sebelum menyimpan (final check)
                $prod = Product::find($item['product_id']);
                if ($prod->stock < $item['quantity']) {
                    throw new \Exception("Gagal! Stok '{$prod->name}' tidak cukup (Tersedia: {$prod->stock}).");
                }

                // Simpan transaksi penjualan
                $trx = new SaleTransaction();
                $trx->sale_id = $saleId;
                $trx->product_id = $item['product_id'];
                $trx->price = $item['price'];
                $trx->quantity = $item['quantity'];
                $trx->save();

                // Kurangi stok secara permanen
                $prod->stock -= $item['quantity'];
                $prod->save();
            }

            DB::commit(); // Commit semua perubahan ke database
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua perubahan jika ada error
            $this->command->error($e->getMessage());
            $this->command->info("Transaksi dibatalkan. Mengembalikan ke menu utama.");
            return false;
        }
    }

    private function tampilkanStruk(string $saleId, int $payment, int $kembali): void
    {
        $strukContent = $this->generateStruk($saleId, $payment, $kembali);
        $this->command->line($strukContent);

        if ($this->command->confirm("Simpan struk?", true)) {
            $strukDir = database_path('struk');
            if (!file_exists($strukDir))
                mkdir($strukDir, 0755, true);
            $file = $strukDir . '/struk_cart_' . date('YmdHis') . '.txt';
            file_put_contents($file, $strukContent);
            $this->command->info("Struk tersimpan: $file");
        }
    }

    private function generateStruk(string $saleId, int $payment, int $kembali): string
    {
        $tanggal = date('d/m/Y H:i:s');
        $struk = "\n";
        $struk .= "========================================\n";
        $struk .= "           SHERINA MART                 \n";
        $struk .= "     Jl.  No. 123, Kota abc             \n";
        $struk .= "          Telp: 0812-xxxx-xxxx         \n";
        $struk .= "========================================\n";
        $struk .= "Tanggal       : {$tanggal}\n";
        $struk .= "No. Transaksi : " . substr($saleId, 0, 8) . "\n";
        $struk .= "----------------------------------------\n";
        $struk .= "ITEM                  QTY      TOTAL    \n";
        $struk .= "----------------------------------------\n";

        foreach ($this->cart as $item) {
            $nama = substr($item['product_name'], 0, 18);
            $qty = $item['quantity'];
            $subtotal = $this->formatRupiah($item['price'] * $item['quantity']);
            $line1 = str_pad($nama, 20);
            $line2 = str_pad((string) $qty, 5, " ", STR_PAD_BOTH);
            $line3 = str_pad($subtotal, 13, " ", STR_PAD_LEFT);
            $struk .= "{$line1}{$line2}{$line3}\n";
        }

        $struk .= "----------------------------------------\n";
        $struk .= "TOTAL TAGIHAN : " . str_pad($this->formatRupiah($this->cartTotal), 22, " ", STR_PAD_LEFT) . "\n";
        $struk .= "TUNAI         : " . str_pad($this->formatRupiah($payment), 22, " ", STR_PAD_LEFT) . "\n";
        $struk .= "KEMBALI       : " . str_pad($this->formatRupiah($kembali), 22, " ", STR_PAD_LEFT) . "\n";
        $struk .= "========================================\n";
        $struk .= "      Terima Kasih Atas Kunjungan      \n";
        $struk .= "           Anda - Sherina Mart         \n";
        $struk .= "========================================\n";

        return $struk;
    }

    private function updateCartTitle(): void
    {
        $this->cartTitle = "Keranjang Belanja: " . count($this->cart) . " Item - Total: " . $this->formatRupiah($this->cartTotal);
    }
}
