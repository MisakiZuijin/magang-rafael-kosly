<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AturanKos;
use App\Services\AturanKosService;
use App\Services\KosService;
use App\Services\LogAktivitasService;
use Illuminate\Http\Request;

class AdminAturanController extends Controller
{
    public function __construct(
        protected AturanKosService $aturanKosService,
        protected KosService $kosService,
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function index()
    {
        $kosList = $this->kosService->getAll();
        $aturans = AturanKos::with('kos')->latest()->get();

        $view = request()->is('superadmin*') ? 'superadmin.aturan.index' : 'admin.aturan.index';
        return view($view, compact('kosList', 'aturans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kos_id' => 'required|string',
            'isi_aturan' => 'required|string',
        ]);

        $kosId = $request->input('kos_id');
        $isiAturan = $request->input('isi_aturan');

        if ($kosId === 'all') {
            $allKos = $this->kosService->getAll();
            foreach ($allKos as $kos) {
                $this->aturanKosService->create([
                    'kos_id' => $kos->id,
                    'isi_aturan' => $isiAturan,
                ]);
            }
            $this->logAktivitasService->log('tambah_aturan', "Menambahkan aturan baru ke SEMUA KOS: \"{$isiAturan}\"");
            return redirect()->back()->with('success', 'Aturan kos berhasil diterapkan ke semua gedung kos.');
        }

        $request->validate([
            'kos_id' => 'exists:kos,id',
        ]);

        $this->aturanKosService->create([
            'kos_id' => $kosId,
            'isi_aturan' => $isiAturan,
        ]);

        $kosObj = $this->kosService->getById((int)$kosId);
        $kosNama = $kosObj->nama ?? 'Kos';
        $this->logAktivitasService->log('tambah_aturan', "Menambahkan aturan baru di {$kosNama}: \"{$isiAturan}\"");

        return redirect()->back()->with('success', 'Aturan kos berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'kos_id' => 'required|exists:kos,id',
            'isi_aturan' => 'required|string',
        ]);

        $this->aturanKosService->update($id, $validated);
        $this->logAktivitasService->log('update_aturan', "Memperbarui aturan kos ID: {$id}");

        return redirect()->back()->with('success', 'Aturan kos berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $this->aturanKosService->delete($id);
        $this->logAktivitasService->log('hapus_aturan', "Menghapus aturan kos ID: {$id}");

        return redirect()->back()->with('success', 'Aturan kos berhasil dihapus.');
    }
}
