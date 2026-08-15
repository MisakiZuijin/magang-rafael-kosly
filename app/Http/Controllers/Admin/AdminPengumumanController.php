<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\KamarService;
use App\Services\KosService;
use App\Services\NotifikasiService;
use App\Services\PengumumanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPengumumanController extends Controller
{
    public function __construct(
        protected PengumumanService $pengumumanService,
        protected NotifikasiService $notifikasiService,
        protected KosService $kosService,
        protected KamarService $kamarService
    ) {}

    public function index()
    {
        $pengumumans = $this->pengumumanService->getAll();
        return view('admin.pengumuman.index', compact('pengumumans'));
    }

    public function create()
    {
        $kosList = $this->kosService->getAll();
        return view('admin.pengumuman.create', compact('kosList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:200',
            'isi' => 'required|string',
            'tipe' => 'required|in:pembayaran,aturan,info',
            'target_tipe' => 'required|in:kos,kamar,semua',
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'integer',
        ]);

        $pengumuman = $this->pengumumanService->create([
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'tipe' => $validated['tipe'],
            'dibuat_oleh' => Auth::id(),
        ], $this->buildTargets($validated));

        // Kirim notifikasi ke target
        $this->sendNotifications($validated, $pengumuman->judul, $pengumuman->isi);

        return redirect()->route('admin.pengumuman.index')->with('success', 'Pengumuman berhasil dikirim.');
    }

    private function buildTargets(array $validated): array
    {
        $targets = [];

        if ($validated['target_tipe'] === 'semua') {
            return $targets;
        }

        foreach ($validated['target_ids'] ?? [] as $id) {
            $targets[] = [
                'tipe' => $validated['target_tipe'],
                'id' => $id,
            ];
        }

        return $targets;
    }

    private function sendNotifications(array $validated, string $judul, string $pesan): void
    {
        $userIds = [];

        if ($validated['target_tipe'] === 'semua') {
            $userIds = \App\Models\User::whereIn('role', ['penghuni', 'mitra'])->pluck('id')->toArray();
        } elseif ($validated['target_tipe'] === 'kos') {
            $userIds = \App\Models\PenghuniKamar::whereHas('kamar', function ($q) use ($validated) {
                $q->whereIn('kos_id', $validated['target_ids'] ?? []);
            })->where('status', 'aktif')->pluck('penghuni_id')->unique()->toArray();
        } elseif ($validated['target_tipe'] === 'kamar') {
            $userIds = \App\Models\PenghuniKamar::whereIn('kamar_id', $validated['target_ids'] ?? [])
                ->where('status', 'aktif')->pluck('penghuni_id')->unique()->toArray();
        }

        $this->notifikasiService->sendBulk($userIds, $judul, $pesan);
    }
}
