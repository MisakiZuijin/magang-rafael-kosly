<?php

use App\Services\PenghuniKamarService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sewa:check-jatuh-tempo', function (PenghuniKamarService $penghuniKamarService) {
    $this->info('Memeriksa masa sewa kamar (H-7 Bulanan, H-3 Bulanan/Mingguan, & Jatuh Tempo)...');
    $result = $penghuniKamarService->periksaSemuaNotifikasiSewa();
    $h7 = $result['h7']['processed'] ?? 0;
    $h3 = $result['h3']['processed'] ?? 0;
    $jt = $result['jatuh_tempo']['processed'] ?? 0;
    $this->info("Pemeriksaan selesai: {$h7} notifikasi H-7, {$h3} notifikasi H-3, dan {$jt} notifikasi Jatuh Tempo berhasil dikirim ke Grup WA Kamar & Penghuni.");
})->purpose('Memeriksa dan mengirimkan notifikasi Web dan WhatsApp untuk sewa kamar (H-7, H-3, dan Jatuh Tempo)');

// Jadwalkan otomatis per menit
\Illuminate\Support\Facades\Schedule::command('sewa:check-jatuh-tempo')->everyMinute();

