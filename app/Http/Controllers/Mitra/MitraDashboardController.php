<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\Kamar;
use App\Services\DashboardService;
use App\Services\KamarService;
use App\Services\LogAktivitasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MitraDashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected KamarService $kamarService,
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function index()
    {
        $data = $this->dashboardService->getMitraData(Auth::id());
        return view('mitra.dashboard', compact('data'));
    }

    public function kamar()
    {
        $user = Auth::user();
        $kosList = $user->kos;

        $kamarData = collect();
        foreach ($kosList as $kos) {
            $kamars = $this->kamarService->getByKosWithPenghuni($kos->id);
            $kamarData = $kamarData->merge($kamars);
        }

        return view('mitra.kamar', compact('kamarData', 'kosList'));
    }

    public function showKamar(string|int $id)
    {
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');
        $kamar = Kamar::with(['kos.mitra', 'penghuniKamar.penghuni'])
            ->whereIn('kos_id', $mitraKosIds)
            ->where(function($q) use ($id) {
                $q->where('kode_kamar', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0);
            })
            ->firstOrFail();

        return view('mitra.show', compact('kamar'));
    }

    public function updateKamar(Request $request, string|int $id)
    {
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');
        $kamar = Kamar::with('kos')
            ->whereIn('kos_id', $mitraKosIds)
            ->where(function($q) use ($id) {
                $q->where('kode_kamar', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0);
            })
            ->firstOrFail();

        if ($kamar->kos->is_locked) {
            return redirect()->back()->with('error', 'Akses edit kamar untuk kos ini sedang dikunci oleh Admin/SuperAdmin.');
        }

        if ($request->has('harga_per_bulan')) {
            $request->merge(['harga_per_bulan' => preg_replace('/[^0-9]/', '', (string)$request->input('harga_per_bulan'))]);
        }
        if ($request->has('harga_per_minggu') && $request->filled('harga_per_minggu')) {
            $request->merge(['harga_per_minggu' => preg_replace('/[^0-9]/', '', (string)$request->input('harga_per_minggu'))]);
        }
        if ($request->has('harga_per_hari') && $request->filled('harga_per_hari')) {
            $request->merge(['harga_per_hari' => preg_replace('/[^0-9]/', '', (string)$request->input('harga_per_hari'))]);
        }

        $validated = $request->validate([
            'kode_kamar' => 'required|string|max:20',
            'tipe' => 'required|in:standar,berbagi',
            'detail' => 'nullable|string',
            'foto.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'harga_per_hari' => 'nullable|numeric',
            'harga_per_minggu' => 'nullable|numeric',
            'harga_per_bulan' => 'required|numeric',
            'kapasitas' => 'required|integer|min:1',
            'wa_group_id' => 'nullable|string|max:100',
            'link_grup_wa' => 'nullable|url|max:255',
        ]);

        $fotoPaths = $kamar->foto ?? [];
        if ($request->hasFile('foto')) {
            foreach ($request->file('foto') as $file) {
                if ($file && $file->isValid()) {
                    $fotoPaths[] = $file->store('kamar', 'public');
                }
            }
        }

        $validated['foto'] = array_values($fotoPaths);
        $activeCount = \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)->where('status', 'aktif')->count();
        if ($validated['tipe'] === 'berbagi') {
            $validated['kapasitas'] = $activeCount >= 3 ? 3 : 2;
        } else {
            $validated['kapasitas'] = 1;
        }

        $this->kamarService->update($kamar->id, $validated);
        $this->logAktivitasService->log('update_kamar_mitra', "Mitra {$user->nama} memperbarui data Kamar {$validated['kode_kamar']} pada Kos {$kamar->kos->nama}");

        return redirect()->back()->with('success', 'Data kamar berhasil diperbarui.');
    }

    public function deleteFotoKamar(Request $request, string|int $id)
    {
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');
        $kamar = Kamar::with('kos')
            ->whereIn('kos_id', $mitraKosIds)
            ->where(function($q) use ($id) {
                $q->where('kode_kamar', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0);
            })
            ->firstOrFail();

        if ($kamar->kos->is_locked) {
            return redirect()->back()->with('error', 'Akses edit kamar untuk kos ini sedang dikunci oleh Admin/SuperAdmin.');
        }

        $index = (int)$request->input('index');
        $fotos = $kamar->foto ?? [];

        if (isset($fotos[$index])) {
            Storage::disk('public')->delete($fotos[$index]);
            array_splice($fotos, $index, 1);
            $kamar->update(['foto' => array_values($fotos)]);
        }

        return redirect()->back()->with('success', 'Foto kamar berhasil dihapus.');
    }
}
