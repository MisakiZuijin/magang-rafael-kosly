<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\KamarService;
use App\Services\KosService;
use App\Services\PenghuniKamarService;
use App\Services\UserService;
use Illuminate\Http\Request;

class AdminKosController extends Controller
{
    public function __construct(
        protected KosService $kosService,
        protected KamarService $kamarService,
        protected UserService $userService,
        protected PenghuniKamarService $penghuniKamarService
    ) {}

    public function index()
    {
        $kosList = $this->kosService->getWithKamarCount();
        $mitras = $this->userService->getActiveByRole('mitra');

        return view('admin.kos.index', compact('kosList', 'mitras'));
    }

    public function storeKos(Request $request)
    {
        $validated = $request->validate([
            'mitra_id' => 'required|exists:users,id',
            'nama' => 'required|string|max:100',
            'alamat' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'deskripsi' => 'nullable|string',
            'no_rekening' => 'nullable|string|max:50',
            'bank' => 'nullable|string|max:50',
            'nama_pemilik_rekening' => 'nullable|string|max:100',
        ]);

        $this->kosService->create($validated);

        return redirect()->back()->with('success', 'Kos berhasil didaftarkan.');
    }

    public function storeKamar(Request $request)
    {
        $validated = $request->validate([
            'kos_id' => 'required|exists:kos,id',
            'kode_kamar' => 'required|string|max:20',
            'tipe' => 'required|in:standar,berbagi',
            'harga_per_hari' => 'nullable|numeric',
            'harga_per_bulan' => 'required|numeric',
            'kapasitas' => 'required|integer|min:1',
        ]);

        $validated['status'] = 'kosong';
        $this->kamarService->create($validated);

        return redirect()->back()->with('success', 'Kamar berhasil didaftarkan.');
    }

    public function daftarPenghuni(Request $request)
    {
        $validated = $request->validate([
            'kamar_id' => 'required|exists:kamar,id',
            'penghuni_id' => 'required|exists:users,id',
            'tanggal_masuk' => 'required|date',
            'tanggal_keluar' => 'nullable|date|after:tanggal_masuk',
            'durasi' => 'required|in:harian,bulanan',
        ]);

        $validated['status'] = 'aktif';
        $this->penghuniKamarService->create($validated);

        return redirect()->back()->with('success', 'Penghuni berhasil didaftarkan ke kamar.');
    }
}
