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
    public Category $category;
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
                // remove exit button with
                // ->disableDefaultItems()
                ->setTitleSeparator("*-")
                // ->addLineBreak('<3', 2)
                // ->addStaticItem('AREA 2')
                ->open();

            if ($option === null) {
                break;
            }

            // $this->info("Anda Memilih Pilihan : {$option}");
            if ($option == 0) {
                $sale = new SaleTransaction;
                // $this->info("Anda Memilih Pilihan : {$option} Transaksi Pembelian Barang");
                $products = Product::all()->pluck('name', 'id')->toArray();
                $sale->product_id = select(
                    label: 'Pilih Barang:',
                    options: $products,
                );
                $choosen_product = Product::find($sale->product_id);
                $sale->price = $choosen_product->price;
                $sale->quantity = (int)$this->ask("Masukkan Jumlah Barang : ");
                if ($sale->save()) {
                    $this->notify("Success", "data berhasil disimpan");
                    $payment = $sale->price * $sale->quantity;
                    $this->info("Total Bayar: {$payment}");
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
                $category->code = (int)$this->ask("Masukkan Kode Kategori : ");
                $category->name = $this->ask("Masukkan Nama Kategori : ");
                if ($category->save()) {
                    $this->notify("Success", "data berhasil disimpan");
                } else {
                    $this->notify("Failed", "data gagal disimpan");
                }

            } else if ($option == 3) {
                $this->info("Anda Memilih Pilihan : {$option} Ubah Kategori Barang");
                $code = (int)$this->ask("Masukkan Kode Kategori yang akan diubah : ");
                $category = Category::where('code', $code)->first();
                $category->code = (int)$this->ask("Masukkan Kode Kategori : ");
                $category->name = $this->ask("Masukkan Nama Kategori : ");
                if ($category->save()) {
                    $this->notify("Success", "data berhasil diubah");
                } else {
                    $this->notify("Failed", "data gagal diubah");
                }
            } else if ($option == 4) {
                $this->info("Anda Memilih Pilihan : {$option} Hapus Kategori Barang");
                $code = (int)$this->ask("Masukkan Kode Kategori yang akan dihapus : ");
                $category = Category::where('code', $code)->first();
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
                $variety->code = (int)$this->ask("Masukkan Kode Jenis : ");
                $variety->name = $this->ask("Masukkan Nama Jenis : ");
                if ($variety->save()) {
                    $this->notify("Success", "data berhasil disimpan");
                } else {
                    $this->notify("Failed", "data gagal disimpan");
                }
            } else if ($option == 7) {
                $this->info("Anda Memilih Pilihan : {$option} Ubah Jenis Barang");
                $code = (int)$this->ask("Masukkan Kode Jenis yang akan diubah : ");
                $variety = Variety::where('code', $code)->first();
                $variety->code = (int)$this->ask("Masukkan Kode Jenis : ");
                $variety->name = $this->ask("Masukkan Nama Jenis : ");
                if ($variety->save()) {
                    $this->notify("Success", "data berhasil diubah");
                } else {
                    $this->notify("Failed", "data gagal diubah");
                }
            } else if ($option == 8) {
                $this->info("Anda Memilih Pilihan : {$option} Hapus Jenis Barang");
                $code = (int)$this->ask("Masukkan Kode Jenis yang akan dihapus : ");
                $variety = Variety::where('code', $code)->first();
                if ($variety->delete()) {
                    $this->notify("Success", "data berhasil dihapus");
                } else {
                    $this->notify("Failed", "data gagal dihapus");
                }

            } else if ($option == 9) {
                $this->info("Anda Memilih Pilihan : {$option} Daftar Barang");
                $headers = ['kode', 'nama', 'harga', 'kategori', 'jenis','dibuat', 'diubah'];
                $data = Product::all()->map(function ($item) {
                    return [
                        'kode' => $item->code,
                        'nama' => $item->name,
                        'harga' => $item->price,
                        'kategori' => $item->category->name,
                        'jenis' => $item->variety->name,
                        'dibuat' => $item->created_at,
                        'diubah' => $item->updated_at,
                    ];
                })->toArray();
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

                $varieties = Variety::all()->pluck('name', 'id')->toArray();
                $product->variety_id = select(
                    label: 'Pilih Kategori Barang:',
                    options: $varieties,
                );

                $product->code = (int)$this->ask("Masukkan Kode Barang : ");
                $product->name = $this->ask("Masukkan Nama Barang : ");
                $product->price = (int)$this->ask("Masukkan Harga Barang : ");
                if ($product->save()) {
                    $this->notify("Success", "data berhasil disimpan");
                } else {
                    $this->notify("Failed", "data gagal disimpan");
                }

            } else if ($option == 11) {
                $this->info("Anda Memilih Pilihan : {$option} Ubah Barang");
            } else if ($option == 12) {
                $this->info("Anda Memilih Pilihan : {$option} Hapus Barang");
            } else if ($option == 13) {
                $headers = ['kode', 'nama', 'harga', 'jumlah', 'bayar', 'dibuat', 'diubah'];
                $data = SaleTransaction::all()->map(function ($item) {
                    return [
                        'kode' => $item->product->code,
                        'nama' => $item->product->name,
                        'harga' => $item->product->price,
                        'jumlah' => $item->quantity,
                        'jumlah bayar' => $item->quantity * $item->product->price,
                        'dibuat' => $item->created_at,
                        'diubah' => $item->updated_at,
                    ];
                })->toArray();
                $this->table($headers, $data);
                $this->ask("Tekan Enter untuk kembali ke menu utama");

            }
        }
        
        $this->info("Terimakasih telah menggunakan applikasi kami.");

    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
