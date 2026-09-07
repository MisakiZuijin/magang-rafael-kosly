<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\Kamar;
use App\Models\Kos;
use App\Models\PenghuniKamar;
use App\Models\User;
use App\Services\KamarService;
use App\Services\KosService;
use App\Services\LogAktivitasService;
use App\Services\PenghuniKamarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MitraKosController extends Controller
{
    public function __construct(
        protected KosService $kosService,
        protected KamarService $kamarService,
        protected PenghuniKamarService $penghuniKamarService,
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function index()
    {
        $this->penghuniKamarService->periksaSemuaNotifikasiSewa();
        $user = Auth::user();
        $kosList = Kos::where('mitra_id', $user->id)
            ->with(['kamar.penghuniKamar.penghuni', 'kamar.penghuniKamar.pembayaran', 'aturanKos'])
            ->withCount(['kamar as total_kamar', 'kamar as kamar_terisi' => function ($q) {
                $q->where('status', 'terisi');
            }])
            ->latest()
            ->get();

        $activePenghunis = User::where('role', 'penghuni')
            ->where('is_active', true)
            ->where('created_by', $user->id)
            ->with(['penghuniKamar' => function ($q) {
                $q->where('status', 'aktif')->with('kamar');
            }])
            ->get();

        return view('mitra.kos.index', compact('kosList', 'activePenghunis'));
    }

    public function storeKos(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'alamat' => 'required|string',
            'link_gmaps' => 'required|string',
            'deskripsi' => 'nullable|string',
            'no_rekening' => 'required|string|max:50',
            'bank' => 'required|string|max:50',
            'nama_pemilik_rekening' => 'required|string|max:100',
        ]);

        $validated['mitra_id'] = $user->id;

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('images/kos', 'public');
        }

        $kos = $this->kosService->create($validated);
        $this->logAktivitasService->log('tambah_kos_mitra_pro', "Mitra Pro {$user->nama} mendaftarkan properti kos baru: {$kos->nama}");

        return redirect()->back()->with('success', 'Kos berhasil didaftarkan.');
    }

    public function updateKos(Request $request, string|int $id)
    {
        $user = Auth::user();
        $kos = Kos::where('mitra_id', $user->id)
            ->where(function ($q) use ($id) {
                $q->where('slug', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0);
            })
            ->firstOrFail();

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'alamat' => 'required|string',
            'link_gmaps' => 'required|string',
            'deskripsi' => 'nullable|string',
            'no_rekening' => 'required|string|max:50',
            'bank' => 'required|string|max:50',
            'nama_pemilik_rekening' => 'required|string|max:100',
        ]);

        if ($request->hasFile('foto')) {
            if ($kos->foto && Storage::disk('public')->exists($kos->foto)) {
                Storage::disk('public')->delete($kos->foto);
            }
            $validated['foto'] = $request->file('foto')->store('images/kos', 'public');
        }

        $this->kosService->update($kos->id, $validated);
        $this->logAktivitasService->log('update_kos_mitra_pro', "Mitra Pro {$user->nama} memperbarui kos: {$validated['nama']}");

        return redirect()->back()->with('success', 'Data kos berhasil diperbarui.');
    }

    public function destroyKos(string|int $id)
    {
        $user = Auth::user();
        $kos = Kos::where('mitra_id', $user->id)
            ->where(function ($q) use ($id) {
                $q->where('slug', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0);
            })
            ->with('kamar.penghuniKamar')
            ->firstOrFail();

        $hasActivePenghuni = PenghuniKamar::whereIn('kamar_id', $kos->kamar->pluck('id'))
            ->where('status', 'aktif')
            ->exists();

        if ($hasActivePenghuni) {
            return redirect()->back()->with('error', "Tidak dapat menghapus Kos '{$kos->nama}' karena masih terdapat penghuni aktif. Harap kosongkan seluruh kamar terlebih dahulu.");
        }

        foreach ($kos->kamar as $k) {
            if (!empty($k->foto) && is_array($k->foto)) {
                foreach ($k->foto as $fotoPath) {
                    if ($fotoPath && Storage::disk('public')->exists($fotoPath)) {
                        Storage::disk('public')->delete($fotoPath);
                    }
                }
            }
        }

        if ($kos->foto && Storage::disk('public')->exists($kos->foto)) {
            Storage::disk('public')->delete($kos->foto);
        }

        $namaKos = $kos->nama;
        $this->kosService->delete($kos->id);
        $this->logAktivitasService->log('hapus_kos_mitra_pro', "Mitra Pro {$user->nama} menghapus kos: {$namaKos}");

        return redirect()->back()->with('success', "Kos '{$namaKos}' beserta seluruh kamarnya berhasil dihapus.");
    }

    public function storeKamar(Request $request)
    {
        $user = Auth::user();

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
            'kos_id' => 'required|exists:kos,id',
            'kode_kamar' => 'required|string|max:20',
            'tipe' => 'required|in:standar,berbagi',
            'detail' => 'nullable|string',
            'foto.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'harga_per_hari' => 'nullable|numeric',
            'harga_per_minggu' => 'nullable|numeric',
            'harga_per_bulan' => 'required|numeric|min:1',
            'kapasitas' => 'required|integer|min:1',
            'wa_group_id' => 'required|string|max:100',
            'link_grup_wa' => 'required|url|max:255',
        ]);

        // Verifikasi kepemilikan kos
        $kos = Kos::where('id', $validated['kos_id'])->where('mitra_id', $user->id)->firstOrFail();

        $fotoPaths = [];
        if ($request->hasFile('foto')) {
            foreach ($request->file('foto') as $file) {
                if ($file && $file->isValid()) {
                    $fotoPaths[] = $file->store('kamar', 'public');
                }
            }
        }
        $validated['foto'] = $fotoPaths;
        $validated['status'] = 'kosong';
        $validated['kapasitas'] = $validated['tipe'] === 'berbagi' ? 2 : 1;
        $kamar = $this->kamarService->create($validated);
        $this->logAktivitasService->log('tambah_kamar_mitra_pro', "Mitra Pro {$user->nama} menambahkan Kamar {$kamar->kode_kamar} di {$kos->nama}");

        return redirect()->back()->with('success', 'Kamar berhasil didaftarkan.');
    }

    public function showKamar(string|int $id)
    {
        $this->penghuniKamarService->periksaSemuaNotifikasiSewa();
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');

        $kamar = Kamar::with(['kos.mitra', 'penghuniKamar.penghuni', 'penghuniKamar.pembayaran'])
            ->whereIn('kos_id', $mitraKosIds)
            ->where(function ($q) use ($id) {
                $q->where('kode_kamar', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0);
            })
            ->firstOrFail();

        return view('mitra.kos.show_kamar', compact('kamar'));
    }

    public function updateKamar(Request $request, string|int $id)
    {
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');

        $kamar = Kamar::whereIn('kos_id', $mitraKosIds)
            ->where(function ($q) use ($id) {
                $q->where('kode_kamar', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0);
            })
            ->firstOrFail();

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
            'harga_per_bulan' => 'required|numeric|min:1',
            'kapasitas' => 'required|integer|min:1',
            'wa_group_id' => 'required|string|max:100',
            'link_grup_wa' => 'required|url|max:255',
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
        $activeCount = PenghuniKamar::where('kamar_id', $kamar->id)->where('status', 'aktif')->count();
        if ($validated['tipe'] === 'berbagi') {
            $validated['kapasitas'] = $activeCount >= 3 ? 3 : 2;
        } else {
            $validated['kapasitas'] = 1;
        }

        $this->kamarService->update($kamar->id, $validated);
        $this->logAktivitasService->log('update_kamar_mitra_pro', "Mitra Pro {$user->nama} memperbarui data Kamar {$validated['kode_kamar']}");

        return redirect()->back()->with('success', 'Data kamar berhasil diperbarui.');
    }

    public function destroyKamar(string|int $id)
    {
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');

        $kamar = Kamar::whereIn('kos_id', $mitraKosIds)
            ->where(function ($q) use ($id) {
                $q->where('kode_kamar', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0);
            })
            ->firstOrFail();

        $hasActivePenghuni = PenghuniKamar::where('kamar_id', $kamar->id)
            ->where('status', 'aktif')
            ->exists();

        if ($hasActivePenghuni) {
            return redirect()->back()->with('error', "Tidak dapat menghapus Kamar {$kamar->kode_kamar} karena masih terdapat penghuni aktif. Harap kosongkan kamar terlebih dahulu.");
        }

        if (!empty($kamar->foto) && is_array($kamar->foto)) {
            foreach ($kamar->foto as $fotoPath) {
                if ($fotoPath && Storage::disk('public')->exists($fotoPath)) {
                    Storage::disk('public')->delete($fotoPath);
                }
            }
        }

        $kodeKamar = $kamar->kode_kamar;
        $this->kamarService->delete($kamar->id);
        $this->logAktivitasService->log('hapus_kamar_mitra_pro', "Mitra Pro {$user->nama} menghapus Kamar {$kodeKamar}");

        if (request()->routeIs('*.showKamar') || url()->previous() == route('mitra.kamar.show', $id) || url()->previous() == route('mitra.kamar.show', $kamar->kode_kamar)) {
            return redirect()->route('mitra.kos.index')->with('success', "Kamar {$kodeKamar} berhasil dihapus.");
        }

        return redirect()->back()->with('success', "Kamar {$kodeKamar} berhasil dihapus.");
    }

    public function daftarPenghuni(Request $request)
    {
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');

        $kamar = Kamar::whereIn('kos_id', $mitraKosIds)
            ->where('id', $request->input('kamar_id'))
            ->first();

        if (!$kamar) {
            return redirect()->back()->with('error', 'Kamar tidak ditemukan atau bukan milik Anda.');
        }

        $isPenghuniAllowed = fn($penghuniId) => User::where('id', $penghuniId)
            ->where('role', 'penghuni')
            ->where('created_by', $user->id)
            ->exists();

        if ($request->filled('penghuni_id') && !$isPenghuniAllowed($request->input('penghuni_id'))) {
            return redirect()->back()->with('error', 'Penghuni ke-1 yang Anda pilih tidak terdaftar dalam daftar penghuni Anda.');
        }
        if ($request->filled('penghuni_id_2') && !$isPenghuniAllowed($request->input('penghuni_id_2'))) {
            return redirect()->back()->with('error', 'Penghuni ke-2 yang Anda pilih tidak terdaftar dalam daftar penghuni Anda.');
        }
        if ($request->filled('penghuni_id_3') && !$isPenghuniAllowed($request->input('penghuni_id_3'))) {
            return redirect()->back()->with('error', 'Penghuni ke-3 yang Anda pilih tidak terdaftar dalam daftar penghuni Anda.');
        }

        // Cek penghuni 1
        $penghuni1Active = PenghuniKamar::where('penghuni_id', $request->input('penghuni_id'))
            ->where('status', 'aktif')
            ->exists();
        if ($penghuni1Active) {
            return redirect()->back()->with('error', 'Penghuni ke-1 yang Anda pilih sudah terdaftar dan sedang menempati kamar lain.');
        }

        // Cek penghuni 2
        if ($request->filled('penghuni_id_2')) {
            $penghuni2Active = PenghuniKamar::where('penghuni_id', $request->input('penghuni_id_2'))
                ->where('status', 'aktif')
                ->exists();
            if ($penghuni2Active) {
                return redirect()->back()->with('error', 'Penghuni ke-2 yang Anda pilih sudah terdaftar dan sedang menempati kamar lain.');
            }
        }

        // Cek penghuni 3
        if ($request->filled('penghuni_id_3')) {
            $penghuni3Active = PenghuniKamar::where('penghuni_id', $request->input('penghuni_id_3'))
                ->where('status', 'aktif')
                ->exists();
            if ($penghuni3Active) {
                return redirect()->back()->with('error', 'Penghuni ke-3 yang Anda pilih sudah terdaftar dan sedang menempati kamar lain.');
            }
        }

        if ($kamar->tipe === 'berbagi' || $kamar->kapasitas >= 2) {
            $validated = $request->validate([
                'kamar_id' => 'required|exists:kamar,id',
                'penghuni_id' => 'required|exists:users,id',
                'penghuni_id_2' => 'required|exists:users,id|different:penghuni_id',
                'penghuni_id_3' => 'nullable|exists:users,id|different:penghuni_id|different:penghuni_id_2',
                'tanggal_masuk' => 'required|date',
                'tanggal_keluar' => 'nullable|date|after:tanggal_masuk',
                'durasi' => 'required|in:harian,mingguan,bulanan',
            ], [
                'penghuni_id_2.required' => 'Kamar tipe berbagi wajib mendaftarkan minimal 2 orang penghuni.',
                'penghuni_id_2.different' => 'Penghuni ke-2 harus orang yang berbeda dari Penghuni ke-1.',
                'penghuni_id_3.different' => 'Penghuni ke-3 harus orang yang berbeda dari Penghuni ke-1 dan ke-2.',
            ]);

            $tglMasukObj = \Carbon\Carbon::parse($validated['tanggal_masuk'])->setTime(0, 0, 0);
            if (empty($validated['tanggal_keluar'])) {
                if ($validated['durasi'] === 'bulanan') {
                    $tglKeluarObj = $tglMasukObj->copy()->addDays(29)->setTime(14, 0, 0);
                } elseif ($validated['durasi'] === 'mingguan') {
                    $tglKeluarObj = $tglMasukObj->copy()->addDays(6)->setTime(14, 0, 0);
                } else {
                    $tglKeluarObj = $tglMasukObj->copy()->addDay()->setTime(14, 0, 0);
                }
            } else {
                $tglKeluarObj = \Carbon\Carbon::parse($validated['tanggal_keluar'])->setTime(14, 0, 0);
            }

            $validated['tanggal_masuk'] = $tglMasukObj->toDateTimeString();
            $validated['tanggal_keluar'] = $tglKeluarObj->toDateTimeString();

            $this->penghuniKamarService->create([
                'kamar_id' => $validated['kamar_id'],
                'penghuni_id' => $validated['penghuni_id'],
                'tanggal_masuk' => $validated['tanggal_masuk'],
                'tanggal_keluar' => $validated['tanggal_keluar'],
                'durasi' => $validated['durasi'],
                'status' => 'aktif',
            ]);

            $this->penghuniKamarService->create([
                'kamar_id' => $validated['kamar_id'],
                'penghuni_id' => $validated['penghuni_id_2'],
                'tanggal_masuk' => $validated['tanggal_masuk'],
                'tanggal_keluar' => $validated['tanggal_keluar'],
                'durasi' => $validated['durasi'],
                'status' => 'aktif',
            ]);

            $names = [];
            $u1 = User::find($validated['penghuni_id']);
            $u2 = User::find($validated['penghuni_id_2']);
            $names[] = $u1->nama;
            $names[] = $u2->nama;

            if (!empty($validated['penghuni_id_3'])) {
                $this->penghuniKamarService->create([
                    'kamar_id' => $validated['kamar_id'],
                    'penghuni_id' => $validated['penghuni_id_3'],
                    'tanggal_masuk' => $validated['tanggal_masuk'],
                    'tanggal_keluar' => $validated['tanggal_keluar'],
                    'durasi' => $validated['durasi'],
                    'status' => 'aktif',
                ]);
                $u3 = User::find($validated['penghuni_id_3']);
                $names[] = $u3->nama;
            }

            $joinedNames = implode(', ', $names);
            $countPenghuni = count($names);
            $this->logAktivitasService->log('daftar_penghuni_mitra_pro', "Mitra Pro {$user->nama} mendaftarkan {$countPenghuni} penghuni ({$joinedNames}) ke Kamar {$kamar->kode_kamar}");

            return redirect()->back()->with('success', "Berhasil mendaftarkan {$countPenghuni} penghuni ke kamar tipe berbagi.");
        } else {
            $validated = $request->validate([
                'kamar_id' => 'required|exists:kamar,id',
                'penghuni_id' => 'required|exists:users,id',
                'tanggal_masuk' => 'required|date',
                'tanggal_keluar' => 'nullable|date|after:tanggal_masuk',
                'durasi' => 'required|in:harian,mingguan,bulanan',
            ]);

            $tglMasukObj = \Carbon\Carbon::parse($validated['tanggal_masuk'])->setTime(0, 0, 0);
            if (empty($validated['tanggal_keluar'])) {
                if ($validated['durasi'] === 'bulanan') {
                    $tglKeluarObj = $tglMasukObj->copy()->addDays(29)->setTime(14, 0, 0);
                } elseif ($validated['durasi'] === 'mingguan') {
                    $tglKeluarObj = $tglMasukObj->copy()->addDays(6)->setTime(14, 0, 0);
                } else {
                    $tglKeluarObj = $tglMasukObj->copy()->addDay()->setTime(14, 0, 0);
                }
            } else {
                $tglKeluarObj = \Carbon\Carbon::parse($validated['tanggal_keluar'])->setTime(14, 0, 0);
            }

            $validated['tanggal_masuk'] = $tglMasukObj->toDateTimeString();
            $validated['tanggal_keluar'] = $tglKeluarObj->toDateTimeString();
            $validated['status'] = 'aktif';

            $this->penghuniKamarService->create($validated);
            $u = User::find($validated['penghuni_id']);
            $this->logAktivitasService->log('daftar_penghuni_mitra_pro', "Mitra Pro {$user->nama} mendaftarkan penghuni {$u->nama} ke Kamar {$kamar->kode_kamar}");

            return redirect()->back()->with('success', 'Penghuni berhasil didaftarkan ke kamar.');
        }
    }

    public function checkoutPenghuni(int $id)
    {
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');

        $pk = PenghuniKamar::whereHas('kamar', function ($q) use ($mitraKosIds) {
            $q->whereIn('kos_id', $mitraKosIds);
        })->with('penghuni', 'kamar')->findOrFail($id);

        $penghuniNama = $pk->penghuni->nama ?? 'Penghuni';
        $kodeKamar = $pk->kamar->kode_kamar ?? '-';

        $this->penghuniKamarService->checkout($id);
        $this->logAktivitasService->log('checkout_penghuni_mitra_pro', "Mitra Pro {$user->nama} melakukan checkout untuk {$penghuniNama} dari Kamar {$kodeKamar}");

        return redirect()->back()->with('success', 'Penghuni berhasil di-checkout.');
    }

    public function kosongkanKamar(string|int $kamarId)
    {
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');

        $kamar = Kamar::whereIn('kos_id', $mitraKosIds)
            ->where(function ($q) use ($kamarId) {
                $q->where('kode_kamar', $kamarId)->orWhere('id', is_numeric($kamarId) ? (int)$kamarId : 0);
            })
            ->firstOrFail();

        $kodeKamar = $kamar->kode_kamar ?? '-';

        $penghuniKamarList = PenghuniKamar::where('kamar_id', $kamar->id)
            ->where('status', 'aktif')
            ->get();

        foreach ($penghuniKamarList as $pk) {
            $this->penghuniKamarService->checkout($pk->id);
        }

        $this->kamarService->updateStatus($kamar->id, 'kosong');
        $this->logAktivitasService->log('kosongkan_kamar_mitra_pro', "Mitra Pro {$user->nama} mengosongkan seluruh penghuni pada Kamar {$kodeKamar}");

        return redirect()->back()->with('success', 'Kamar berhasil dikosongkan.');
    }

    public function deleteFotoKamar(Request $request, string|int $id)
    {
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');

        $kamar = Kamar::whereIn('kos_id', $mitraKosIds)
            ->where(function ($q) use ($id) {
                $q->where('kode_kamar', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0);
            })
            ->firstOrFail();

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
