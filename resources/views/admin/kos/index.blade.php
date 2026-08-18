@extends('layouts.app')

@section('title', 'Pendaftaran Kos & Kamar')

@section('content')
<div class="space-y-4" x-data="{ 
    modalKos: false, 
    modalKamar: false, 
    modalPenghuni: false,
    modalEditKos: false,
    modalEditKamar: false,
    filterKosId: 'all',
    filterTipeKamar: 'all',
    editKosData: { id: '', mitra_id: '', nama: '', alamat: '', latitude: '', longitude: '', bank: '', no_rekening: '', nama_pemilik_rekening: '' },
    editKosUrl: '',
    editKamarData: { id: '', kos_id: '', kode_kamar: '', tipe: 'standar', harga_per_bulan: '', harga_per_hari: '', kapasitas: 1 },
    editKamarUrl: '',
    openEditKosModal(kos) {
        this.editKosData = {
            id: kos.id,
            mitra_id: kos.mitra_id,
            nama: kos.nama || '',
            alamat: kos.alamat || '',
            latitude: kos.latitude || '',
            longitude: kos.longitude || '',
            bank: kos.bank || '',
            no_rekening: kos.no_rekening || '',
            nama_pemilik_rekening: kos.nama_pemilik_rekening || ''
        };
        const prefix = '{{ request()->is('superadmin*') ? 'superadmin' : 'admin' }}';
        this.editKosUrl = '/' + prefix + '/kos/' + kos.id;
        this.modalEditKos = true;
    },
    openEditKamarModal(kamar) {
        this.editKamarData = {
            id: kamar.id,
            kos_id: kamar.kos_id,
            kode_kamar: kamar.kode_kamar || '',
            tipe: kamar.tipe || 'standar',
            harga_per_bulan: kamar.harga_per_bulan || '',
            harga_per_hari: kamar.harga_per_hari || '',
            kapasitas: kamar.kapasitas || 1
        };
        const prefix = '{{ request()->is('superadmin*') ? 'superadmin' : 'admin' }}';
        this.editKamarUrl = '/' + prefix + '/kamar/' + kamar.id;
        this.modalEditKamar = true;
    },
    selectedKosIdForKamar: '',
    selectedKamarIdForPenghuni: '',
    selectedKamarTipe: 'standar',
    updateKamarTipe() {
        const select = document.getElementById('select-kamar-penghuni');
        if (select && select.selectedOptions.length > 0) {
            const opt = select.selectedOptions[0];
            this.selectedKamarTipe = opt.getAttribute('data-tipe') || 'standar';
        }
    }
}">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Pendaftaran Kos & Kamar</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kelola kos, kamar, dan penempatan anak kos</p>
        </div>
    </div>

    {{-- Action Buttons Bar --}}
    <div class="grid grid-cols-3 gap-2">
        <button @click="modalKos = true" class="flex flex-col items-center justify-center p-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl shadow-sm active:scale-95 transition-all text-center">
            <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="text-xs font-bold leading-tight">Kos Baru</span>
        </button>

        <button @click="modalKamar = true" class="flex flex-col items-center justify-center p-3 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl shadow-sm active:scale-95 transition-all text-center">
            <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span class="text-xs font-bold leading-tight">Kamar Baru</span>
        </button>

        <button @click="modalPenghuni = true" class="flex flex-col items-center justify-center p-3 bg-purple-600 hover:bg-purple-700 text-white rounded-2xl shadow-sm active:scale-95 transition-all text-center">
            <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            <span class="text-xs font-bold leading-tight">Penghuni</span>
        </button>
    </div>

    {{-- Filter Kos & Tipe Kamar Selector Bar --}}
    @if(!$kosList->isEmpty())
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-3 border border-gray-200 dark:border-gray-800 shadow-sm space-y-2">
        <div class="flex items-center justify-between">
            <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                Filter Tampilan Kos &amp; Kamar:
            </label>
            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 font-mono"
                x-text="(filterKosId === 'all' ? 'Semua Kos' : 'Kos Terpilih') + ' · ' + (filterTipeKamar === 'all' ? 'Semua Tipe' : (filterTipeKamar === 'standar' ? 'Standar' : 'Berbagi'))"></span>
        </div>

        <div class="grid grid-cols-2 gap-2">
            {{-- Filter Kos --}}
            <div>
                <label class="block text-[10px] font-semibold text-gray-500 dark:text-gray-400 mb-0.5">Pilih Kos:</label>
                <select x-model="filterKosId" class="w-full py-1.5 px-2 bg-gray-50 dark:bg-gray-800 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-xs font-bold text-gray-900 dark:text-white focus:ring-emerald-500">
                    <option value="all">-- Semua Kos ({{ $kosList->count() }}) --</option>
                    @foreach($kosList as $kItem)
                    <option value="{{ $kItem->id }}">{{ $kItem->nama }} ({{ $kItem->kamar->count() }} Kamar)</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Tipe Kamar --}}
            <div>
                <label class="block text-[10px] font-semibold text-gray-500 dark:text-gray-400 mb-0.5">Pilih Tipe Kamar:</label>
                <select x-model="filterTipeKamar" class="w-full py-1.5 px-2 bg-gray-50 dark:bg-gray-800 border border-purple-200 dark:border-purple-800/60 rounded-xl text-xs font-bold text-gray-900 dark:text-white focus:ring-purple-500">
                    <option value="all">-- Semua Tipe --</option>
                    <option value="standar">Standar (1 Orang)</option>
                    <option value="berbagi">Berbagi (2 Orang)</option>
                </select>
            </div>
        </div>
    </div>
    @endif

    {{-- Daftar Kos List --}}
    @if($kosList->isEmpty())
    <x-empty-state message="Belum ada kos yang terdaftar. Klik + Kos Baru untuk memulainya." />
    @else
    <div class="space-y-4">
        @foreach($kosList as $kos)
        <div x-show="(filterKosId === 'all' || filterKosId == '{{ $kos->id }}') &amp;&amp; (filterTipeKamar === 'all' || {{ json_encode($kos->kamar->pluck('tipe')->toArray()) }}.includes(filterTipeKamar))"
            x-transition
            class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
                <div class="flex justify-between items-start mb-1">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                            Mitra: {{ $kos->mitra->nama ?? '-' }}
                        </span>
                        <h3 class="font-bold text-base text-gray-900 dark:text-white leading-snug">{{ $kos->nama }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $kos->alamat ?? 'Alamat tidak diisi' }}</p>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="px-2 py-1 text-[10px] font-bold rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                            {{ $kos->kamar->count() }} Kamar
                        </span>
                        <button type="button"
                            @click="openEditKosModal(@js($kos))"
                            class="px-2 py-1 text-[10px] font-bold text-amber-700 dark:text-amber-300 bg-amber-100 hover:bg-amber-200 dark:bg-amber-900/40 dark:hover:bg-amber-900/60 rounded-lg transition-all flex items-center gap-0.5">
                            <span>✏️ Edit Kos</span>
                        </button>
                    </div>
                </div>

                @if($kos->bank && $kos->no_rekening)
                <p class="text-[11px] font-mono text-gray-400 mt-2">
                    Rekening: {{ $kos->bank }} - {{ $kos->no_rekening }} (a.n {{ $kos->nama_pemilik_rekening ?? '-' }})
                </p>
                @endif
            </div>

            {{-- Rooms List in this Kos --}}
            <div class="p-3">
                @if($kos->kamar->isEmpty())
                <div class="p-3 text-center">
                    <p class="text-xs text-gray-400">Belum ada kamar di kos ini.</p>
                    <button @click="selectedKosIdForKamar = '{{ $kos->id }}'; modalKamar = true" class="text-xs font-bold text-emerald-600 mt-1 inline-block">
                        + Tambah Kamar
                    </button>
                </div>
                @else
                <div class="grid grid-cols-1 sm:grid-cols-1 gap-2.5">
                    @foreach($kos->kamar as $kamar)
                    @php
                    $activePenghunis = $kamar->penghuniKamar ? $kamar->penghuniKamar->where('status', 'aktif') : collect();
                    $isTerisi = $kamar->status === 'terisi' || $activePenghunis->isNotEmpty();
                    @endphp

                    <div x-show="filterTipeKamar === 'all' || filterTipeKamar === '{{ $kamar->tipe }}'"
                        x-transition
                        class="p-3 rounded-xl border {{ $isTerisi ? 'bg-emerald-50/40 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-900/50' : 'bg-gray-50/60 dark:bg-gray-800/40 border-gray-200 dark:border-gray-800' }} space-y-2">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-xs font-mono text-gray-900 dark:text-white">
                                    Kamar {{ $kamar->kode_kamar }}
                                </span>
                                <span class="px-1.5 py-0.5 text-[10px] uppercase font-bold rounded-md {{ $kamar->tipe === 'berbagi' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' }}">
                                    {{ ucfirst($kamar->tipe) }}
                                </span>
                                <button type="button"
                                    @click="openEditKamarModal(@js($kamar))"
                                    class="px-1.5 py-0.5 text-[10px] font-bold text-blue-700 dark:text-blue-300 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/40 dark:hover:bg-blue-900/60 rounded-md transition-all">
                                    ✏️ Edit
                                </button>
                            </div>

                            <x-badge type="{{ $isTerisi ? 'success' : 'warning' }}">
                                {{ $isTerisi ? 'Terisi' : 'Kosong' }}
                            </x-badge>
                        </div>

                        <p class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                            Rp {{ number_format($kamar->harga_per_bulan, 0, ',', '.') }} / bln
                        </p>

                        {{-- Penghuni --}}
                        @if($activePenghunis->isNotEmpty())
                        <div class="mt-2 pt-2 border-t border-emerald-200/60 dark:border-emerald-900/40 space-y-1.5">
                            <p class="text-[10px] uppercase font-bold text-emerald-700 dark:text-emerald-400 tracking-wider">
                                Penghuni Aktif ({{ $activePenghunis->count() }}/{{ $kamar->kapasitas }})
                            </p>

                            @foreach($activePenghunis as $pk)
                            <div class="flex items-center justify-between text-xs bg-white/60 dark:bg-gray-900/60 p-1.5 rounded-lg border border-emerald-100 dark:border-emerald-900/30">
                                <span class="font-bold text-gray-900 dark:text-white truncate">
                                    {{ $pk->penghuni->nama ?? '-' }}
                                </span>
                                <span class="text-[10px] text-gray-500 font-mono">
                                    {{ $pk->durasi }}
                                </span>
                            </div>
                            @endforeach

                            {{-- Tombol Kosongkan Kamar --}}
                            <div class="pt-1 flex justify-end">
                                <form action="{{ route('admin.kamar.kosongkan', $kamar->id) }}" method="POST" onsubmit="return confirm('Kosongkan Kamar {{ $kamar->kode_kamar }} dan selesaikan sewa penghuni?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 text-[11px] font-bold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-300 rounded-lg border border-red-200 dark:border-red-900/50 flex items-center gap-1 active:scale-95 transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        <span>Kosongkan Kamar</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                        @else
                        <div class="mt-2 pt-2 border-t border-gray-200/60 dark:border-gray-700 flex justify-between items-center text-[10px]">
                            <span class="text-amber-600 font-medium">Belum ada penghuni</span>
                            <button @click="
                                        selectedKamarIdForPenghuni = '{{ $kamar->id }}'; 
                                        selectedKamarTipe = '{{ $kamar->tipe }}'; 
                                        modalPenghuni = true;
                                    "
                                class="px-2 py-1 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/40 dark:hover:bg-purple-900/60 text-purple-700 dark:text-purple-300 font-bold rounded-lg transition-all">
                                + Daftarkan Penghuni
                            </button>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Modal Pendaftaran Kos Baru --}}
    <x-modal show="modalKos" title="Daftarkan Kos Baru">
        <form action="{{ route('admin.kos.store') }}" method="POST" class="space-y-3">
            @csrf

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pemilik / Mitra Kos</label>
                <select name="mitra_id" required class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                    <option value="">-- Pilih Mitra --</option>
                    @foreach($mitras as $mitra)
                    <option value="{{ $mitra->id }}">{{ $mitra->nama }} ({{ $mitra->email }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Kos</label>
                <input type="text" name="nama" required placeholder="Contoh: Kos Mawar Asri" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Alamat Lengkap</label>
                <textarea name="alamat" rows="2" placeholder="Alamat jalan, nomor, kecamatan" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Latitude</label>
                    <input type="text" name="latitude" placeholder="-7.250445" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Longitude</label>
                    <input type="text" name="longitude" placeholder="112.768845" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Bank Pembayaran</label>
                    <input type="text" name="bank" placeholder="BCA / BRI / Mandiri" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">No. Rekening</label>
                    <input type="text" name="no_rekening" placeholder="1234567890" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Pemilik Rekening</label>
                <input type="text" name="nama_pemilik_rekening" placeholder="Nama Sesuai Rekening" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <x-btn type="button" variant="secondary" size="sm" @click="modalKos = false">Batal</x-btn>
                <x-btn type="submit" variant="primary" size="sm">Simpan Kos</x-btn>
            </div>
        </form>
    </x-modal>

    {{-- Modal Edit Kos --}}
    <x-modal show="modalEditKos" title="Edit Data Kos">
        <form :action="editKosUrl" method="POST" class="space-y-3">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pemilik / Mitra Kos</label>
                <select name="mitra_id" x-model="editKosData.mitra_id" required class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                    <option value="">-- Pilih Mitra --</option>
                    @foreach($mitras as $mitra)
                    <option value="{{ $mitra->id }}">{{ $mitra->nama }} ({{ $mitra->email }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Kos</label>
                <input type="text" name="nama" x-model="editKosData.nama" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Alamat Lengkap</label>
                <textarea name="alamat" x-model="editKosData.alamat" rows="2" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Latitude</label>
                    <input type="text" name="latitude" x-model="editKosData.latitude" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Longitude</label>
                    <input type="text" name="longitude" x-model="editKosData.longitude" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Bank Pembayaran</label>
                    <input type="text" name="bank" x-model="editKosData.bank" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">No. Rekening</label>
                    <input type="text" name="no_rekening" x-model="editKosData.no_rekening" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Pemilik Rekening</label>
                <input type="text" name="nama_pemilik_rekening" x-model="editKosData.nama_pemilik_rekening" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <x-btn type="button" variant="secondary" size="sm" @click="modalEditKos = false">Batal</x-btn>
                <x-btn type="submit" variant="primary" size="sm">Update Kos</x-btn>
            </div>
        </form>
    </x-modal>

    {{-- Modal Pendaftaran Kamar Baru --}}
    <x-modal show="modalKamar" title="Daftarkan Kamar Baru">
        <form action="{{ route('admin.kamar.store') }}" method="POST" class="space-y-3">
            @csrf

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pilih Kos</label>
                <select name="kos_id" x-model="selectedKosIdForKamar" required class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                    <option value="">-- Pilih Kos --</option>
                    @foreach($kosList as $k)
                    <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Kode Kamar</label>
                    <input type="text" name="kode_kamar" required placeholder="A01 / K01" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Jenis Kamar</label>
                    <select name="tipe" required class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                        <option value="standar">Standar (1 Orang)</option>
                        <option value="berbagi">Berbagi (2 Orang)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Harga / Bulan (Rp)</label>
                    <input type="number" name="harga_per_bulan" required placeholder="1000000" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Harga / Hari (Opsional)</label>
                    <input type="number" name="harga_per_hari" placeholder="100000" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Kapasitas Penghuni</label>
                <input type="number" name="kapasitas" value="1" min="1" max="5" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <x-btn type="button" variant="secondary" size="sm" @click="modalKamar = false">Batal</x-btn>
                <x-btn type="submit" variant="primary" size="sm">Simpan Kamar</x-btn>
            </div>
        </form>
    </x-modal>

    {{-- Modal Edit Kamar --}}
    <x-modal show="modalEditKamar" title="Edit Data Kamar">
        <form :action="editKamarUrl" method="POST" class="space-y-3">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pilih Kos</label>
                <select name="kos_id" x-model="editKamarData.kos_id" required class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                    <option value="">-- Pilih Kos --</option>
                    @foreach($kosList as $k)
                    <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Kode Kamar</label>
                    <input type="text" name="kode_kamar" x-model="editKamarData.kode_kamar" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Jenis Kamar</label>
                    <select name="tipe" x-model="editKamarData.tipe" required class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                        <option value="standar">Standar (1 Orang)</option>
                        <option value="berbagi">Berbagi (2 Orang)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Harga / Bulan (Rp)</label>
                    <input type="number" name="harga_per_bulan" x-model="editKamarData.harga_per_bulan" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Harga / Hari (Opsional)</label>
                    <input type="number" name="harga_per_hari" x-model="editKamarData.harga_per_hari" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Kapasitas Penghuni</label>
                <input type="number" name="kapasitas" x-model="editKamarData.kapasitas" min="1" max="5" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <x-btn type="button" variant="secondary" size="sm" @click="modalEditKamar = false">Batal</x-btn>
                <x-btn type="submit" variant="primary" size="sm">Update Kamar</x-btn>
            </div>
        </form>
    </x-modal>

    {{-- Modal Pendaftaran Penghuni ke Kamar --}}
    @php
    $penghuniUsers = \App\Models\User::where('role', 'penghuni')
    ->where('is_active', true)
    ->with(['penghuniKamar' => function($q) {
    $q->where('status', 'aktif')->with('kamar');
    }])
    ->get();
    $allKamars = \App\Models\Kamar::with('kos')->get();
    @endphp
    <x-modal show="modalPenghuni" title="Daftarkan Penghuni ke Kamar">
        <form action="{{ route('admin.penghuni.daftar') }}" method="POST" class="space-y-3">
            @csrf

            {{-- Pilih Kamar (Kamar Terisi Ditandai Disabled) --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pilih Kamar Kos (Hanya Kamar Kosong)</label>
                <select id="select-kamar-penghuni"
                    name="kamar_id"
                    x-model="selectedKamarIdForPenghuni"
                    @change="updateKamarTipe()"
                    required
                    class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                    <option value="" data-tipe="standar">-- Pilih Kamar Kos --</option>
                    @foreach($allKamars as $km)
                    @php
                    $isFull = $km->status === 'terisi';
                    @endphp
                    <option value="{{ $km->id }}"
                        data-tipe="{{ $km->tipe }}"
                        {{ $isFull ? 'disabled' : '' }}
                        class="{{ $isFull ? 'text-gray-400 bg-gray-100 dark:bg-gray-800' : 'text-gray-900 dark:text-white font-bold' }}">
                        {{ $km->kos->nama ?? 'Kos' }} - Kamar {{ $km->kode_kamar }} (Jenis: {{ ucfirst($km->tipe) }}) {{ $isFull ? '[TERISI - TIDAK DAPAT DIPILIH]' : '[TERSEDIA]' }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Info Jenis Kamar Terpilih --}}
            <div class="p-2.5 rounded-xl border text-xs flex items-center justify-between"
                :class="selectedKamarTipe === 'berbagi' ? 'bg-purple-50 dark:bg-purple-950/30 border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300' : 'bg-blue-50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300'">
                <div>
                    <span class="font-bold uppercase tracking-wider">Jenis Kamar:</span>
                    <span x-text="selectedKamarTipe === 'berbagi' ? 'BERBAGI (Wajib 2 Orang)' : 'STANDAR (1 Orang)'" class="font-bold ml-1"></span>
                </div>
            </div>

            {{-- Penghuni 1 (Wajib) --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                    Pilih Penghuni 1 <span class="text-red-500">*</span>
                </label>
                <select name="penghuni_id" required class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                    <option value="">-- Pilih Anak Kos Ke-1 --</option>
                    @foreach($penghuniUsers as $pu)
                    @php
                    $activePk = $pu->penghuniKamar ? $pu->penghuniKamar->where('status', 'aktif')->first() : null;
                    $alreadyOccupying = $activePk !== null;
                    @endphp
                    <option value="{{ $pu->id }}"
                        {{ $alreadyOccupying ? 'disabled' : '' }}
                        class="{{ $alreadyOccupying ? 'text-gray-400 bg-gray-100 dark:bg-gray-800' : 'text-gray-900 dark:text-white font-bold' }}">
                        {{ $pu->nama }} ({{ $pu->email }}) {{ $alreadyOccupying ? '[SUDAH MENEMPATI ' . ($activePk->kamar->kode_kamar ?? 'KAMAR LAIN') . ']' : '[TERSEDIA]' }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Penghuni 2 (Wajib Jika Tipe Berbagi / 2 Orang) --}}
            <div x-show="selectedKamarTipe === 'berbagi'" x-transition class="space-y-1">
                <label class="block text-xs font-bold text-purple-700 dark:text-purple-300 uppercase tracking-wider mb-1">
                    Pilih Penghuni 2 <span class="text-red-500">* (Wajib Untuk Kamar Berbagi)</span>
                </label>
                <select name="penghuni_id_2" :required="selectedKamarTipe === 'berbagi'" class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                    <option value="">-- Pilih Anak Kos Ke-2 --</option>
                    @foreach($penghuniUsers as $pu)
                    @php
                    $activePk = $pu->penghuniKamar ? $pu->penghuniKamar->where('status', 'aktif')->first() : null;
                    $alreadyOccupying = $activePk !== null;
                    @endphp
                    <option value="{{ $pu->id }}"
                        {{ $alreadyOccupying ? 'disabled' : '' }}
                        class="{{ $alreadyOccupying ? 'text-gray-400 bg-gray-100 dark:bg-gray-800' : 'text-gray-900 dark:text-white font-bold' }}">
                        {{ $pu->nama }} ({{ $pu->email }}) {{ $alreadyOccupying ? '[SUDAH MENEMPATI ' . ($activePk->kamar->kode_kamar ?? 'KAMAR LAIN') . ']' : '[TERSEDIA]' }}
                    </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-purple-600 dark:text-purple-400 italic mt-0.5">Kamar tipe berbagi harus diisi oleh 2 orang berbeda.</p>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Durasi Sewa</label>
                    <select name="durasi" required class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                        <option value="bulanan">Bulanan</option>
                        <option value="harian">Harian</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Tanggal Masuk</label>
                    <input type="date" name="tanggal_masuk" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Tanggal Selesai / Jatuh Tempo</label>
                <input type="date" name="tanggal_keluar" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <x-btn type="button" variant="secondary" size="sm" @click="modalPenghuni = false">Batal</x-btn>
                <x-btn type="submit" variant="primary" size="sm">Daftarkan Penghuni</x-btn>
            </div>
        </form>
    </x-modal>
</div>
@endsection