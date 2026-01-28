<?php

declare(strict_types=1);

namespace App\Support;

use LaravelZero\Framework\Commands\Command;

/**
 * Kelas dasar untuk semua Action
 * Menyediakan akses ke helper methods dari Command
 */
abstract class BaseAction
{
    protected Command $command;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Method utama yang harus diimplementasikan oleh setiap Action
     */
    abstract public function jalankan(): void;

    /**
     * Helper untuk validasi input angka
     */
    protected function tanyaAngka(string $pertanyaan, int $minimal = 0, ?string $default = null): int
    {
        do {
            $input = $this->command->ask($pertanyaan, $default);
            if (!is_numeric($input)) {
                $this->command->error("Input harus berupa angka!");
                continue;
            }
            $nilai = (int) $input;
            if ($nilai < $minimal) {
                $this->command->error("Nilai minimal adalah {$minimal}");
                continue;
            }
            return $nilai;
        } while (true);
    }

    /**
     * Helper untuk format Rupiah
     */
    protected function formatRupiah(int $jumlah): string
    {
        return "Rp " . number_format($jumlah, 0, ',', '.');
    }
}
