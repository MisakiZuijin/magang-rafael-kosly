<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\AturanKos;
use App\Models\Kos;
use App\Services\AturanKosService;
use App\Services\LogAktivitasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MitraAturanController extends Controller
{
    public function __construct(
        protected AturanKosService $aturanKosService,
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function index()
    {
        $user = Auth::user();
        $kosList = Kos::where('mitra_id', $user->id)
            ->with(['aturanKos' => function ($q) {
                $q->latest();
            }])
            ->latest()
            ->get();
        $mitraKosIds = $kosList->pluck('id');

        $aturans = AturanKos::whereIn('kos_id', $mitraKosIds)->with('kos')->latest()->get();

        return view('mitra.aturan.index', compact('kosList', 'aturans'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'kos_id' => 'required|string',
            'isi_aturan' => 'required|string',
        ]);

        $kosId = $request->input('kos_id');
        $isiAturan = $request->input('isi_aturan');

        if ($kosId === 'all') {
            $allKos = Kos::where('mitra_id', $user->id)->get();
            foreach ($allKos as $kos) {
                $this->aturanKosService->create([
                    'kos_id' => $kos->id,
                    'isi_aturan' => $isiAturan,
                ]);
            }
            $this->logAktivitasService->log('tambah_aturan_mitra_pro', "Mitra Pro {$user->nama} menambahkan aturan ke SEMUA KOS miliknya: \"{$isiAturan}\"");
            return redirect()->back()->with('success', 'Aturan kos berhasil diterapkan ke seluruh kos milik Anda.');
        }

        $kos = Kos::where('id', $kosId)->where('mitra_id', $user->id)->firstOrFail();

        $this->aturanKosService->create([
            'kos_id' => $kos->id,
            'isi_aturan' => $isiAturan,
        ]);

        $this->logAktivitasService->log('tambah_aturan_mitra_pro', "Mitra Pro {$user->nama} menambahkan aturan di {$kos->nama}: \"{$isiAturan}\"");

        return redirect()->back()->with('success', 'Aturan kos berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');

        $aturan = AturanKos::whereIn('kos_id', $mitraKosIds)->findOrFail($id);

        $validated = $request->validate([
            'kos_id' => 'required|exists:kos,id',
            'isi_aturan' => 'required|string',
        ]);

        // Verifikasi kepemilikan kos baru
        Kos::where('id', $validated['kos_id'])->where('mitra_id', $user->id)->firstOrFail();

        $this->aturanKosService->update($aturan->id, $validated);
        $this->logAktivitasService->log('update_aturan_mitra_pro', "Mitra Pro {$user->nama} memperbarui aturan kos ID: {$id}");

        return redirect()->back()->with('success', 'Aturan kos berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');

        $aturan = AturanKos::whereIn('kos_id', $mitraKosIds)->findOrFail($id);

        $this->aturanKosService->delete($aturan->id);
        $this->logAktivitasService->log('hapus_aturan_mitra_pro', "Mitra Pro {$user->nama} menghapus aturan kos ID: {$id}");

        return redirect()->back()->with('success', 'Aturan kos berhasil dihapus.');
    }
}
