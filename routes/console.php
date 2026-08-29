<?php

use App\Services\PenghuniKamarService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sewa:check-jatuh-tempo', function (PenghuniKamarService $penghuniKamarService) {
    $this->info('Memeriksa sewa kamar yang telah jatuh tempo...');
    $result = $penghuniKamarService->periksaDanKirimNotifikasiJatuhTempo();
    $this->info("Pemeriksaan selesai. {$result['processed']} notifikasi Web & WhatsApp berhasil dikirimkan (1x).");
})->purpose('Memeriksa dan mengirimkan notifikasi Web dan WhatsApp untuk sewa kamar yang jatuh tempo');

// Jadwalkan otomatis per menit
\Illuminate\Support\Facades\Schedule::command('sewa:check-jatuh-tempo')->everyMinute();
