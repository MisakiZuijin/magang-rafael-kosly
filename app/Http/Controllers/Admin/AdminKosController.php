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

        $view = request()->is('superadmin*') ? 'superadmin.kos.index' : 'admin.kos.index';
        return view($view, compact('kosList', 'mitras'));
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
        $kamar = $this->kamarService->getById($request->input('kamar_id'));
        if (!$kamar) {
            return redirect()->back()->with('error', 'Kamar tidak ditemukan.');
        }

        if ($kamar->status === 'terisi') {
            return redirect()->back()->with('error', 'Kamar ini sudah terisi penuh.');
        }

        // Check if Penghuni 1 is already assigned to an active room
        $penghuni1Active = \App\Models\PenghuniKamar::where('penghuni_id', $request->input('penghuni_id'))
            ->where('status', 'aktif')
            ->exists();
        if ($penghuni1Active) {
            return redirect()->back()->with('error', 'Penghuni ke-1 yang Anda pilih sudah terdaftar dan sedang menempati kamar lain.');
        }

        // Check if Penghuni 2 is already assigned to an active room
        if ($request->filled('penghuni_id_2')) {
            $penghuni2Active = \App\Models\PenghuniKamar::where('penghuni_id', $request->input('penghuni_id_2'))
                ->where('status', 'aktif')
                ->exists();
            if ($penghuni2Active) {
                return redirect()->back()->with('error', 'Penghuni ke-2 yang Anda pilih sudah terdaftar dan sedang menempati kamar lain.');
            }
        }

        if ($kamar->tipe === 'berbagi' || $kamar->kapasitas == 2) {
            $validated = $request->validate([
                'kamar_id' => 'required|exists:kamar,id',
                'penghuni_id' => 'required|exists:users,id',
                'penghuni_id_2' => 'required|exists:users,id|different:penghuni_id',
                'tanggal_masuk' => 'required|date',
                'tanggal_keluar' => 'nullable|date|after:tanggal_masuk',
                'durasi' => 'required|in:harian,bulanan',
            ], [
                'penghuni_id_2.required' => 'Kamar tipe berbagi (2 orang) wajib mendaftarkan 2 orang penghuni.',
                'penghuni_id_2.different' => 'Penghuni ke-2 harus orang yang berbeda dari Penghuni ke-1.',
            ]);

            // Register Penghuni 1
            $this->penghuniKamarService->create([
                'kamar_id' => $validated['kamar_id'],
                'penghuni_id' => $validated['penghuni_id'],
                'tanggal_masuk' => $validated['tanggal_masuk'],
                'tanggal_keluar' => $validated['tanggal_keluar'],
                'durasi' => $validated['durasi'],
                'status' => 'aktif',
            ]);

            // Register Penghuni 2
            $this->penghuniKamarService->create([
                'kamar_id' => $validated['kamar_id'],
                'penghuni_id' => $validated['penghuni_id_2'],
                'tanggal_masuk' => $validated['tanggal_masuk'],
                'tanggal_keluar' => $validated['tanggal_keluar'],
                'durasi' => $validated['durasi'],
                'status' => 'aktif',
            ]);

            return redirect()->back()->with('success', 'Berhasil mendaftarkan 2 penghuni ke kamar tipe berbagi.');
        } else {
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

    public function checkoutPenghuni(int $id)
    {
        $this->penghuniKamarService->checkout($id);
        return redirect()->back()->with('success', 'Penghuni berhasil di-checkout.');
    }

    public function kosongkanKamar(int $kamarId)
    {
        $penghuniKamarList = \App\Models\PenghuniKamar::where('kamar_id', $kamarId)
            ->where('status', 'aktif')
            ->get();

        foreach ($penghuniKamarList as $pk) {
            $this->penghuniKamarService->checkout($pk->id);
        }

        $this->kamarService->updateStatus($kamarId, 'kosong');

        return redirect()->back()->with('success', 'Kamar berhasil dikosongkan.');
    }

    public function updateKos(Request $request, int $id)
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

        $this->kosService->update($id, $validated);

        return redirect()->back()->with('success', 'Data kos berhasil diperbarui.');
    }

    public function updateKamar(Request $request, int $id)
    {
        $validated = $request->validate([
            'kos_id' => 'required|exists:kos,id',
            'kode_kamar' => 'required|string|max:20',
            'tipe' => 'required|in:standar,berbagi',
            'harga_per_hari' => 'nullable|numeric',
            'harga_per_bulan' => 'required|numeric',
            'kapasitas' => 'required|integer|min:1',
        ]);

        $this->kamarService->update($id, $validated);

        return redirect()->back()->with('success', 'Data kamar berhasil diperbarui.');
    }
}
