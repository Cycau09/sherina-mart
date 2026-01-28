<?php

declare(strict_types=1);

namespace App\Actions\Transaksi;

use App\Actions\Transaksi\TransaksiBaru;
use App\Actions\Transaksi\BatalkanTransaksi;
use App\Support\BaseAction;
use function Laravel\Prompts\select;

/**
 * Action untuk menampilkan Submenu Transaksi
 * Memisahkan logika pemilihan transaksi dari MenuCommand
 */
class MenuTransaksi extends BaseAction
{
    public function jalankan(): void
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
                (new BatalkanTransaksi($this->command))->jalankan();
            }

            if ($subOption === 'new') {
                (new TransaksiBaru($this->command))->jalankan();
            }
        }
    }
}
