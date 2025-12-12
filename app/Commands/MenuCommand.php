<?php
declare(strict_types=1);
namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\select;
use App\Models\Category;
use App\Models\Variety;
use App\Models\Product;
use App\Models\SaleTransaction;

class MenuCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:menu-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'menampilkan Menu pada pengguna';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $title = "Selamat di Applikasi Kami\nSilahkan Pilih Menu Berikut :";
        $options = [
            "Pilihan 1: Transaksi Pembelian Barang",
            "Pilihan 2: Daftar Kategori Barang",
            "Pilihan 3: Tambah Kategori Barang",
            "Pilihan 4: Ubah Kategori Barang",
            "Pilihan 5: Hapus Kategori Barang",
            "Pilihan 6: Daftar Jenis Barang",
            "Pilihan 7: Tambah Jenis Barang",
            "Pilihan 8: Ubah Jenis Barang",
            "Pilihan 9: Hapus Jenis Barang",
            "Pilihan 10: Daftar Barang",
            "Pilihan 11: Tambah Barang",
            "Pilihan 12: Ubah Barang",
            "Pilihan 13 : Hapus Barang",
            "Pilihan 14 : Daftar Penjualan Barang",
        ];

        while (true) {
            $option = $this->menu($title, $options)
                ->setForegroundColour("green")
                ->setBackgroundColour("black")
                ->setWidth(200)
                ->setPadding(10)
                ->setMargin(5)
                ->setExitButtonText("Abort")
                ->setTitleSeparator("*-")
                ->open();

            if ($option === null) {
                break;
            }

            // $this->info("Anda Memilih Pilihan : {$option}");
            if ($option == 0) {
                // $this->info("Anda Memilih Pilihan : {$option} Transaksi Pembelian Barang");
                $sale = new SaleTransaction;
                $products = Product::all()->pluck('name', 'id')->toArray();
                $sale->product_id = select(
                    label: 'Pilih Barang:',
                    options: $products,
                );
                $choosen_product = Product::find($sale->product_id);
                $sale->price = $choosen_product->price;

                // Tampilkan info produk dan stok sebelum input
                $this->info("------------------------------------------------");
                $this->info("Barang Dipilih : {$choosen_product->name}");
                $this->info("Stok Tersedia  : {$choosen_product->stock}");
                $this->info("------------------------------------------------");

                // Update prompt input agar user tahu batasnya
                $sale->quantity = (int) $this->ask("Masukkan Jumlah Barang (Minimal: 1 Maksimal: {$choosen_product->stock}) : ");

                // codingan mulai
                // Validasi jumlah minimal (tidak boleh 0 atau negatif)
                if ($sale->quantity <= 0) {
                    $this->error("Jumlah barang tidak valid! Minimal beli 1 barang untuk mencetak struk.");
                    continue;
                }

                // Cek ketersediaan stok
                if ($choosen_product->stock < $sale->quantity) {
                    $this->error("stok kurang harap periksa stok anda! Stok tersisa: {$choosen_product->stock}");
                    continue;
                }

                // Kurangi stok
                $choosen_product->stock -= $sale->quantity;
                $choosen_product->save();
                // codingan ini selesai

                if ($sale->save()) {
                    $this->notify("Success", "data berhasil disimpan");
                    $payment = $sale->price * $sale->quantity;


                    // codingan mulai
                    // Tampilkan total dengan format Rupiah
                    $this->info("Total Bayar: " . $this->formatRupiah($payment));

                    // Generate struk transaksi
                    $struk = $this->generateStruk($sale, $choosen_product);

                    // Tampilkan struk di layar
                    $this->line($struk);

                    // Tanya apakah ingin menyimpan struk
                    $simpanStruk = $this->confirm("Apakah Anda ingin menyimpan struk ke file?", true);

                    if ($simpanStruk) {
                        // Buat folder struk jika belum ada
                        $strukDir = database_path('struk');
                        if (!file_exists($strukDir)) {
                            mkdir($strukDir, 0755, true);
                        }

                        // Simpan struk ke file
                        $filename = 'struk_' . date('Ymd_His') . '_' . $sale->id . '.txt';
                        $filepath = $strukDir . '/' . $filename;
                        file_put_contents($filepath, $struk);

                        $this->info("✓ Struk berhasil disimpan di: {$filepath}");
                    }
                    // codingan ini selesai
                } else {
                    $this->notify("Failed", "data gagal disimpan");
                }

            } else if ($option == 1) {
                $headers = ['kode', 'nama', 'dibuat', 'diubah'];
                $data = Category::all()->map(function ($item) {
                    return [
                        'kode' => $item->code,
                        'nama' => $item->name,
                        'dibuat' => $item->created_at,
                        'diubah' => $item->updated_at,
                    ];
                })->toArray();
                $this->table($headers, $data);
                $this->ask("Tekan Enter untuk kembali ke menu utama");
            } else if ($option == 2) {
                $this->info("Anda Memilih Pilihan : {$option} Tambah Kategori Barang");
                $category = new Category();
                $category->code = (int) $this->ask("Masukkan Kode Kategori : ");
                $category->name = $this->ask("Masukkan Nama Kategori : ");
                if ($category->save()) {
                    $this->notify("Success", "data berhasil disimpan");
                } else {
                    $this->notify("Failed", "data gagal disimpan");
                }

            } else if ($option == 3) {
                $this->info("Anda Memilih Pilihan : {$option} Ubah Kategori Barang");
                $code = (int) $this->ask("Masukkan Kode Kategori yang akan diubah : ");
                $category = Category::where('code', $code)->first();

                // ========== MULAI: Validasi Null Check ==========
                if (!$category) {
                    $this->error("Kategori dengan kode {$code} tidak ditemukan!");
                    continue; // Kembali ke menu
                }
                // ========== AKHIR: Validasi Null Check ==========

                $category->code = (int) $this->ask("Masukkan Kode Kategori : ");
                $category->name = $this->ask("Masukkan Nama Kategori : ");
                if ($category->save()) {
                    $this->notify("Success", "data berhasil diubah");
                } else {
                    $this->notify("Failed", "data gagal diubah");
                }
            } else if ($option == 4) {
                $this->info("Anda Memilih Pilihan : {$option} Hapus Kategori Barang");
                $code = (int) $this->ask("Masukkan Kode Kategori yang akan dihapus : ");
                $category = Category::where('code', $code)->first();

                // ========== MULAI: Validasi Null Check ==========
                if (!$category) {
                    $this->error("Kategori dengan kode {$code} tidak ditemukan!");
                    continue; // Kembali ke menu
                }
                // ========== AKHIR: Validasi Null Check ==========

                // ========== MULAI: Fitur Konfirmasi Hapus ==========
                if (!$this->confirm("Apakah Anda yakin ingin menghapus kategori '{$category->name}'?")) {
                    $this->info("Penghapusan dibatalkan.");
                    continue;
                }
                // ========== AKHIR: Fitur Konfirmasi Hapus ==========

                if ($category->delete()) {
                    $this->notify("Success", "data berhasil dihapus");
                } else {
                    $this->notify("Failed", "data gagal dihapus");
                }
            } else if ($option == 5) {
                $this->info("Anda Memilih Pilihan : {$option} Daftar Jenis Barang");
                $headers = ['kode', 'nama', 'dibuat', 'diubah'];
                $data = Variety::all()->map(function ($item) {
                    return [
                        'kode' => $item->code,
                        'nama' => $item->name,
                        'dibuat' => $item->created_at,
                        'diubah' => $item->updated_at,
                    ];
                })->toArray();
                $this->table($headers, $data);
                $this->ask("Tekan Enter untuk kembali ke menu utama");
            } else if ($option == 6) {
                $this->info("Anda Memilih Pilihan : {$option} Tambah Jenis Barang");
                $variety = new Variety();
                $variety->code = (int) $this->ask("Masukkan Kode Jenis : ");
                $variety->name = $this->ask("Masukkan Nama Jenis : ");
                if ($variety->save()) {
                    $this->notify("Success", "data berhasil disimpan");
                } else {
                    $this->notify("Failed", "data gagal disimpan");
                }
            } else if ($option == 7) {
                $this->info("Anda Memilih Pilihan : {$option} Ubah Jenis Barang");
                $code = (int) $this->ask("Masukkan Kode Jenis yang akan diubah : ");
                $variety = Variety::where('code', $code)->first();

                // ========== MULAI: Validasi Null Check ==========
                if (!$variety) {
                    $this->error("Jenis dengan kode {$code} tidak ditemukan!");
                    continue; // Kembali ke menu
                }
                // ========== AKHIR: Validasi Null Check ==========

                $variety->code = (int) $this->ask("Masukkan Kode Jenis : ");
                $variety->name = $this->ask("Masukkan Nama Jenis : ");
                if ($variety->save()) {
                    $this->notify("Success", "data berhasil diubah");
                } else {
                    $this->notify("Failed", "data gagal diubah");
                }
            } else if ($option == 8) {
                $this->info("Anda Memilih Pilihan : {$option} Hapus Jenis Barang");
                $code = (int) $this->ask("Masukkan Kode Jenis yang akan dihapus : ");
                $variety = Variety::where('code', $code)->first();

                // ========== MULAI: Validasi Null Check ==========
                if (!$variety) {
                    $this->error("Jenis dengan kode {$code} tidak ditemukan!");
                    continue; // Kembali ke menu
                }
                // ========== AKHIR: Validasi Null Check ==========

                // ========== MULAI: Fitur Konfirmasi Hapus ==========
                if (!$this->confirm("Apakah Anda yakin ingin menghapus jenis '{$variety->name}'?")) {
                    $this->info("Penghapusan dibatalkan.");
                    continue;
                }
                // ========== AKHIR: Fitur Konfirmasi Hapus ==========

                if ($variety->delete()) {
                    $this->notify("Success", "data berhasil dihapus");
                } else {
                    $this->notify("Failed", "data gagal dihapus");
                }

            } else if ($option == 9) {
                $this->info("Anda Memilih Pilihan : {$option} Daftar Barang");

                // ========== MULAI: Tambah Kolom Stock di Daftar Barang ==========
                $headers = ['kode', 'nama', 'harga', 'stok', 'kategori', 'jenis', 'dibuat', 'diubah'];
                $data = Product::all()->map(function ($item) {
                    return [
                        'kode' => $item->code,
                        'nama' => $item->name,
                        'harga' => $item->price,
                        'stok' => $item->stock,  // Tampilkan stok
                        'kategori' => $item->category->name,
                        'jenis' => $item->variety->name,
                        'dibuat' => $item->created_at,
                        'diubah' => $item->updated_at,
                    ];
                })->toArray();
                // ========== AKHIR: Tambah Kolom Stock di Daftar Barang ==========

                $this->table($headers, $data);
                $this->ask("Tekan Enter untuk kembali ke menu utama");
            } else if ($option == 10) {
                $product = new Product();
                $this->info("Anda Memilih Pilihan : {$option} Tambah Barang");
                $categories = Category::all()->pluck('name', 'id')->toArray();
                $product->category_id = select(
                    label: 'Pilih Kategori Barang:',
                    options: $categories,
                );

                // Pilih jenis barang
                $varieties = Variety::all()->pluck('name', 'id')->toArray();
                $product->variety_id = select(
                    label: 'Pilih Jenis Barang:',  // DIPERBAIKI: Label yang benar
                    options: $varieties,
                );

                $product->code = (int) $this->ask("Masukkan Kode Barang : ");
                $product->name = $this->ask("Masukkan Nama Barang : ");

                // ========== MULAI: Validasi Input Angka (Anti Minus) ==========
                do {
                    $inputPrice = (int) $this->ask("Masukkan Harga Barang (harus > 0) : ");
                    if ($inputPrice <= 0)
                        $this->error("Harga tidak boleh nol atau negatif!");
                } while ($inputPrice <= 0);
                $product->price = $inputPrice;

                // Input Stock Saat Tambah Produk
                do {
                    $inputStock = (int) $this->ask("Masukkan Jumlah Stok (harus >= 0) : ");
                    if ($inputStock < 0)
                        $this->error("Stok tidak boleh negatif!");
                } while ($inputStock < 0);
                $product->stock = $inputStock;
                // ========== AKHIR: Validasi Input Angka (Anti Minus) ==========

                if ($product->save()) {
                    $this->notify("Success", "data berhasil disimpan");
                } else {
                    $this->notify("Failed", "data gagal disimpan");
                }

            } else if ($option == 11) {
                // ========== MULAI: Implementasi Ubah Barang ==========
                $this->info("Anda Memilih Pilihan : {$option} Ubah Barang");
                $code = (int) $this->ask("Masukkan Kode Barang yang akan diubah : ");
                $product = Product::where('code', $code)->first();

                // Validasi: Cek apakah barang ditemukan
                if (!$product) {
                    $this->error("Barang dengan kode {$code} tidak ditemukan!");
                    continue; // Kembali ke menu
                }

                // Pilih kategori baru
                $categories = Category::all()->pluck('name', 'id')->toArray();
                $product->category_id = select(
                    label: 'Pilih Kategori Barang:',
                    options: $categories,
                );

                // Pilih jenis baru
                $varieties = Variety::all()->pluck('name', 'id')->toArray();
                $product->variety_id = select(
                    label: 'Pilih Jenis Barang:',
                    options: $varieties,
                );

                // Input data baru
                $product->code = (int) $this->ask("Masukkan Kode Barang : ");
                $product->name = $this->ask("Masukkan Nama Barang : ");
                $product->price = (int) $this->ask("Masukkan Harga Barang : ");

                // ========== MULAI: Update Stok Barang ==========
                // Menampilkan stok saat ini sebagai default
                $product->stock = (int) $this->ask("Masukkan Jumlah Stok : ", (string) $product->stock);
                // ========== AKHIR: Update Stok Barang ==========

                if ($product->save()) {
                    $this->notify("Success", "data berhasil diubah");
                } else {
                    $this->notify("Failed", "data gagal diubah");
                }
                // ========== AKHIR: Implementasi Ubah Barang ==========
            } else if ($option == 12) {
                // ========== MULAI: Implementasi Hapus Barang ==========
                $this->info("Anda Memilih Pilihan : {$option} Hapus Barang");
                $code = (int) $this->ask("Masukkan Kode Barang yang akan dihapus : ");
                $product = Product::where('code', $code)->first();

                // Validasi: Cek apakah barang ditemukan
                if (!$product) {
                    $this->error("Barang dengan kode {$code} tidak ditemukan!");
                    continue; // Kembali ke menu
                }

                // ========== MULAI: Fitur Konfirmasi Hapus ==========
                if (!$this->confirm("Apakah Anda yakin ingin menghapus barang '{$product->name}'?")) {
                    $this->info("Penghapusan dibatalkan.");
                    continue;
                }
                // ========== AKHIR: Fitur Konfirmasi Hapus ==========

                if ($product->delete()) {
                    $this->notify("Success", "data berhasil dihapus");
                } else {
                    $this->notify("Failed", "data gagal dihapus");
                }
                // ========== AKHIR: Implementasi Hapus Barang ==========
            } else if ($option == 13) {
                $headers = ['kode', 'nama', 'harga', 'jumlah', 'bayar', 'dibuat', 'diubah'];

                // ========== MULAI: Perbaikan Bug - Gunakan harga saat transaksi ==========
                $data = SaleTransaction::all()->map(function ($item) {
                    return [
                        'kode' => $item->product->code,
                        'nama' => $item->product->name,
                        'harga' => $item->price,  // DIPERBAIKI: Gunakan harga saat transaksi
                        'jumlah' => $item->quantity,
                        'jumlah bayar' => $item->quantity * $item->price,  // DIPERBAIKI: Hitung dengan harga transaksi
                        'dibuat' => $item->created_at,
                        'diubah' => $item->updated_at,
                    ];
                })->toArray();
                // ========== AKHIR: Perbaikan Bug ==========

                $this->table($headers, $data);
                $this->ask("Tekan Enter untuk kembali ke menu utama");

            }
        }

        $this->info("Terimakasih telah menggunakan applikasi kami.");

    }

    // ========== MULAI: Helper Method Format Rupiah ==========
    /**
     * Format angka menjadi format Rupiah
     * 
     * @param int $amount Jumlah uang
     * @return string Format: Rp 1.000
     */
    private function formatRupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
    // ========== AKHIR: Helper Method Format Rupiah ==========

    // ========== MULAI: Helper Method Generate Struk ==========
    /**
     * Generate struk transaksi dalam format text
     * 
     * @param SaleTransaction $sale Data transaksi
     * @param Product $product Data produk
     * @return string Struk dalam format text
     */
    private function generateStruk(SaleTransaction $sale, Product $product): string
    {
        $total = $sale->price * $sale->quantity;
        $tanggal = $sale->created_at->format('d/m/Y H:i:s');

        $struk = "\n";
        $struk .= "========================================\n";
        $struk .= "           SHERINA MART                 \n";
        $struk .= "     Jl.  No. 123, Kota abc             \n";
        $struk .= "          Telp: 0812-xxxx-xxxx         \n";
        $struk .= "========================================\n";
        $struk .= "Tanggal: {$tanggal}\n";
        $struk .= "No. Transaksi: {$sale->id}\n";
        $struk .= "----------------------------------------\n";
        $struk .= "Nama Barang  : {$product->name}\n";
        $struk .= "Kode Barang  : {$product->code}\n";
        $struk .= "Harga Satuan : " . $this->formatRupiah($sale->price) . "\n";
        $struk .= "Jumlah       : {$sale->quantity}\n";
        $struk .= "----------------------------------------\n";
        $struk .= "TOTAL BAYAR  : " . $this->formatRupiah($total) . "\n";
        $struk .= "========================================\n";
        $struk .= "      Terima Kasih Atas Kunjungan      \n";
        $struk .= "           Anda - Sherina Mart         \n";
        $struk .= "========================================\n";

        return $struk;
    }
    // ========== AKHIR: Helper Method Generate Struk ==========


    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
    }
}
