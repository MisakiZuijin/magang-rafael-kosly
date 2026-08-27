<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kamar;
use App\Models\PenghuniKamar;
use App\Models\User;
use App\Services\KamarService;
use App\Services\KosService;
use App\Services\LogAktivitasService;
use App\Services\PenghuniKamarService;
use App\Services\UserService;
use Illuminate\Http\Request;

class AdminKosController extends Controller
{
    public function __construct(
        protected KosService $kosService,
        protected KamarService $kamarService,
        protected UserService $userService,
        protected PenghuniKamarService $penghuniKamarService,
        protected LogAktivitasService $logAktivitasService
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
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'alamat' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'deskripsi' => 'nullable|string',
            'no_rekening' => 'nullable|string|max:50',
            'bank' => 'nullable|string|max:50',
            'nama_pemilik_rekening' => 'nullable|string|max:100',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('images/kos', 'public');
        }

        $kos = $this->kosService->create($validated);
        $this->logAktivitasService->log('tambah_kos', "Mendaftarkan properti kos baru: {$kos->nama}");

        return redirect()->back()->with('success', 'Kos berhasil didaftarkan.');
    }

    public function storeKamar(Request $request)
    {
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
            'harga_per_bulan' => 'required|numeric',
            'kapasitas' => 'required|integer|min:1',
            'wa_group_id' => 'nullable|string|max:100',
            'link_grup_wa' => 'nullable|url|max:255',
        ]);

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
        $kosNama = $kamar->kos->nama ?? 'Kos';
        $this->logAktivitasService->log('tambah_kamar', "Menambahkan Kamar {$kamar->kode_kamar} di {$kosNama}");

        return redirect()->back()->with('success', 'Kamar berhasil didaftarkan.');
    }

    public function daftarPenghuni(Request $request)
    {
        $kamar = $this->kamarService->getById($request->input('kamar_id'));
        if (!$kamar) {
            return redirect()->back()->with('error', 'Kamar tidak ditemukan.');
        }

        // Check if Penghuni 1 is already assigned to an active room
        $penghuni1Active = PenghuniKamar::where('penghuni_id', $request->input('penghuni_id'))
            ->where('status', 'aktif')
            ->exists();
        if ($penghuni1Active) {
            return redirect()->back()->with('error', 'Penghuni ke-1 yang Anda pilih sudah terdaftar dan sedang menempati kamar lain.');
        }

        // Check if Penghuni 2 is already assigned to an active room
        if ($request->filled('penghuni_id_2')) {
            $penghuni2Active = PenghuniKamar::where('penghuni_id', $request->input('penghuni_id_2'))
                ->where('status', 'aktif')
                ->exists();
            if ($penghuni2Active) {
                return redirect()->back()->with('error', 'Penghuni ke-2 yang Anda pilih sudah terdaftar dan sedang menempati kamar lain.');
            }
        }

        // Check if Penghuni 3 is already assigned to an active room
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

            if (empty($validated['tanggal_keluar'])) {
                if ($validated['durasi'] === 'bulanan') {
                    $validated['tanggal_keluar'] = \Carbon\Carbon::parse($validated['tanggal_masuk'])->addDays(30)->toDateString();
                } elseif ($validated['durasi'] === 'mingguan') {
                    $validated['tanggal_keluar'] = \Carbon\Carbon::parse($validated['tanggal_masuk'])->addDays(7)->toDateString();
                } else {
                    $validated['tanggal_keluar'] = \Carbon\Carbon::parse($validated['tanggal_masuk'])->addDay()->toDateString();
                }
            }

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

            $names = [];
            $u1 = User::find($validated['penghuni_id']);
            $u2 = User::find($validated['penghuni_id_2']);
            $names[] = $u1->nama;
            $names[] = $u2->nama;

            // Register Penghuni 3 jika diisi
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
            $this->logAktivitasService->log('daftar_penghuni', "Mendaftarkan {$countPenghuni} penghuni ({$joinedNames}) ke Kamar {$kamar->kode_kamar}");

            return redirect()->back()->with('success', "Berhasil mendaftarkan {$countPenghuni} penghuni ke kamar tipe berbagi.");
        } else {
            $validated = $request->validate([
                'kamar_id' => 'required|exists:kamar,id',
                'penghuni_id' => 'required|exists:users,id',
                'tanggal_masuk' => 'required|date',
                'tanggal_keluar' => 'nullable|date|after:tanggal_masuk',
                'durasi' => 'required|in:harian,mingguan,bulanan',
            ]);

            if (empty($validated['tanggal_keluar'])) {
                if ($validated['durasi'] === 'bulanan') {
                    $validated['tanggal_keluar'] = \Carbon\Carbon::parse($validated['tanggal_masuk'])->addDays(30)->toDateString();
                } elseif ($validated['durasi'] === 'mingguan') {
                    $validated['tanggal_keluar'] = \Carbon\Carbon::parse($validated['tanggal_masuk'])->addDays(7)->toDateString();
                } else {
                    $validated['tanggal_keluar'] = \Carbon\Carbon::parse($validated['tanggal_masuk'])->addDay()->toDateString();
                }
            }

            $validated['status'] = 'aktif';
            $this->penghuniKamarService->create($validated);

            $u = User::find($validated['penghuni_id']);
            $this->logAktivitasService->log('daftar_penghuni', "Mendaftarkan penghuni {$u->nama} ke Kamar {$kamar->kode_kamar}");

            return redirect()->back()->with('success', 'Penghuni berhasil didaftarkan ke kamar.');
        }
    }

    public function checkoutPenghuni(int $id)
    {
        $pk = PenghuniKamar::with('penghuni', 'kamar')->find($id);
        $penghuniNama = $pk->penghuni->nama ?? 'Penghuni';
        $kodeKamar = $pk->kamar->kode_kamar ?? '-';

        $this->penghuniKamarService->checkout($id);
        $this->logAktivitasService->log('checkout_penghuni', "Melakukan checkout untuk penghuni {$penghuniNama} dari Kamar {$kodeKamar}");

        return redirect()->back()->with('success', 'Penghuni berhasil di-checkout.');
    }

    public function kosongkanKamar(int $kamarId)
    {
        $kamar = Kamar::find($kamarId);
        $kodeKamar = $kamar->kode_kamar ?? '-';

        $penghuniKamarList = PenghuniKamar::where('kamar_id', $kamarId)
            ->where('status', 'aktif')
            ->get();

        foreach ($penghuniKamarList as $pk) {
            $this->penghuniKamarService->checkout($pk->id);
        }

        $this->kamarService->updateStatus($kamarId, 'kosong');
        $this->logAktivitasService->log('kosongkan_kamar', "Mengosongkan seluruh penghuni pada Kamar {$kodeKamar}");

        return redirect()->back()->with('success', 'Kamar berhasil dikosongkan.');
    }

    public function updateKos(Request $request, int $id)
    {
        $kos = \App\Models\Kos::findOrFail($id);

        $validated = $request->validate([
            'mitra_id' => 'required|exists:users,id',
            'nama' => 'required|string|max:100',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'alamat' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'deskripsi' => 'nullable|string',
            'no_rekening' => 'nullable|string|max:50',
            'bank' => 'nullable|string|max:50',
            'nama_pemilik_rekening' => 'nullable|string|max:100',
        ]);

        if ($request->hasFile('foto')) {
            if ($kos->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($kos->foto)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($kos->foto);
            }
            $validated['foto'] = $request->file('foto')->store('images/kos', 'public');
        }

        $this->kosService->update($id, $validated);
        $this->logAktivitasService->log('update_kos', "Memperbarui data kos: {$validated['nama']}");

        return redirect()->back()->with('success', 'Data kos berhasil diperbarui.');
    }

    public function updateKamar(Request $request, int $id)
    {
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
            'harga_per_bulan' => 'required|numeric',
            'kapasitas' => 'required|integer|min:1',
            'wa_group_id' => 'nullable|string|max:100',
            'link_grup_wa' => 'nullable|url|max:255',
        ]);

        $existingKamar = $this->kamarService->getById($id);
        $fotoPaths = $existingKamar->foto ?? [];

        if ($request->hasFile('foto')) {
            foreach ($request->file('foto') as $file) {
                if ($file && $file->isValid()) {
                    $fotoPaths[] = $file->store('kamar', 'public');
                }
            }
        }

        $validated['foto'] = array_values($fotoPaths);
        $activeCount = \App\Models\PenghuniKamar::where('kamar_id', $id)->where('status', 'aktif')->count();
        if ($validated['tipe'] === 'berbagi') {
            $validated['kapasitas'] = $activeCount >= 3 ? 3 : 2;
        } else {
            $validated['kapasitas'] = 1;
        }

        $this->kamarService->update($id, $validated);
        $this->logAktivitasService->log('update_kamar', "Memperbarui data Kamar {$validated['kode_kamar']}");

        return redirect()->back()->with('success', 'Data kamar berhasil diperbarui.');
    }

    public function showKamar(int $id)
    {
        $kamar = \App\Models\Kamar::with(['kos.mitra', 'penghuniKamar.penghuni'])->findOrFail($id);

        return view('admin.kamar.show', [
            'kamar' => $kamar,
            'isSuperAdmin' => auth()->user()->role === 'super_admin'
        ]);
    }

    public function deleteFotoKamar(Request $request, int $id)
    {
        $kamar = \App\Models\Kamar::findOrFail($id);
        $index = (int)$request->input('index');
        $fotos = $kamar->foto ?? [];

        if (isset($fotos[$index])) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($fotos[$index]);
            array_splice($fotos, $index, 1);
            $kamar->update(['foto' => array_values($fotos)]);
        }

        return redirect()->back()->with('success', 'Foto kamar berhasil dihapus.');
    }
}
