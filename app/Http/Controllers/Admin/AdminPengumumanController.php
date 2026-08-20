<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\KamarService;
use App\Services\KosService;
use App\Services\NotifikasiService;
use App\Services\PengumumanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Services\LogAktivitasService;
use App\Services\WhatsAppService;

class AdminPengumumanController extends Controller
{
    public function __construct(
        protected PengumumanService $pengumumanService,
        protected NotifikasiService $notifikasiService,
        protected WhatsAppService $whatsAppService,
        protected KosService $kosService,
        protected KamarService $kamarService,
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function index()
    {
        $pengumumans = $this->pengumumanService->getAll();
        $view = request()->is('superadmin*') ? 'superadmin.pengumuman.index' : 'admin.pengumuman.index';
        return view($view, compact('pengumumans'));
    }

    public function create()
    {
        $kosList = $this->kosService->getAll();
        $view = request()->is('superadmin*') ? 'superadmin.pengumuman.create' : 'admin.pengumuman.create';
        return view($view, compact('kosList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:200',
            'isi' => 'required|string',
            'tipe' => 'required|in:pembayaran,aturan,info',
            'channel' => 'required|in:web,whatsapp,keduanya',
            'target_tipe' => 'required|in:kos,kamar,semua',
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'integer',
        ]);

        $pengumuman = $this->pengumumanService->create([
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'tipe' => $validated['tipe'],
            'channel' => $validated['channel'],
            'dibuat_oleh' => Auth::id(),
        ], $this->buildTargets($validated));

        // Jika tipe pengumuman adalah aturan kos baru, daftarkan otomatis ke tabel aturan_kos
        if ($validated['tipe'] === 'aturan') {
            $this->syncAturanKos($validated);
        }

        // Kirim notifikasi ke target sesuai channel terpilih
        $this->sendNotifications($validated, $pengumuman->judul, $pengumuman->isi);

        $this->logAktivitasService->log(
            'kirim_pengumuman',
            "Mengirim pengumuman \"{$pengumuman->judul}\" via channel {$validated['channel']}"
        );

        $prefix = request()->is('superadmin*') ? 'superadmin.' : 'admin.';
        return redirect()->route($prefix . 'pengumuman.index')->with('success', 'Pengumuman berhasil dikirim.');
    }

    private function syncAturanKos(array $validated): void
    {
        $textAturan = $validated['judul'] . ': ' . $validated['isi'];

        if ($validated['target_tipe'] === 'semua') {
            $kosIds = \App\Models\Kos::pluck('id');
            foreach ($kosIds as $kosId) {
                \App\Models\AturanKos::create([
                    'kos_id' => $kosId,
                    'isi_aturan' => $textAturan,
                ]);
            }
        } elseif ($validated['target_tipe'] === 'kos') {
            foreach ($validated['target_ids'] ?? [] as $kosId) {
                \App\Models\AturanKos::create([
                    'kos_id' => $kosId,
                    'isi_aturan' => $textAturan,
                ]);
            }
        } elseif ($validated['target_tipe'] === 'kamar') {
            $kamars = \App\Models\Kamar::whereIn('id', $validated['target_ids'] ?? [])->get();
            $grouped = $kamars->groupBy('kos_id');

            foreach ($grouped as $kosId => $kamarItems) {
                $kamarCodes = $kamarItems->pluck('kode_kamar')->implode(', ');
                \App\Models\AturanKos::create([
                    'kos_id' => $kosId,
                    'isi_aturan' => "[Kamar {$kamarCodes}] {$textAturan}",
                ]);
            }
        }
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
            $userIds = \App\Models\User::pluck('id')->toArray();
        } elseif ($validated['target_tipe'] === 'kos') {
            $userIds = \App\Models\PenghuniKamar::whereHas('kamar', function ($q) use ($validated) {
                $q->whereIn('kos_id', $validated['target_ids'] ?? []);
            })->pluck('penghuni_id')->unique()->toArray();
        } elseif ($validated['target_tipe'] === 'kamar') {
            $userIds = \App\Models\PenghuniKamar::whereIn('kamar_id', $validated['target_ids'] ?? [])
                ->pluck('penghuni_id')->unique()->toArray();
        }

        $channel = $validated['channel'] ?? 'web';

        // 1. Web App Notification
        if (in_array($channel, ['web', 'keduanya'])) {
            $this->notifikasiService->sendBulk($userIds, $judul, $pesan, 'web');
        }

        // 2. WhatsApp Notification (Personal PM & WA Group Kamar)
        if (in_array($channel, ['whatsapp', 'keduanya'])) {
            $this->whatsAppService->sendBulk($userIds, $judul, $pesan);

            // Kirim langsung ke Grup WhatsApp Kamar jika wa_group_id diisi
            $targetedKamars = collect();
            if ($validated['target_tipe'] === 'semua') {
                $targetedKamars = \App\Models\Kamar::with('kos')->whereNotNull('wa_group_id')->where('wa_group_id', '!=', '')->get();
            } elseif ($validated['target_tipe'] === 'kos') {
                $targetedKamars = \App\Models\Kamar::with('kos')->whereIn('kos_id', $validated['target_ids'] ?? [])
                    ->whereNotNull('wa_group_id')->where('wa_group_id', '!=', '')->get();
            } elseif ($validated['target_tipe'] === 'kamar') {
                $targetedKamars = \App\Models\Kamar::with('kos')->whereIn('id', $validated['target_ids'] ?? [])
                    ->whereNotNull('wa_group_id')->where('wa_group_id', '!=', '')->get();
            }

            foreach ($targetedKamars as $kamarItem) {
                $kosNama = $kamarItem->kos->nama ?? 'Kos';
                $this->whatsAppService->sendDirect($kamarItem->wa_group_id, "PENGUMUMAN KAMAR {$kamarItem->kode_kamar} ({$kosNama}) - " . $judul, $pesan);
            }
        }
    }
}
