<?php

namespace App\Console\Commands;

use App\Services\PenghuniKamarService;
use Illuminate\Console\Command;

class CekNotifikasiSewaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cek-notifikasi-sewa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa dan kirim notifikasi masa sewa H-7 (Bulanan), H-3 (Bulanan & Mingguan), dan Jatuh Tempo ke Grup WA Kamar dan Penghuni';

    /**
     * Execute the console command.
     */
    public function handle(PenghuniKamarService $penghuniKamarService)
    {
        $this->info('Memeriksa masa sewa penghuni kos...');

        $hasil = $penghuniKamarService->periksaSemuaNotifikasiSewa();

        $h7Processed = $hasil['h7']['processed'] ?? 0;
        $h7Total = $hasil['h7']['total_h7'] ?? 0;
        $h3Processed = $hasil['h3']['processed'] ?? 0;
        $h3Total = $hasil['h3']['total_h3'] ?? 0;
        $jtProcessed = $hasil['jatuh_tempo']['processed'] ?? 0;
        $jtTotal = $hasil['jatuh_tempo']['total_expired'] ?? 0;

        $this->info("Notifikasi H-7 (Bulanan): {$h7Processed} dari {$h7Total} data diproses.");
        $this->info("Notifikasi H-3 (Bulanan & Mingguan): {$h3Processed} dari {$h3Total} data diproses.");
        $this->info("Notifikasi Jatuh Tempo: {$jtProcessed} dari {$jtTotal} data diproses.");

        return Command::SUCCESS;
    }
}
