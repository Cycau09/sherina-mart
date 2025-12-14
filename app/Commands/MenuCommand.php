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
        // START UPDATE: Menu Utama Loop
        // Perubahan:
        // - Menggunakan while(true) untuk membungkus seluruh logika menu utama.
        // - Menangkap output 'null' dari menu selection sebagai sinyal Exit.
        // Alasan:
        // - Memastikan aplikasi tidak tertutup otomatis setelah menjalankan satu perintah.
        // - Pengguna harus secara eksplisit memilih keluar atau membatalkan menu.
        // ==============================
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
            "Pilihan 13: Hapus Barang",
            "Pilihan 14: Daftar Penjualan Barang",
        ];

        while (true) {
            try {
                // Tampilan Menu Utama
                $option = $this->menu($title, $options)
                    ->setForegroundColour("green")
                    ->setBackgroundColour("black")
                    ->setWidth(200)
                    ->setPadding(10)
                    ->setMargin(5)
                    ->setTitleSeparator("*-")
                    ->open();

                // Jika null (Exit default), keluar dari loop utama (Tutup Aplikasi)
                if ($option === null) {
                    $this->info("Terimakasih telah menggunakan applikasi kami.");
                    break;
                }

                // Dispatcher ke Method yang sesuai
                switch ($option) {
                    case 0:
                        $this->handleTransaction();
                        break;
                    case 1:
                        $this->handleCategoryList();
                        break;
                    case 2:
                        $this->handleCategoryAdd();
                        break;
                    case 3:
                        $this->handleCategoryEdit();
                        break;
                    case 4:
                        $this->handleCategoryDelete();
                        break;
                    case 5:
                        $this->handleVarietyList();
                        break;
                    case 6:
                        $this->handleVarietyAdd();
                        break;
                    case 7:
                        $this->handleVarietyEdit();
                        break;
                    case 8:
                        $this->handleVarietyDelete();
                        break;
                    case 9:
                        $this->handleProductList();
                        break;
                    case 10:
                        $this->handleProductAdd();
                        break;
                    case 11:
                        $this->handleProductEdit();
                        break;
                    case 12:
                        $this->handleProductDelete();
                        break;
                    case 13:
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
        // END UPDATE: Menu Utama Loop
        // Hasil:
        // - Aplikasi terus berjalan sampai user memilih keluar.
        // - Error handling memastikan aplikasi robust (tidak crash).
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
        if ($this->confirm("Yakin batalkan transaksi {$trx->product->name}?", true)) {
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
                $qty = (int) $this->ask("Jumlah beli (Stok: {$choosen->stock}):");

                if ($qty <= 0 || $qty > $choosen->stock) {
                    $this->error("Jumlah tidak valid!");
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
                    $payment = (int) $this->ask("Masukkan Jumlah Uang Tunai:");
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
        $category->code = (int) $this->ask("Masukkan Kode Kategori : ");
        $category->name = $this->ask("Masukkan Nama Kategori : ");
        if ($category->save()) {
            $this->notify("Success", "data berhasil disimpan");
        } else {
            $this->notify("Failed", "data gagal disimpan");
        }
    }

    private function handleCategoryEdit()
    {
        $this->info("Ubah Kategori Barang");
        $code = (int) $this->ask("Masukkan Kode Kategori yang akan diubah : ");
        $category = Category::where('code', $code)->first();

        if (!$category) {
            $this->error("Kategori dengan kode {$code} tidak ditemukan!");
            return;
        }

        $category->code = (int) $this->ask("Masukkan Kode Kategori : ");
        $category->name = $this->ask("Masukkan Nama Kategori : ");
        if ($category->save()) {
            $this->notify("Success", "data berhasil diubah");
        } else {
            $this->notify("Failed", "data gagal diubah");
        }
    }

    private function handleCategoryDelete()
    {
        $this->info("Hapus Kategori Barang");
        $code = (int) $this->ask("Masukkan Kode Kategori yang akan dihapus : ");
        $category = Category::where('code', $code)->first();

        if (!$category) {
            $this->error("Kategori dengan kode {$code} tidak ditemukan!");
            return;
        }

        if (!$this->confirm("Apakah Anda yakin ingin menghapus kategori '{$category->name}'?")) {
            $this->info("Penghapusan dibatalkan.");
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
        $variety->code = (int) $this->ask("Masukkan Kode Jenis : ");
        $variety->name = $this->ask("Masukkan Nama Jenis : ");
        if ($variety->save()) {
            $this->notify("Success", "data berhasil disimpan");
        } else {
            $this->notify("Failed", "data gagal disimpan");
        }
    }

    private function handleVarietyEdit()
    {
        $this->info("Ubah Jenis Barang");
        $code = (int) $this->ask("Masukkan Kode Jenis yang akan diubah : ");
        $variety = Variety::where('code', $code)->first();

        if (!$variety) {
            $this->error("Jenis dengan kode {$code} tidak ditemukan!");
            return;
        }

        $variety->code = (int) $this->ask("Masukkan Kode Jenis : ");
        $variety->name = $this->ask("Masukkan Nama Jenis : ");
        if ($variety->save()) {
            $this->notify("Success", "data berhasil diubah");
        } else {
            $this->notify("Failed", "data gagal diubah");
        }
    }

    private function handleVarietyDelete()
    {
        $this->info("Hapus Jenis Barang");
        $code = (int) $this->ask("Masukkan Kode Jenis yang akan dihapus : ");
        $variety = Variety::where('code', $code)->first();

        if (!$variety) {
            $this->error("Jenis dengan kode {$code} tidak ditemukan!");
            return;
        }

        if (!$this->confirm("Apakah Anda yakin ingin menghapus jenis '{$variety->name}'?")) {
            $this->info("Penghapusan dibatalkan.");
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

        $categories = Category::all()->pluck('name', 'id')->toArray();
        $product->category_id = select(label: 'Pilih Kategori Barang:', options: $categories);

        $varieties = Variety::all()->pluck('name', 'id')->toArray();
        $product->variety_id = select(label: 'Pilih Jenis Barang:', options: $varieties);

        $product->code = (int) $this->ask("Masukkan Kode Barang : ");
        $product->name = $this->ask("Masukkan Nama Barang : ");

        do {
            $inputPrice = (int) $this->ask("Masukkan Harga Barang (harus > 0) : ");
            if ($inputPrice <= 0)
                $this->error("Harga tidak boleh nol atau negatif!");
        } while ($inputPrice <= 0);
        $product->price = $inputPrice;

        do {
            $inputStock = (int) $this->ask("Masukkan Jumlah Stok (harus >= 0) : ");
            if ($inputStock < 0)
                $this->error("Stok tidak boleh negatif!");
        } while ($inputStock < 0);
        $product->stock = $inputStock;

        if ($product->save()) {
            $this->notify("Success", "data berhasil disimpan");
        } else {
            $this->notify("Failed", "data gagal disimpan");
        }
    }

    private function handleProductEdit()
    {
        $this->info("Ubah Barang");
        $code = (int) $this->ask("Masukkan Kode Barang yang akan diubah : ");
        $product = Product::where('code', $code)->first();

        if (!$product) {
            $this->error("Barang dengan kode {$code} tidak ditemukan!");
            return;
        }

        $categories = Category::all()->pluck('name', 'id')->toArray();
        $product->category_id = select(label: 'Pilih Kategori Barang:', options: $categories);

        $varieties = Variety::all()->pluck('name', 'id')->toArray();
        $product->variety_id = select(label: 'Pilih Jenis Barang:', options: $varieties);

        $product->code = (int) $this->ask("Masukkan Kode Barang : ");
        $product->name = $this->ask("Masukkan Nama Barang : ");
        $product->price = (int) $this->ask("Masukkan Harga Barang : ");
        $product->stock = (int) $this->ask("Masukkan Jumlah Stok : ", (string) $product->stock);

        if ($product->save()) {
            $this->notify("Success", "data berhasil diubah");
        } else {
            $this->notify("Failed", "data gagal diubah");
        }
    }

    private function handleProductDelete()
    {
        $this->info("Hapus Barang");
        $code = (int) $this->ask("Masukkan Kode Barang yang akan dihapus : ");
        $product = Product::where('code', $code)->first();

        if (!$product) {
            $this->error("Barang dengan kode {$code} tidak ditemukan!");
            return;
        }

        if (!$this->confirm("Apakah Anda yakin ingin menghapus barang '{$product->name}'?")) {
            $this->info("Penghapusan dibatalkan.");
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
        $headers = ['kode', 'nama', 'harga', 'jumlah', 'bayar', 'dibuat', 'diubah'];
        $data = SaleTransaction::all()->map(function ($item) {
            return [
                'kode' => $item->product ? $item->product->code : 'DEL',
                'nama' => $item->product ? $item->product->name : 'Deleted',
                'harga' => $item->price,
                'jumlah' => $item->quantity,
                'jumlah bayar' => $item->quantity * $item->price,
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
