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
use Illuminate\Support\Str;

class MenuCommand extends Command
{
    protected $signature = 'app:menu-command';
    protected $description = 'menampilkan Menu pada pengguna';

    public function handle()
    {
        // ==============================
        // FIX: Mengganti $this->menu() ke select()
        // Alasan: Infinite loop "Cannot shrink menu" di Windows PowerShell karena margin terlalu besar.
        // Solusi: Menggunakan Laravel\Prompts\select() yang lebih kompatibel dan efisien.
        // ==============================
        
        while (true) {
            try {
                // Tampilkan ASCII Header
                $this->line("");
                $this->line($this->getAsciiHeader());
                $this->line("");
                $this->info("Selamat Datang di Aplikasi Sherina Mart");
                $this->line("");

                $option = select(
                    label: 'Pilih Menu:',
                    options: [
                        1 => 'Transaksi Pembelian Barang',
                        2 => 'Daftar Kategori Barang',
                        3 => 'Tambah Kategori Barang',
                        4 => 'Ubah Kategori Barang',
                        5 => 'Hapus Kategori Barang',
                        6 => 'Daftar Jenis Barang',
                        7 => 'Tambah Jenis Barang',
                        8 => 'Ubah Jenis Barang',
                        9 => 'Hapus Jenis Barang',
                        10 => 'Daftar Barang',
                        11 => 'Tambah Barang',
                        12 => 'Ubah Barang',
                        13 => 'Hapus Barang',
                        14 => 'Daftar Penjualan Barang',
                        15 => 'Keluar dari Aplikasi',
                    ],
                    scroll: 10
                );

                // Jika pilih keluar
                if ($option === 15 || $option === null) {
                    $this->info("Terimakasih telah menggunakan applikasi kami.");
                    break;
                }

                // Dispatcher ke Method yang sesuai
                switch ($option) {
                    case 1:
                        $this->handleTransaction();
                        break;
                    case 2:
                        $this->handleCategoryList();
                        break;
                    case 3:
                        $this->handleCategoryAdd();
                        break;
                    case 4:
                        $this->handleCategoryEdit();
                        break;
                    case 5:
                        $this->handleCategoryDelete();
                        break;
                    case 6:
                        $this->handleVarietyList();
                        break;
                    case 7:
                        $this->handleVarietyAdd();
                        break;
                    case 8:
                        $this->handleVarietyEdit();
                        break;
                    case 9:
                        $this->handleVarietyDelete();
                        break;
                    case 10:
                        $this->handleProductList();
                        break;
                    case 11:
                        $this->handleProductAdd();
                        break;
                    case 12:
                        $this->handleProductEdit();
                        break;
                    case 13:
                        $this->handleProductDelete();
                        break;
                    case 14:
                        $this->handleSalesList();
                        break;
                }

            } catch (\Throwable $e) {
                // Global Error Handler untuk mencegah Crash
                $this->error("Terjadi kesalahan: " . $e->getMessage());
                $this->info("Kembali ke Menu Utama...");
                // Loop akan berlanjut (continue otomatis)
            }
        }
        // ==============================
        // END UPDATE: Menu Utama (Sekarang pakai select())
        // Hasil:
        // - Tidak ada lagi infinite loop di PowerShell.
        // - Lebih kompatibel dengan terminal apapun.
        // ==============================
    }

    // =========================================================================
    // MODUL TRANSAKSI
    // =========================================================================

