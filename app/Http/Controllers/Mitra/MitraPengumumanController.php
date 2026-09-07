<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\AturanKos;
use App\Models\Kamar;
use App\Models\Kos;
use App\Models\PenghuniKamar;
use App\Models\User;
use App\Services\LogAktivitasService;
use App\Services\NotifikasiService;
use App\Services\PengumumanService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MitraPengumumanController extends Controller
{
    public function __construct(
        protected PengumumanService $pengumumanService,
        protected NotifikasiService $notifikasiService,
        protected WhatsAppService $whatsAppService,
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function index()
    {
        $user = Auth::user();
        $pengumumans = \App\Models\Pengumuman::where('dibuat_oleh', $user->id)
            ->with(['targets.kos', 'targets.kamar.kos', 'dibuatOleh'])
            ->latest()
            ->get();

        return view('mitra.pengumuman.index', compact('pengumumans'));
    }

    public function create()
    {
        $user = Auth::user();
        $kosList = Kos::where('mitra_id', $user->id)->with('kamar')->get();

        $selectedKamarId = request()->query('kamar_id');
        $selectedKamarIdsStr = request()->query('kamar_ids');
        $selectedKamarIds = [];

        if ($selectedKamarIdsStr) {
            $selectedKamarIds = array_map('intval', explode(',', $selectedKamarIdsStr));
        }
        if ($selectedKamarId) {
            $selectedKamarIds[] = (int) $selectedKamarId;
        }
        $selectedKamarIds = array_values(array_unique(array_filter($selectedKamarIds)));

        $prefilledKamar = null;
        if (!empty($selectedKamarId)) {
            $prefilledKamar = Kamar::with('kos')->find($selectedKamarId);
        }

        return view('mitra.pengumuman.create', compact('kosList', 'selectedKamarId', 'selectedKamarIds', 'prefilledKamar'));
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id')->toArray();

        $channel = $request->input('channel', 'web');
        $targetRule = in_array($channel, ['whatsapp', 'keduanya']) ? 'required|in:kos,kamar' : 'required|in:kos,kamar,semua';

        $validated = $request->validate([
            'judul' => 'required|string|max:200',
            'isi' => 'required|string',
            'tipe' => 'required|in:pembayaran,aturan,info',
            'channel' => 'required|in:web,whatsapp,keduanya',
            'target_tipe' => $targetRule,
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'integer',
        ]);

        // Verifikasi kepemilikan target kos / kamar
        if ($validated['target_tipe'] === 'kos') {
            foreach ($validated['target_ids'] ?? [] as $kosId) {
                if (!in_array($kosId, $mitraKosIds)) {
                    return redirect()->back()->with('error', 'Target kos tidak valid atau bukan milik Anda.');
                }
            }
        } elseif ($validated['target_tipe'] === 'kamar') {
            $validKamarCount = Kamar::whereIn('id', $validated['target_ids'] ?? [])
                ->whereIn('kos_id', $mitraKosIds)
                ->count();
            if ($validKamarCount !== count($validated['target_ids'] ?? [])) {
                return redirect()->back()->with('error', 'Target kamar tidak valid atau bukan milik Anda.');
            }
        }

        $pengumuman = $this->pengumumanService->create([
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'tipe' => $validated['tipe'],
            'channel' => $validated['channel'],
            'dibuat_oleh' => $user->id,
        ], $this->buildTargets($validated, $mitraKosIds));

        if ($validated['tipe'] === 'aturan') {
            $this->syncAturanKos($validated, $mitraKosIds);
        }

        $this->sendNotifications($validated, $pengumuman->judul, $pengumuman->isi, $mitraKosIds, $user);

        $this->logAktivitasService->log(
            'kirim_pengumuman_mitra_pro',
            "Mitra Pro {$user->nama} mengirim pengumuman \"{$pengumuman->judul}\" via channel {$validated['channel']}"
        );

        return redirect()->route('mitra.pengumuman.index')->with('success', 'Pengumuman berhasil dikirim.');
    }

    private function syncAturanKos(array $validated, array $mitraKosIds): void
    {
        $textAturan = $validated['judul'] . ': ' . $validated['isi'];

        if ($validated['target_tipe'] === 'semua') {
            foreach ($mitraKosIds as $kosId) {
                AturanKos::create([
                    'kos_id' => $kosId,
                    'isi_aturan' => $textAturan,
                ]);
            }
        } elseif ($validated['target_tipe'] === 'kos') {
            foreach ($validated['target_ids'] ?? [] as $kosId) {
                AturanKos::create([
                    'kos_id' => $kosId,
                    'isi_aturan' => $textAturan,
                ]);
            }
        } elseif ($validated['target_tipe'] === 'kamar') {
            $kamars = Kamar::whereIn('id', $validated['target_ids'] ?? [])->get();
            $grouped = $kamars->groupBy('kos_id');

            foreach ($grouped as $kosId => $kamarItems) {
                $kamarCodes = $kamarItems->pluck('kode_kamar')->implode(', ');
                AturanKos::create([
                    'kos_id' => $kosId,
                    'isi_aturan' => "[Kamar {$kamarCodes}] {$textAturan}",
                ]);
            }
        }
    }

    private function buildTargets(array $validated, array $mitraKosIds): array
    {
        $targets = [];

        if ($validated['target_tipe'] === 'semua') {
            foreach ($mitraKosIds as $kId) {
                $targets[] = ['tipe' => 'kos', 'id' => $kId];
            }
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

    private function sendNotifications(array $validated, string $judul, string $pesan, array $mitraKosIds, User $user): void
    {
        $userIds = [];

        if ($validated['target_tipe'] === 'semua') {
            $userIds = PenghuniKamar::whereHas('kamar', function ($q) use ($mitraKosIds) {
                $q->whereIn('kos_id', $mitraKosIds);
            })->where('status', 'aktif')->pluck('penghuni_id')->unique()->toArray();
        } elseif ($validated['target_tipe'] === 'kos') {
            $userIds = PenghuniKamar::whereHas('kamar', function ($q) use ($validated) {
                $q->whereIn('kos_id', $validated['target_ids'] ?? []);
            })->where('status', 'aktif')->pluck('penghuni_id')->unique()->toArray();
        } elseif ($validated['target_tipe'] === 'kamar') {
            $userIds = PenghuniKamar::whereIn('kamar_id', $validated['target_ids'] ?? [])
                ->where('status', 'aktif')
                ->pluck('penghuni_id')->unique()->toArray();
        }

        $channel = $validated['channel'] ?? 'web';

        if (in_array($channel, ['web', 'keduanya'])) {
            $this->notifikasiService->sendBulk($userIds, $judul, $pesan, 'web');
        }

        if (in_array($channel, ['whatsapp', 'keduanya'])) {
            $waItems = [];

            $targetedKamars = collect();
            if ($validated['target_tipe'] === 'kos') {
                $targetedKamars = Kamar::with('kos')
                    ->whereIn('kos_id', $validated['target_ids'] ?? [])
                    ->whereNotNull('wa_group_id')
                    ->where('wa_group_id', '!=', '')
                    ->where('wa_group_id', '!=', '-')
                    ->get();
            } elseif ($validated['target_tipe'] === 'kamar') {
                $targetedKamars = Kamar::with('kos')
                    ->whereIn('id', $validated['target_ids'] ?? [])
                    ->whereNotNull('wa_group_id')
                    ->where('wa_group_id', '!=', '')
                    ->where('wa_group_id', '!=', '-')
                    ->get();
            } elseif ($validated['target_tipe'] === 'semua') {
                $targetedKamars = Kamar::with('kos')
                    ->whereIn('kos_id', $mitraKosIds)
                    ->whereNotNull('wa_group_id')
                    ->where('wa_group_id', '!=', '')
                    ->where('wa_group_id', '!=', '-')
                    ->get();
            }

            foreach ($targetedKamars as $kamarItem) {
                $kosNama = $kamarItem->kos->nama ?? 'Kos';
                $waItems[] = [
                    'target' => $kamarItem->wa_group_id,
                    'judul' => "PENGUMUMAN KAMAR {$kamarItem->kode_kamar} ({$kosNama}) - " . $judul,
                    'pesan' => $pesan,
                    'user_id' => null,
                ];
            }

            if (!empty($waItems)) {
                $customToken = !empty($user->wa_gateway_token) ? $user->wa_gateway_token : null;
                $customEndpoint = !empty($user->wa_gateway_endpoint) ? $user->wa_gateway_endpoint : null;
                $this->whatsAppService->sendPengumumanWithThrottle($waItems, $customToken, $customEndpoint);
            }
        }
    }
}
