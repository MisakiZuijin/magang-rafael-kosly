<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\Penghuni\PenghuniDashboardController;
use App\Http\Controllers\Mitra\MitraDashboardController;
use App\Http\Controllers\Mitra\MitraKosController;
use App\Http\Controllers\Mitra\MitraPenggunaController;
use App\Http\Controllers\Mitra\MitraPembayaranController;
use App\Http\Controllers\Mitra\MitraWhatsAppController;
use App\Http\Controllers\Mitra\MitraAturanController;
use App\Http\Controllers\Mitra\MitraPengumumanController;
use App\Http\Controllers\Mitra\MitraLaporanController;
use App\Http\Controllers\Admin\AdminAturanController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPenggunaController;
use App\Http\Controllers\Admin\AdminKosController;
use App\Http\Controllers\Admin\AdminPengumumanController;
use App\Http\Controllers\Admin\AdminPembayaranController;
use App\Http\Controllers\Admin\AdminMapController;
use App\Http\Controllers\Admin\AdminLaporanController;
use App\Http\Controllers\Admin\AdminWhatsAppController;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\SuperAdminPencairanController;
use App\Http\Controllers\SuperAdmin\SuperAdminPengaturanController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => Auth::check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Universal Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Notifikasi & Nota
    Route::get('/pembayaran/{pembayaran}/nota', [AdminPembayaranController::class, 'nota'])->name('pembayaran.nota');
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/{id}/read', [NotifikasiController::class, 'markAsRead'])->name('notifikasi.read');
    Route::post('/notifikasi/read-all', [NotifikasiController::class, 'markAllAsRead'])->name('notifikasi.read-all');
    Route::get('/notifikasi/unread-count', [NotifikasiController::class, 'getUnreadCount'])->name('notifikasi.unread-count');

    /*
    |--------------------------------------------------------------------------
    | Penghuni Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:penghuni'])->prefix('penghuni')->name('penghuni.')->group(function () {
        Route::get('/dashboard', [PenghuniDashboardController::class, 'index'])->name('dashboard');
        Route::get('/aturan', [PenghuniDashboardController::class, 'aturan'])->name('aturan');
        Route::get('/pembayaran', [PenghuniDashboardController::class, 'pembayaran'])->name('pembayaran');
        Route::post('/pembayaran/upload', [PenghuniDashboardController::class, 'uploadBukti'])->name('pembayaran.upload')->middleware('throttle:10,1');
        Route::post('/checkout', [PenghuniDashboardController::class, 'selfCheckout'])->name('checkout');
    });

    /*
    |--------------------------------------------------------------------------
    | Mitra Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:mitra'])->prefix('mitra')->name('mitra.')->group(function () {
        Route::get('/dashboard', [MitraDashboardController::class, 'index'])->name('dashboard');
        Route::get('/kamar', [MitraDashboardController::class, 'kamar'])->name('kamar');
        Route::get('/kamar/{kamar}', [MitraDashboardController::class, 'showKamar'])->name('kamar.show');
        Route::put('/kamar/{kamar}', [MitraDashboardController::class, 'updateKamar'])->name('kamar.update');
        Route::delete('/kamar/{kamar}/foto', [MitraDashboardController::class, 'deleteFotoKamar'])->name('kamar.foto.delete');

        // FITUR KHUSUS MITRA PRO:
        Route::middleware(['mitra.pro'])->group(function () {
            // Kelola Kos & Kamar Mitra Pro
            Route::get('/kos', [MitraKosController::class, 'index'])->name('kos.index');
            Route::post('/kos', [MitraKosController::class, 'storeKos'])->name('kos.store');
            Route::put('/kos/{kos}', [MitraKosController::class, 'updateKos'])->name('kos.update');
            Route::delete('/kos/{kos}', [MitraKosController::class, 'destroyKos'])->name('kos.destroy');
            Route::post('/kamar', [MitraKosController::class, 'storeKamar'])->name('kamar.store');
            Route::get('/kos/kamar/{kamar}', [MitraKosController::class, 'showKamar'])->name('kos.kamar.show');
            Route::put('/kos/kamar/{kamar}', [MitraKosController::class, 'updateKamar'])->name('kos.kamar.update');
            Route::delete('/kos/kamar/{kamar}', [MitraKosController::class, 'destroyKamar'])->name('kos.kamar.destroy');
            Route::delete('/kos/kamar/{kamar}/foto', [MitraKosController::class, 'deleteFotoKamar'])->name('kos.kamar.foto.delete');
            Route::post('/daftar-penghuni', [MitraKosController::class, 'daftarPenghuni'])->name('penghuni.daftar');
            Route::post('/kosongkan-kamar/{kamar}', [MitraKosController::class, 'kosongkanKamar'])->name('kamar.kosongkan');
            Route::post('/checkout-penghuni/{id}', [MitraKosController::class, 'checkoutPenghuni'])->name('penghuni.checkout');

            // Kelola & Pendaftaran Akun Penghuni
            Route::get('/penghuni', [MitraPenggunaController::class, 'index'])->name('penghuni.index');
            Route::get('/penghuni/create', [MitraPenggunaController::class, 'create'])->name('penghuni.create');
            Route::post('/penghuni', [MitraPenggunaController::class, 'store'])->name('penghuni.store');
            Route::get('/penghuni/{id}/edit', [MitraPenggunaController::class, 'edit'])->name('penghuni.edit');
            Route::put('/penghuni/{id}', [MitraPenggunaController::class, 'update'])->name('penghuni.update');
            Route::delete('/penghuni/{id}', [MitraPenggunaController::class, 'destroy'])->name('penghuni.destroy');
            Route::post('/penghuni/{id}/toggle-active', [MitraPenggunaController::class, 'toggleActive'])->name('penghuni.toggle-active');

            // Verifikasi Pembayaran
            Route::get('/pembayaran', [MitraPembayaranController::class, 'index'])->name('pembayaran.index');
            Route::post('/pembayaran/{id}/verify', [MitraPembayaranController::class, 'verify'])->name('pembayaran.verify');
            Route::post('/pembayaran/{id}/reject', [MitraPembayaranController::class, 'reject'])->name('pembayaran.reject');

            // Aturan Kos
            Route::get('/aturan', [MitraAturanController::class, 'index'])->name('aturan.index');
            Route::post('/aturan', [MitraAturanController::class, 'store'])->name('aturan.store');
            Route::put('/aturan/{id}', [MitraAturanController::class, 'update'])->name('aturan.update');
            Route::delete('/aturan/{id}', [MitraAturanController::class, 'destroy'])->name('aturan.destroy');

            // Pengumuman
            Route::get('/pengumuman', [MitraPengumumanController::class, 'index'])->name('pengumuman.index');
            Route::get('/pengumuman/create', [MitraPengumumanController::class, 'create'])->name('pengumuman.create');
            Route::post('/pengumuman', [MitraPengumumanController::class, 'store'])->name('pengumuman.store');

            // Laporan & Export Excel/CSV
            Route::get('/laporan', [MitraLaporanController::class, 'index'])->name('laporan.index');
            Route::get('/laporan/filter', [MitraLaporanController::class, 'filter'])->name('laporan.filter');
            Route::get('/laporan/export-csv', [MitraLaporanController::class, 'exportCsv'])->name('laporan.export');

            // WhatsApp Gateway Pribadi (Fonnte)
            Route::get('/whatsapp', [MitraWhatsAppController::class, 'index'])->name('whatsapp.index');
            Route::post('/whatsapp', [MitraWhatsAppController::class, 'update'])->name('whatsapp.store');
            Route::post('/whatsapp/test', [MitraWhatsAppController::class, 'testSend'])->name('whatsapp.test');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Pengguna
        Route::get('/pengguna', [AdminPenggunaController::class, 'index'])->name('pengguna.index');
        Route::get('/pengguna/create', [AdminPenggunaController::class, 'create'])->name('pengguna.create');
        Route::post('/pengguna', [AdminPenggunaController::class, 'store'])->name('pengguna.store');
        Route::get('/pengguna/{user}/edit', [AdminPenggunaController::class, 'edit'])->name('pengguna.edit');
        Route::put('/pengguna/{user}', [AdminPenggunaController::class, 'update'])->name('pengguna.update');
        Route::post('/pengguna/{user}/toggle', [AdminPenggunaController::class, 'toggleActive'])->name('pengguna.toggle');
        Route::delete('/pengguna/{user}', [AdminPenggunaController::class, 'destroy'])->name('pengguna.destroy');

        // Pendaftaran Kos & Kamar
        Route::get('/kos', [AdminKosController::class, 'index'])->name('kos.index');
        Route::post('/kos', [AdminKosController::class, 'storeKos'])->name('kos.store');
        Route::post('/kamar', [AdminKosController::class, 'storeKamar'])->name('kamar.store');
        Route::post('/daftar-penghuni', [AdminKosController::class, 'daftarPenghuni'])->name('penghuni.daftar');
        Route::post('/kosongkan-kamar/{kamar}', [AdminKosController::class, 'kosongkanKamar'])->name('kamar.kosongkan');
        Route::post('/checkout-penghuni/{id}', [AdminKosController::class, 'checkoutPenghuni'])->name('penghuni.checkout');
        Route::put('/kos/{kos}', [AdminKosController::class, 'updateKos'])->name('kos.update');
        Route::get('/kamar/{kamar}', [AdminKosController::class, 'showKamar'])->name('kamar.show');
        Route::put('/kamar/{kamar}', [AdminKosController::class, 'updateKamar'])->name('kamar.update');
        Route::delete('/kamar/{kamar}', [AdminKosController::class, 'destroyKamar'])->name('kamar.destroy');
        Route::delete('/kamar/{kamar}/foto', [AdminKosController::class, 'deleteFotoKamar'])->name('kamar.foto.delete');
        Route::delete('/kos/{kos}', [AdminKosController::class, 'destroyKos'])->name('kos.destroy');

        // Pengumuman
        Route::get('/pengumuman', [AdminPengumumanController::class, 'index'])->name('pengumuman.index');
        Route::get('/pengumuman/create', [AdminPengumumanController::class, 'create'])->name('pengumuman.create');
        Route::post('/pengumuman', [AdminPengumumanController::class, 'store'])->name('pengumuman.store');

        // Aturan Kos
        Route::get('/aturan', [AdminAturanController::class, 'index'])->name('aturan.index');
        Route::post('/aturan', [AdminAturanController::class, 'store'])->name('aturan.store');
        Route::put('/aturan/{id}', [AdminAturanController::class, 'update'])->name('aturan.update');
        Route::delete('/aturan/{id}', [AdminAturanController::class, 'destroy'])->name('aturan.destroy');

        // Pembayaran
        Route::get('/pembayaran', [AdminPembayaranController::class, 'index'])->name('pembayaran.index');
        Route::post('/pembayaran/{id}/verify', [AdminPembayaranController::class, 'verify'])->name('pembayaran.verify');
        Route::post('/pembayaran/{id}/reject', [AdminPembayaranController::class, 'reject'])->name('pembayaran.reject');

        // Map
        Route::get('/map', [AdminMapController::class, 'index'])->name('map.index');

        // Laporan
        Route::get('/laporan', [AdminLaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/filter', [AdminLaporanController::class, 'filter'])->name('laporan.filter');
        Route::get('/laporan/export-csv', [AdminLaporanController::class, 'exportCsv'])->name('laporan.export');

        // WA Gateway (Fonnte)
        Route::get('/whatsapp', [AdminWhatsAppController::class, 'index'])->name('whatsapp.index');
        Route::post('/whatsapp', [AdminWhatsAppController::class, 'store'])->name('whatsapp.store');
        Route::post('/whatsapp/test', [AdminWhatsAppController::class, 'testSend'])->name('whatsapp.test');
    });

    /*
    |--------------------------------------------------------------------------
    | Super Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:super_admin'])->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'dashboard'])->name('dashboard');

        // Kelola Admin
        Route::get('/admin', [SuperAdminDashboardController::class, 'adminIndex'])->name('admin.index');
        Route::post('/admin', [SuperAdminDashboardController::class, 'adminStore'])->name('admin.store');
        Route::put('/admin/{user}', [SuperAdminDashboardController::class, 'adminUpdate'])->name('admin.update');
        Route::post('/admin/{user}/toggle', [SuperAdminDashboardController::class, 'adminToggle'])->name('admin.toggle');
        Route::delete('/admin/{user}', [SuperAdminDashboardController::class, 'adminDestroy'])->name('admin.destroy');

        // Kelola Lokasi Kantor
        Route::get('/kantor', [SuperAdminDashboardController::class, 'kantorIndex'])->name('kantor.index');
        Route::post('/kantor', [SuperAdminDashboardController::class, 'kantorStore'])->name('kantor.store');
        Route::put('/kantor/{kantor}', [SuperAdminDashboardController::class, 'kantorUpdate'])->name('kantor.update');
        Route::post('/kantor/{kantor}/toggle', [SuperAdminDashboardController::class, 'kantorToggle'])->name('kantor.toggle');
        Route::delete('/kantor/{kantor}', [SuperAdminDashboardController::class, 'kantorDestroy'])->name('kantor.destroy');

        // Pencairan Biaya Pendapatan Per Kos
        Route::get('/pencairan', [SuperAdminPencairanController::class, 'index'])->name('pencairan.index');
        Route::post('/pencairan/proses', [SuperAdminPencairanController::class, 'proses'])->name('pencairan.proses');

        // Pengaturan Web (Nama, Logo, Favicon)
        Route::get('/pengaturan', [SuperAdminPengaturanController::class, 'index'])->name('pengaturan.index');
        Route::post('/pengaturan', [SuperAdminPengaturanController::class, 'update'])->name('pengaturan.update');

        // Super Admin bisa akses semua route Admin
        Route::get('/pengguna', [AdminPenggunaController::class, 'index'])->name('pengguna.index');
        Route::get('/pengguna/create', [AdminPenggunaController::class, 'create'])->name('pengguna.create');
        Route::post('/pengguna', [AdminPenggunaController::class, 'store'])->name('pengguna.store');
        Route::get('/pengguna/{user}/edit', [AdminPenggunaController::class, 'edit'])->name('pengguna.edit');
        Route::put('/pengguna/{user}', [AdminPenggunaController::class, 'update'])->name('pengguna.update');
        Route::post('/pengguna/{user}/toggle', [AdminPenggunaController::class, 'toggleActive'])->name('pengguna.toggle');
        Route::delete('/pengguna/{user}', [AdminPenggunaController::class, 'destroy'])->name('pengguna.destroy');

        Route::get('/kos', [AdminKosController::class, 'index'])->name('kos.index');
        Route::post('/kos', [AdminKosController::class, 'storeKos'])->name('kos.store');
        Route::post('/kamar', [AdminKosController::class, 'storeKamar'])->name('kamar.store');
        Route::post('/daftar-penghuni', [AdminKosController::class, 'daftarPenghuni'])->name('penghuni.daftar');
        Route::post('/kosongkan-kamar/{kamar}', [AdminKosController::class, 'kosongkanKamar'])->name('kamar.kosongkan');
        Route::post('/checkout-penghuni/{id}', [AdminKosController::class, 'checkoutPenghuni'])->name('penghuni.checkout');
        Route::put('/kos/{kos}', [AdminKosController::class, 'updateKos'])->name('kos.update');
        Route::get('/kamar/{kamar}', [AdminKosController::class, 'showKamar'])->name('kamar.show');
        Route::put('/kamar/{kamar}', [AdminKosController::class, 'updateKamar'])->name('kamar.update');
        Route::delete('/kamar/{kamar}', [AdminKosController::class, 'destroyKamar'])->name('kamar.destroy');
        Route::delete('/kamar/{kamar}/foto', [AdminKosController::class, 'deleteFotoKamar'])->name('kamar.foto.delete');
        Route::delete('/kos/{kos}', [AdminKosController::class, 'destroyKos'])->name('kos.destroy');

        Route::get('/pengumuman', [AdminPengumumanController::class, 'index'])->name('pengumuman.index');
        Route::get('/pengumuman/create', [AdminPengumumanController::class, 'create'])->name('pengumuman.create');
        Route::post('/pengumuman', [AdminPengumumanController::class, 'store'])->name('pengumuman.store');

        Route::get('/aturan', [AdminAturanController::class, 'index'])->name('aturan.index');
        Route::post('/aturan', [AdminAturanController::class, 'store'])->name('aturan.store');
        Route::put('/aturan/{id}', [AdminAturanController::class, 'update'])->name('aturan.update');
        Route::delete('/aturan/{id}', [AdminAturanController::class, 'destroy'])->name('aturan.destroy');

        Route::get('/pembayaran', [AdminPembayaranController::class, 'index'])->name('pembayaran.index');
        Route::post('/pembayaran/{id}/verify', [AdminPembayaranController::class, 'verify'])->name('pembayaran.verify');
        Route::post('/pembayaran/{id}/reject', [AdminPembayaranController::class, 'reject'])->name('pembayaran.reject');

        Route::get('/map', [AdminMapController::class, 'index'])->name('map.index');
        Route::get('/laporan', [AdminLaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/filter', [AdminLaporanController::class, 'filter'])->name('laporan.filter');
        Route::get('/laporan/export-csv', [AdminLaporanController::class, 'exportCsv'])->name('laporan.export');

        // WA Gateway (Fonnte)
        Route::get('/whatsapp', [AdminWhatsAppController::class, 'index'])->name('whatsapp.index');
        Route::post('/whatsapp', [AdminWhatsAppController::class, 'store'])->name('whatsapp.store');
        Route::post('/whatsapp/test', [AdminWhatsAppController::class, 'testSend'])->name('whatsapp.test');
    });
});
