<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\LogAktivitasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuperAdminPengaturanController extends Controller
{
    public function __construct(
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function index()
    {
        $appName = Setting::appName();
        $appLogo = Setting::appLogo();
        $appFavicon = Setting::appFavicon();
        $currentLogoPath = Setting::getByKey('app_logo');
        $hasCustomFavicon = file_exists(public_path('favicon.ico'));

        return view('superadmin.pengaturan.index', compact(
            'appName',
            'appLogo',
            'appFavicon',
            'currentLogoPath',
            'hasCustomFavicon'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:100',
            'app_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'app_favicon' => 'nullable|file|max:2048',
        ], [
            'app_name.required' => 'Nama website wajib diisi.',
            'app_logo.image' => 'Logo harus berupa berkas gambar.',
            'app_logo.max' => 'Ukuran logo maksimal 2MB.',
            'app_favicon.max' => 'Ukuran favicon maksimal 2MB.',
        ]);

        // 1. Simpan Nama Web
        Setting::setKey('app_name', trim($request->input('app_name')));

        // 2. Simpan Logo Web (ke storage publik)
        if ($request->hasFile('app_logo')) {
            $oldLogo = Setting::getByKey('app_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            $path = $request->file('app_logo')->store('settings', 'public');
            Setting::setKey('app_logo', $path);
        }

        // 3. Simpan Favicon Web (langsung ke public/favicon.ico)
        if ($request->hasFile('app_favicon')) {
            $file = $request->file('app_favicon');
            $file->move(public_path(), 'favicon.ico');
            Setting::setKey('app_favicon', 'favicon.ico');
        }

        $this->logAktivitasService->log('update_pengaturan_web', 'Super Admin memperbarui identitas website (Nama, Logo, atau Favicon)');

        return redirect()->back()->with('success', 'Pengaturan website berhasil disimpan dan diperbarui.');
    }
}