    private function handleTransaction()
    {
        // ==============================
        // START UPDATE: Submenu Transaksi Loop
        // Perubahan:
        // - Membungkus pilihan menu transaksi dalam while(true).
        // Alasan:
        // - Agar setelah selesai 'void' atau 'batal', user kembali memilih menu transaksi, bukan terpental keluar.
        // ==============================
        while (true) {
            $subOption = select(
                label: 'Menu Transaksi:',
                options: [
                    'new' => 'Transaksi Pembelian Baru',
                    'void' => 'Batalkan Transaksi (Void)',
                    'cancel' => 'Kembali ke Menu Utama',
                ],
            );

            // ==============================
            // START UPDATE: Back Logic
            // Perubahan: Menggunakan return untuk kembali ke caller (Menu Utama)
            // Alasan: Menjaga alur stack pemanggilan.
            // ==============================
            if ($subOption === 'cancel') {
                return; // KEMBALI KE MENU UTAMA
            }

            if ($subOption === 'void') {
                $this->handleVoidTransaction();
                // Setelah selesai void, loop akan berulang (kembali ke menu transaksi)
            }

            if ($subOption === 'new') {
                $this->handleNewTransaction();
                // Setelah selesai transaksi, loop akan berulang (kembali ke menu transaksi)
            }
        }
        // ==============================
        // END UPDATE: Submenu Transaksi Loop
        // Hasil: User tetap berada di konteks transaksi sampai memilih kembali.
        // ==============================
    }

    private function handleVoidTransaction()
    {
        $this->info("Mode Pembatalan Transaksi");
        $transactions = SaleTransaction::with('product')->latest()->take(10)->get();

        if ($transactions->isEmpty()) {
            $this->error("Belum ada transaksi yang dapat dibatalkan.");
            return;
        }

        $choices = [];
        foreach ($transactions as $t) {
            $time = $t->created_at->format('d/m H:i');
            $productName = $t->product ? $t->product->name : 'Produk Terhapus';
            $label = "[{$time}] {$productName} - {$this->formatRupiah($t->price)} x {$t->quantity}";
            $choices[$t->id] = $label;
        }
        $choices['cancel'] = 'Batal & Kembali';

        $selectedId = select(
            label: 'Pilih Transaksi untuk dibatalkan:',
            options: $choices,
            scroll: 10
        );

        if ($selectedId === 'cancel' || $selectedId === null) {
            return;
        }

        $trx = SaleTransaction::find($selectedId);
        // FIX CRASH: Cek produk terhapus sebelum confirm
        $productName = $trx->product ? $trx->product->name : 'Produk Terhapus';
        if ($this->confirm("Yakin batalkan transaksi {$productName}?", true)) {
            if ($trx->product) {
                $trx->product->stock += $trx->quantity;
                $trx->product->save();
            }
            $trx->delete();
            $this->info("Transaksi berhasil dibatalkan (Void). Stok dikembalikan.");
        }
    }

