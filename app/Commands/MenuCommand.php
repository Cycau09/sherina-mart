<?php

declare(strict_types=1);

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\select;

// ========== MULAI: Import Action Classes (Refactoring Pattern) ==========
use App\Actions\Kategori\DaftarKategori;
use App\Actions\Kategori\TambahKategori;
use App\Actions\Kategori\UbahKategori;
use App\Actions\Kategori\HapusKategori;
use App\Actions\Jenis\DaftarJenis;
use App\Actions\Jenis\TambahJenis;
use App\Actions\Jenis\UbahJenis;
use App\Actions\Jenis\HapusJenis;
use App\Actions\Barang\DaftarBarang;
use App\Actions\Barang\TambahBarang;
use App\Actions\Barang\UbahBarang;
use App\Actions\Barang\HapusBarang;
use App\Actions\Transaksi\TransaksiBaru;
use App\Actions\Transaksi\BatalkanTransaksi;
use App\Actions\Laporan\LaporanPenjualan;
// ========== AKHIR: Import Action Classes ==========

class MenuCommand extends Command
{
    protected $signature = 'app:menu-command';
    protected $description = 'Menampilkan Menu pada pengguna';

    public function handle()
    {
        // ===========================================================================
        // MENU UTAMA - Loop tak terbatas sampai user pilih keluar
        // Menggunakan Laravel Prompts (select) untuk kompatibilitas Windows Terminal
        // ===========================================================================
        
        while (true) {
            try {
                // Tampilkan Logo ASCII Header
                $this->line("");
                $this->line($this->getAsciiHeader());
                $this->line("");
                $this->info("Selamat Datang di Aplikasi Sherina Mart");
                $this->line("");

                // Pilihan Menu Utama
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

                // Konfirmasi Keluar
                if ($option === 15 || $option === null) {
                    if ($this->confirm("Apakah Anda yakin ingin keluar dari aplikasi?", true)) {
                        $this->info("Terimakasih telah menggunakan applikasi kami.");
                        break;
                    } else {
                        continue; // Kembali ke menu utama
                    }
                }

                // ===========================================================================
                // DISPATCHER - Mengalihkan ke Action Class yang sesuai
                // Semua menu sekarang menggunakan Action Pattern (File terpisah)
                // Struktur kode jadi sangat ringkas dan mudah di-maintenance
                // ===========================================================================
                switch ($option) {
                    // Transaksi - menggunakan Action Pattern
                    case 1:
                        $this->handleTransaction();
                        break;
                    
                    // Kategori - menggunakan Action Pattern
                    case 2:
                        (new DaftarKategori($this))->jalankan();
                        break;
                    case 3:
                        (new TambahKategori($this))->jalankan();
                        break;
                    case 4:
                        (new UbahKategori($this))->jalankan();
                        break;
                    case 5:
                        (new HapusKategori($this))->jalankan();
                        break;
                    
                    // Jenis Barang - menggunakan Action Pattern
                    case 6:
                        (new DaftarJenis($this))->jalankan();
                        break;
                    case 7:
                        (new TambahJenis($this))->jalankan();
                        break;
                    case 8:
                        (new UbahJenis($this))->jalankan();
                        break;
                    case 9:
                        (new HapusJenis($this))->jalankan();
                        break;
                    
                    // Barang - menggunakan Action Pattern
                    case 10:
                        (new DaftarBarang($this))->jalankan();
                        break;
                    case 11:
                        (new TambahBarang($this))->jalankan();
                        break;
                    case 12:
                        (new UbahBarang($this))->jalankan();
                        break;
                    case 13:
                        (new HapusBarang($this))->jalankan();
                        break;
                    
                    // Laporan - menggunakan Action Pattern
                    case 14:
                        (new LaporanPenjualan($this))->jalankan();
                        break;
                }

            } catch (\Throwable $e) {
                // Global Error Handler - mencegah aplikasi crash
                $this->error("❌ TERJADI KESALAHAN: " . $e->getMessage());
                $this->line("Lokasi Error: " . $e->getFile() . " (Baris " . $e->getLine() . ")");
                $this->info("⚠️  Aplikasi akan kembali ke Menu Utama...");
                $this->ask("Tekan Enter untuk melanjutkan");
            }
        }
    }

    // ===========================================================================
    // METHOD TRANSAKSI - Submenu untuk memilih jenis transaksi
    // ===========================================================================

    private function handleTransaction()
    {
        // Submenu Transaksi - loop sampai user memilih kembali
        while (true) {
            $subOption = select(
                label: 'Menu Transaksi:',
                options: [
                    'new' => 'Transaksi Pembelian Baru',
                    'void' => 'Batalkan Transaksi (Void)',
                    'cancel' => 'Kembali ke Menu Utama',
                ],
            );

            if ($subOption === 'cancel') {
                return; // Kembali ke menu utama
            }

            if ($subOption === 'void') {
                (new BatalkanTransaksi($this))->jalankan();
            }

            if ($subOption === 'new') {
                (new TransaksiBaru($this))->jalankan();
            }
        }
    }

    // ===========================================================================
    // HELPER METHODS (Fungsi pembantu yang dipakai di berbagai tempat)
    // ===========================================================================

    /**
     * Tampilkan logo ASCII header aplikasi
     */
    private function getAsciiHeader(): string
    {
        return <<<EOT
   _____ __               _                __  __            __ 
  / ___// /_  ___  ____  (_)___  ____ _   /  |/  /___ ______/ /_
  \__ \/ __ \/ _ \/ __ \/ / __ \/ __ `/  / /|_/ / __ `/ ___/ __/
 ___/ / / / /  __/ /   / / / / / /_/ /  / /  / / /_/ / /  / /_  
/____/_/ /_/\___/_/   /_/_/ /_/\__,_/  /_/  /_/\__,_/_/   \__/  
                                                  by cycau09
EOT;
    }

    /**
     * Schedule command (required oleh LaravelZero)
     */
    public function schedule(Schedule $schedule): void
    {
        // Tidak dipakai untuk sekarang
    }
}