    private function handleNewTransaction()
    {
        // Shopping Cart Logic
        $cart = [];
        $cartTotal = 0;
        $cartTitle = "Keranjang Belanja (Kosong)";

        while (true) {
            $this->line("");
            $this->info($cartTitle);

            $action = select(
                label: 'Aksi Keranjang:',
                options: [
                    'add' => '+ Tambah Barang',
                    'view' => 'Lihat Keranjang',
                    'checkout' => '$$ Bayar / Checkout $$',
                    'cancel' => '<< Batal & Kembali ke Menu Utama',
                ],
                default: 'add'
            );

            if ($action === 'cancel' || $action === null || $action === '') {
                if (count($cart) > 0) {
                    if (!$this->confirm("Keranjang tidak kosong. Yakin ingin membatalkan transaksi?", false)) {
                        continue;
                    }
                }
                return; // KEMBALI KE MENU UTAMA
            }

            if ($action === 'add') {
                $products = Product::where('stock', '>', 0)->get();

                if ($products->isEmpty()) {
                    $this->error("Tidak ada barang tersedia (Stok Habis).");
                    continue;
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
                    continue;
                }

                $choosen = $products->find($prodId);
                // UPDATE: Pakai Validasi Helper
                $qty = $this->askValidInteger("Jumlah beli (Stok: {$choosen->stock}):", 1);

                if ($qty > $choosen->stock) {
                    $this->error("Stok tidak cukup! Tersedia: {$choosen->stock}");
                    continue;
                }

                $subtotal = $choosen->price * $qty;
                $cart[] = [
                    'product_id' => $choosen->id,
                    'product_name' => $choosen->name,
                    'price' => $choosen->price,
                    'quantity' => $qty,
                    'subtotal' => $subtotal
                ];

                $choosen->stock -= $qty;
                $choosen->save();

                $cartTotal += $subtotal;
                $cartTitle = "Keranjang Belanja: " . count($cart) . " Item - Total: " . $this->formatRupiah($cartTotal);
                $this->info("Berhasil ditambahkan ke keranjang.");
            }

            if ($action === 'view') {
                if (empty($cart)) {
                    $this->info("Keranjang masih kosong.");
                } else {
                    $headers = ['Nama', 'Harga', 'Qty', 'Subtotal'];
                    $data = array_map(function ($item) {
                        return [
                            $item['product_name'],
                            $this->formatRupiah($item['price']),
                            $item['quantity'],
                            $this->formatRupiah($item['subtotal'])
                        ];
                    }, $cart);
                    $this->table($headers, $data);
                    $this->info("Total Sementara: " . $this->formatRupiah($cartTotal));
                }
            }

            if ($action === 'checkout') {
                if (empty($cart)) {
                    $this->error("Keranjang kosong!");
                    continue;
                }

                $this->info("=== CHECKOUT ===");
                $this->info("Total Tagihan: " . $this->formatRupiah($cartTotal));

                $payment = 0;
                while ($payment < $cartTotal) {
                    // UPDATE: Pakai Validasi Helper
                    $payment = $this->askValidInteger("Masukkan Jumlah Uang Tunai:", 0);
                    if ($payment < $cartTotal) {
                        $this->error("Uang kurang! Kurang: " . $this->formatRupiah($cartTotal - $payment));
                    }
                }

                $kembali = $payment - $cartTotal;
                $this->info("Kembalian: " . $this->formatRupiah($kembali));

                $saleId = (string) Str::uuid();

                foreach ($cart as $item) {
                    $trx = new SaleTransaction();
                    $trx->sale_id = $saleId; // Asumsi kolom ada
                    $trx->product_id = $item['product_id'];
                    $trx->price = $item['price'];
                    $trx->quantity = $item['quantity'];
                    $trx->save();
                }

                $strukContent = $this->generateStrukCart($cart, $cartTotal, $payment, $kembali, substr($saleId, 0, 8));
                $this->line($strukContent);

                if ($this->confirm("Simpan struk?", true)) {
                    $strukDir = database_path('struk');
                    if (!file_exists($strukDir))
                        mkdir($strukDir, 0755, true);
                    $file = $strukDir . '/struk_cart_' . date('YmdHis') . '.txt';
                    file_put_contents($file, $strukContent);
                    $this->info("Struk tersimpan: $file");
                }

                return; // Selesai transaksi, KEMBALI KE MENU UTAMA
            }
        }
    }

    // =========================================================================
    // MODUL KATEGORI
    // =========================================================================

    private function handleCategoryList()
    {
        $this->info("Daftar Kategori Barang");
        $categories = Category::all();

        if ($categories->isEmpty()) {
            $this->error("Belum ada data Kategori.");
            return;
        }

        // Siapkan Opsi
        $options = [];
        foreach ($categories as $cat) {
            $options[$cat->id] = "[{$cat->code}] {$cat->name}";
        }
        $options['back'] = "⬅ Kembali ke Menu Utama"; // Di Bawah

        // ==============================
        // START UPDATE: Submenu Kategori Loop
        // Perubahan: Loop untuk navigasi daftar kategori
        // Alasan: User bisa melihat detail, lalu kembali ke daftar, tanpa keluar ke menu utama.
        // ==============================
        while (true) {
            $selectedId = select(
                label: 'Cari & Pilih Kategori (Gunakan Panah Atas/Bawah):',
                options: $options,
                scroll: 10
            );

            // Logic Kembali (Return Method)
            if ($selectedId === null || $selectedId === 'back' || $selectedId === '') {
                $this->info("Kembali ke Menu Utama...");
                return; // KELUAR LOOP -> KELUAR METHOD -> BALIK MENU UTAMA
            }

            // Tampilkan Detail
            $selectedCat = $categories->firstWhere('id', $selectedId);
            $this->line("");
            $this->info("=== DETAIL KATEGORI ===");
            $this->line("Kode   : " . $selectedCat->code);
            $this->line("Nama   : " . $selectedCat->name);
            $this->line("Dibuat : " . $selectedCat->created_at);
            $this->line("Diubah : " . $selectedCat->updated_at);
            $this->line("=======================");

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
                $this->info("Kembali ke Menu Utama...");
                return;
            }
            // Jika 'search', loop akan berulang
            $this->line("");
        }
        // ==============================
        // END UPDATE: Submenu Kategori Loop
        // Hasil: Navigasi intuitif (Daftar -> Detail -> Daftar/Menu)
        // ==============================
    }

    private function handleCategoryAdd()
    {
        $this->info("Tambah Kategori Barang");
        $category = new Category();
        // Validasi Kode (Duplikasi)
        do {
            $category->code = $this->askValidInteger("Masukkan Kode Kategori :");
            if (Category::where('code', $category->code)->exists()) {
                $this->error("Kode sudah digunakan! Silakan masukkan kode lain.");
            } else {
                break;
            }
        } while (true);

        // Validasi Nama (Tidak Boleh Kosong)
        do {
            $category->name = $this->ask("Masukkan Nama Kategori : ");
            if (empty(trim($category->name))) {
                $this->error("Nama tidak boleh kosong!");
            } else {
                break;
            }
        } while (true);

        if ($category->save()) {
            $this->notify("Success", "data berhasil disimpan");
        } else {
            $this->notify("Failed", "data gagal disimpan");
        }
    }

    private function handleCategoryEdit()
    {
        $this->info("Ubah Kategori Barang");
        $code = $this->askValidInteger("Masukkan Kode Kategori yang akan diubah :");
        $category = Category::where('code', $code)->first();

        if (!$category) {
            $this->error("Kategori dengan kode {$code} tidak ditemukan!");
            return;
        }

        // Validasi Kode Baru (Duplikasi)
        do {
            $newCode = $this->askValidInteger("Masukkan Kode Kategori Baru :", 0, (string) $category->code);
            if ($newCode != $category->code && Category::where('code', $newCode)->exists()) {
                $this->error("Kode sudah digunakan! Silakan masukkan kode lain.");
            } else {
                $category->code = $newCode;
                break;
            }
        } while (true);

        // Validasi Nama (Tidak Boleh Kosong)
        do {
            $category->name = $this->ask("Masukkan Nama Kategori : ", $category->name);
            if (empty(trim($category->name))) {
                $this->error("Nama tidak boleh kosong!");
            } else {
                break;
            }
        } while (true);

        if ($category->save()) {
            $this->notify("Success", "data berhasil diubah");
        } else {
            $this->notify("Failed", "data gagal diubah");
        }
    }

    private function handleCategoryDelete()
    {
        $this->info("Hapus Kategori Barang");
        $code = $this->askValidInteger("Masukkan Kode Kategori yang akan dihapus :");
        $category = Category::where('code', $code)->first();

        if (!$category) {
            $this->error("Kategori dengan kode {$code} tidak ditemukan!");
            return;
        }

        if (!$this->confirm("Apakah Anda yakin ingin menghapus kategori '{$category->name}'?")) {
            $this->info("Penghapusan dibatalkan.");
            return;
        }

        // CEK INTEGRITAS DATA: Jangan hapus jika ada barang
        $productCount = Product::where('category_id', $category->id)->count();
        if ($productCount > 0) {
            $this->error("GAGAL: Kategori tidak dapat dihapus karena masih memiliki {$productCount} barang terdaftar.");
            $this->line("Silahkan hapus atau pindahkan barang terlebih dahulu.");
            return;
        }

        if ($category->delete()) {
            $this->notify("Success", "data berhasil dihapus");
        } else {
            $this->notify("Failed", "data gagal dihapus");
        }
    }

    // =========================================================================
    // MODUL JENIS BARANG (VARIETY)
    // =========================================================================

    private function handleVarietyList()
    {
        $this->info("Daftar Jenis Barang");
        $varieties = Variety::all();

        if ($varieties->isEmpty()) {
            $this->error("Belum ada data Jenis Barang.");
            return;
        }

        $options = [];
        foreach ($varieties as $var) {
            $options[$var->id] = "[{$var->code}] {$var->name}";
        }
        $options['back'] = "⬅ Kembali ke Menu Utama";

        // ==============================
        // START UPDATE: Submenu Jenis Barang Loop
        // Perubahan: Loop struktur untuk daftar jenis barang
        // Alasan: Konsistensi dengan logika menu kategori.
        // ==============================
        while (true) {
            $selectedId = select(
                label: 'Cari & Pilih Jenis Barang (Gunakan Panah Atas/Bawah):',
                options: $options,
                scroll: 10
            );

            if ($selectedId === null || $selectedId === 'back' || $selectedId === '') {
                $this->info("Kembali ke Menu Utama...");
                return;
            }

            $selectedVar = $varieties->firstWhere('id', $selectedId);
            $this->line("");
            $this->info("=== DETAIL JENIS BARANG ===");
            $this->line("Kode   : " . $selectedVar->code);
            $this->line("Nama   : " . $selectedVar->name);
            $this->line("Dibuat : " . $selectedVar->created_at);
            $this->line("Diubah : " . $selectedVar->updated_at);
            $this->line("=========================");

            $nextAction = select(
                label: 'Aksi Selanjutnya:',
                options: [
                    'search' => 'Cari Lagi',
                    'menu' => 'Kembali ke Menu Utama'
                ],
                default: 'search'
            );

            if ($nextAction === 'menu' || $nextAction === null || $nextAction === '') {
                $this->info("Kembali ke Menu Utama...");
                return;
            }
            $this->line("");
        }
        // ==============================
        // END UPDATE: Submenu Jenis Barang Loop
        // ==============================
    }

    private function handleVarietyAdd()
    {
        $this->info("Tambah Jenis Barang");
        $variety = new Variety();
        
        // Validasi Kode (Duplikasi)
        do {
            $variety->code = $this->askValidInteger("Masukkan Kode Jenis :");
            if (Variety::where('code', $variety->code)->exists()) {
                $this->error("Kode sudah digunakan! Silakan masukkan kode lain.");
            } else {
                break;
            }
        } while (true);

        // Validasi Nama (Tidak Boleh Kosong)
        do {
            $variety->name = $this->ask("Masukkan Nama Jenis : ");
            if (empty(trim($variety->name))) {
                $this->error("Nama tidak boleh kosong!");
            } else {
                break;
            }
        } while (true);

        if ($variety->save()) {
            $this->notify("Success", "data berhasil disimpan");
        } else {
            $this->notify("Failed", "data gagal disimpan");
        }
    }

    private function handleVarietyEdit()
    {
        $this->info("Ubah Jenis Barang");
        $code = $this->askValidInteger("Masukkan Kode Jenis yang akan diubah :");
        $variety = Variety::where('code', $code)->first();

        if (!$variety) {
            $this->error("Jenis dengan kode {$code} tidak ditemukan!");
            return;
        }

        // Validasi Kode Baru (Duplikasi)
        do {
            $newCode = $this->askValidInteger("Masukkan Kode Jenis Baru :", 0, (string) $variety->code);
            if ($newCode != $variety->code && Variety::where('code', $newCode)->exists()) {
                $this->error("Kode sudah digunakan! Silakan masukkan kode lain.");
            } else {
                $variety->code = $newCode;
                break;
            }
        } while (true);

        // Validasi Nama (Tidak Boleh Kosong)
        do {
            $variety->name = $this->ask("Masukkan Nama Jenis : ", $variety->name);
            if (empty(trim($variety->name))) {
                $this->error("Nama tidak boleh kosong!");
            } else {
                break;
            }
        } while (true);

        if ($variety->save()) {
            $this->notify("Success", "data berhasil diubah");
        } else {
            $this->notify("Failed", "data gagal diubah");
        }
    }

    private function handleVarietyDelete()
    {
        $this->info("Hapus Jenis Barang");
        $code = $this->askValidInteger("Masukkan Kode Jenis yang akan dihapus :");
        $variety = Variety::where('code', $code)->first();

        if (!$variety) {
            $this->error("Jenis dengan kode {$code} tidak ditemukan!");
            return;
        }

        if (!$this->confirm("Apakah Anda yakin ingin menghapus jenis '{$variety->name}'?")) {
            $this->info("Penghapusan dibatalkan.");
            return;
        }

        // INTEGRITAS: Cek apakah masih ada produk dengan jenis ini
        $productCount = Product::where('variety_id', $variety->id)->count();
        if ($productCount > 0) {
            $this->error("GAGAL: Jenis tidak dapat dihapus karena masih memiliki {$productCount} produk terdaftar.");
            $this->line("Silahkan hapus atau pindahkan produk terlebih dahulu.");
            return;
        }

        if ($variety->delete()) {
            $this->notify("Success", "data berhasil dihapus");
        } else {
            $this->notify("Failed", "data gagal dihapus");
        }
    }

    // =========================================================================
    // MODUL BARANG (PRODUCT)
    // =========================================================================

    private function handleProductList()
    {
        $this->info("Daftar Barang");
        $products = Product::with(['category', 'variety'])->get();

        if ($products->isEmpty()) {
            $this->error("Belum ada data Barang.");
            return;
        }

        $options = [];
        foreach ($products as $prod) {
            $label = "[{$prod->code}] {$prod->name} - " . $this->formatRupiah($prod->price) . " (Stok: {$prod->stock})";
            $options[$prod->id] = $label;
        }
        $options['back'] = "⬅ Kembali ke Menu Utama";

        // ==============================
        // START UPDATE: Submenu Produk Loop
        // Perubahan: Loop struktur untuk daftar produk
        // Alasan: Memungkinkan browsing produk berulang kali.
        // ==============================
        while (true) {
            $selectedId = select(
                label: 'Cari & Pilih Barang (Gunakan Panah Atas/Bawah):',
                options: $options,
                scroll: 10
            );

            if ($selectedId === null || $selectedId === 'back' || $selectedId === '') {
                $this->info("Kembali ke Menu Utama...");
                return;
            }

            $selectedProd = $products->firstWhere('id', $selectedId);
            $catName = $selectedProd->category ? $selectedProd->category->name : '-';
            $varName = $selectedProd->variety ? $selectedProd->variety->name : '-';

            $this->line("");
            $this->info("=== DETAIL BARANG ===");
            $this->line("Kode     : " . $selectedProd->code);
            $this->line("Nama     : " . $selectedProd->name);
            $this->line("Harga    : " . $this->formatRupiah($selectedProd->price));
            $this->line("Stok     : " . $selectedProd->stock);
            $this->line("Kategori : " . $catName);
            $this->line("Jenis    : " . $varName);
            $this->line("Dibuat   : " . $selectedProd->created_at);
            $this->line("Diubah   : " . $selectedProd->updated_at);
            $this->line("=====================");

            $nextAction = select(
                label: 'Aksi Selanjutnya:',
                options: [
                    'search' => 'Cari Lagi',
                    'menu' => 'Kembali ke Menu Utama'
                ],
                default: 'search'
            );

            if ($nextAction === 'menu' || $nextAction === null || $nextAction === '') {
                $this->info("Kembali ke Menu Utama...");
                return;
            }
            $this->line("");
        }
        // ==============================
        // END UPDATE: Submenu Produk Loop
        // ==============================
    }

    private function handleProductAdd()
    {
        $this->info("Tambah Barang");
        $product = new Product();

        // Cek Ketersediaan Kategori
        $categories = Category::all()->pluck('name', 'id')->toArray();
        if (empty($categories)) {
            $this->error("Belum ada Kategori! Silakan tambahkan Kategori terlebih dahulu.");
            return;
        }
        $product->category_id = select(label: 'Pilih Kategori Barang:', options: $categories);

        // Cek Ketersediaan Jenis
        $varieties = Variety::all()->pluck('name', 'id')->toArray();
        if (empty($varieties)) {
            $this->error("Belum ada Jenis Barang! Silakan tambahkan Jenis terlebih dahulu.");
            return;
        }
        $product->variety_id = select(label: 'Pilih Jenis Barang:', options: $varieties);

        // Validasi Kode (Duplikasi)
        do {
            $product->code = $this->askValidInteger("Masukkan Kode Barang :");
            if (Product::where('code', $product->code)->exists()) {
                $this->error("Kode sudah digunakan! Silakan masukkan kode lain.");
            } else {
                break;
            }
        } while (true);

        $product->name = $this->ask("Masukkan Nama Barang : ");
        $product->price = $this->askValidInteger("Masukkan Harga Barang (harus > 0) :", 1);
        $product->stock = $this->askValidInteger("Masukkan Jumlah Stok (harus >= 0) :", 0);

        if ($product->save()) {
            $this->notify("Success", "data berhasil disimpan");
        } else {
            $this->notify("Failed", "data gagal disimpan");
        }
    }

    private function handleProductEdit()
    {
        $this->info("Ubah Barang");
        $code = $this->askValidInteger("Masukkan Kode Barang yang akan diubah :");
        $product = Product::where('code', $code)->first();

        if (!$product) {
            $this->error("Barang dengan kode {$code} tidak ditemukan!");
            return;
        }

        $categories = Category::all()->pluck('name', 'id')->toArray();
        $product->category_id = select(label: 'Pilih Kategori Barang:', options: $categories);

        $varieties = Variety::all()->pluck('name', 'id')->toArray();
        $product->variety_id = select(label: 'Pilih Jenis Barang:', options: $varieties);

        $product->code = $this->askValidInteger("Masukkan Kode Barang :", 0, (string) $product->code);
        $product->name = $this->ask("Masukkan Nama Barang : ", $product->name);
        
        // Gunakan Helper Validasi
        $product->price = $this->askValidInteger("Masukkan Harga Barang (harus > 0) :", 1, (string) $product->price);
        $product->stock = $this->askValidInteger("Masukkan Jumlah Stok (harus >= 0) :", 0, (string) $product->stock);

        if ($product->save()) {
            $this->notify("Success", "data berhasil diubah");
        } else {
            $this->notify("Failed", "data gagal diubah");
        }
    }

    private function handleProductDelete()
    {
        $this->info("Hapus Barang");
        $code = $this->askValidInteger("Masukkan Kode Barang yang akan dihapus :");
        $product = Product::where('code', $code)->first();

        if (!$product) {
            $this->error("Barang dengan kode {$code} tidak ditemukan!");
            return;
        }

        if (!$this->confirm("Apakah Anda yakin ingin menghapus barang '{$product->name}'?")) {
            $this->info("Penghapusan dibatalkan.");
            return;
        }

        // CEK INTEGRITAS DATA: Jangan hapus jika sudah ada transaksi penjualan
        $trxCount = SaleTransaction::where('product_id', $product->id)->count();
        if ($trxCount > 0) {
            $this->error("GAGAL: Barang tidak dapat dihapus karena memiliki riwayat {$trxCount} transaksi penjualan.");
            $this->line("Data barang diperlukan untuk laporan penjualan.");
            return;
        }

        if ($product->delete()) {
            $this->notify("Success", "data berhasil dihapus");
        } else {
            $this->notify("Failed", "data gagal dihapus");
        }
    }

    // =========================================================================
    // MODUL PENJUALAN
    // =========================================================================

    private function handleSalesList()
    {
        $this->info("Daftar Penjualan Barang");
        $sales = SaleTransaction::all();

        if ($sales->isEmpty()) {
            $this->info("Belum ada data penjualan.");
            $this->ask("Tekan Enter untuk kembali ke menu utama");
            return;
        }

        $headers = ['kode', 'nama', 'harga', 'jumlah', 'bayar', 'dibuat', 'diubah'];
        $data = $sales->map(function ($item) {
            return [
                'kode' => $item->product ? $item->product->code : 'DEL',
                'nama' => $item->product ? $item->product->name : 'Deleted',
                'harga' => $this->formatRupiah($item->price),
                'jumlah' => $item->quantity,
                'jumlah bayar' => $this->formatRupiah($item->quantity * $item->price),
                'dibuat' => $item->created_at,
                'diubah' => $item->updated_at,
            ];
        })->toArray();

        $this->table($headers, $data);
        $this->ask("Tekan Enter untuk kembali ke menu utama");
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    private function getAsciiHeader(): string
    {
        return <<<EOT
   _____ __               _                __  __            __ 
  / ___// /_  ___  ____  (_)___  ____ _   /  |/  /___ ______/ /_
  \__ \/ __ \/ _ \/ __ \/ / __ \/ __ `/  / /|_/ / __ `/ ___/ __/
 ___/ / / / /  __/ / / / / / / / /_/ /  / /  / / /_/ / /  / /_  
/____/_/ /_/\___/_/ /_/_/_/ /_/\__,_/  /_/  /_/\__,_/_/   \__/  
                                                  by cycau09
EOT;
    }

    /**
     * Helper untuk meminta input integer dengan validasi loop.
     * "Anti Salah Input"
     */
    private function askValidInteger(string $question, int $min = 0, ?string $default = null): int
    {
        do {
            $input = $this->ask($question, $default);

            // Jika input kosong dan ada default, pakai default (Logic ask() sebenarnya sudah handle ini, tapi kita pastikan)
            if ($input === null && $default !== null) {
                return (int) $default;
            }

            if (!is_numeric($input)) {
                $this->error("Input tidak valid! Harap masukkan angka.");
                continue;
            }

            $val = (int) $input;
            if ($val < $min) {
                $this->error("Nilai tidak valid! Minimal {$min}.");
                continue;
            }

            return $val;
        } while (true);
    }

    private function formatRupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    private function generateStrukCart(array $items, int $total, int $bayar, int $kembali, string $saleId): string
    {
        $tanggal = date('d/m/Y H:i:s');
        $struk = "\n";
        $struk .= "========================================\n";
        $struk .= "           SHERINA MART                 \n";
        $struk .= "     Jl.  No. 123, Kota abc             \n";
        $struk .= "          Telp: 0812-xxxx-xxxx         \n";
        $struk .= "========================================\n";
        $struk .= "Tanggal       : {$tanggal}\n";
        $struk .= "No. Transaksi : {$saleId}\n";
        $struk .= "----------------------------------------\n";
        $struk .= "ITEM                  QTY      TOTAL    \n";
        $struk .= "----------------------------------------\n";

        foreach ($items as $item) {
            $nama = substr($item['product_name'], 0, 18);
            $qty = $item['quantity'];
            $subtotal = $this->formatRupiah($item['price'] * $item['quantity']);
            $line1 = str_pad($nama, 20);
            $line2 = str_pad((string) $qty, 5, " ", STR_PAD_BOTH);
            $line3 = str_pad($subtotal, 13, " ", STR_PAD_LEFT);
            $struk .= "{$line1}{$line2}{$line3}\n";
        }

        $struk .= "----------------------------------------\n";
        $struk .= "TOTAL TAGIHAN : " . str_pad($this->formatRupiah($total), 22, " ", STR_PAD_LEFT) . "\n";
        $struk .= "TUNAI         : " . str_pad($this->formatRupiah($bayar), 22, " ", STR_PAD_LEFT) . "\n";
        $struk .= "KEMBALI       : " . str_pad($this->formatRupiah($kembali), 22, " ", STR_PAD_LEFT) . "\n";
        $struk .= "========================================\n";
        $struk .= "      Terima Kasih Atas Kunjungan      \n";
        $struk .= "           Anda - Sherina Mart         \n";
        $struk .= "========================================\n";

        return $struk;
    }

    public function schedule(Schedule $schedule): void
    {
    }
}
