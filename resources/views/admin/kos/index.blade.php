@extends('layouts.app')

@php
$isSuperAdmin = request()->is('superadmin*');
$p = $isSuperAdmin ? 'superadmin.' : 'admin.';

$penghuniUsers = \App\Models\User::where('role', 'penghuni')
->where('is_active', true)
->with(['penghuniKamar' => function($q) {
$q->where('status', 'aktif')->with('kamar');
}])
->get();
$allKamars = \App\Models\Kamar::with('kos')->get();

$mitrasJson = $mitras->map(function($m) {
return [
'id' => $m->id,
'nama' => $m->nama,
'no_hp' => $m->no_hp ?? '-',
'email' => $m->email,
];
})->values();

$kosListJson = $kosList->map(function($k) {
return [
'id' => $k->id,
'nama' => $k->nama,
'alamat' => $k->alamat ?? ''
];
})->values();

$allKamarsJson = $allKamars->map(function($km) {
$isFull = $km->status === 'terisi';
return [
'id' => $km->id,
'kode_kamar' => $km->kode_kamar,
'kos_nama' => $km->kos->nama ?? 'Kos',
'tipe' => $km->tipe,
'status' => $km->status,
'isFull' => $isFull,
];
})->values();

$penghuniUsersJson = $penghuniUsers->map(function($pu) {
$activePk = $pu->penghuniKamar ? $pu->penghuniKamar->where('status', 'aktif')->first() : null;
$alreadyOccupying = $activePk !== null;
$isDisabled = $alreadyOccupying || !$pu->is_active;
$statusTag = !$pu->is_active ? '[NONAKTIF]' : ($alreadyOccupying ? '[' . ($activePk->kamar->kode_kamar ?? 'TERISI') . ']' : '[READY]');
return [
'id' => $pu->id,
'nama' => $pu->nama,
'no_hp' => $pu->no_hp ?? '-',
'email' => $pu->email,
'isDisabled' => $isDisabled,
'statusTag' => $statusTag,
];
})->values();
@endphp

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
    filterMasaAktif: 'all',
    filterTipeSewa: 'all',
    search: '',
    matchKamar(kamar, kosSearchText) {
        if (!kamar) return true;
        if (this.filterTipeKamar !== 'all' && kamar.tipe !== this.filterTipeKamar) {
            return false;
        }
        if (this.filterMasaAktif !== 'all' && kamar.statusMasaAktif !== this.filterMasaAktif) {
            return false;
        }
        if (this.filterTipeSewa === 'bulan' && !kamar.hasBulan) return false;
        if (this.filterTipeSewa === 'minggu' && !kamar.hasMinggu) return false;
        if (this.filterTipeSewa === 'hari' && !kamar.hasHari) return false;

        if (this.search) {
            const q = this.search.toLowerCase().trim();
            const fullText = ((kamar.searchableText || '') + ' ' + (kosSearchText || '')).toLowerCase();
            if (!fullText.includes(q)) return false;
        }
        return true;
    },
    matchKos(kosId, rooms, kosSearchText) {
        if (this.filterKosId !== 'all' && this.filterKosId == kosId) {
            return true;
        }
        if (this.filterKosId !== 'all' && this.filterKosId != kosId) {
            return false;
        }
        if (!rooms || rooms.length === 0) {
            if (this.filterTipeKamar !== 'all' || this.filterMasaAktif !== 'all' || this.filterTipeSewa !== 'all') {
                return false;
            }
            if (this.search) {
                return (kosSearchText || '').toLowerCase().includes(this.search.toLowerCase().trim());
            }
            return true;
        }
        return rooms.some(kamar => this.matchKamar(kamar, kosSearchText));
    },
    hasAnyVisibleKos(allKosData) {
        if (!allKosData || allKosData.length === 0) return false;
        return allKosData.some(item => this.matchKos(item.id, item.rooms, item.searchText));
    },
    showImageModal: false,
    previewImageUrl: '',
    previewImageTitle: '',
    openImageModal(url, title) {
        this.previewImageUrl = url;
        this.previewImageTitle = title;
        this.showImageModal = true;
    },
    parseDigits(val) {
        if (!val && val !== 0) return '';
        let str = val.toString().trim();
        if (/^\d+\.\d+$/.test(str)) {
            str = Math.floor(parseFloat(str)).toString();
        }
        return str.replace(/[^0-9]/g, '');
    },
    formatRupiah(val) {
        if (!val && val !== 0) return '';
        const digits = this.parseDigits(val);
        if (!digits) return '';
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(digits);
    },
    editKosData: { id: '', mitra_id: '', nama: '', alamat: '', link_gmaps: '', bank: '', no_rekening: '', nama_pemilik_rekening: '' },
    editKosUrl: '',
    editKamarData: { id: '', kos_id: '', kode_kamar: '', tipe: 'standar', detail: '', harga_per_bulan: '', display_harga_per_bulan: '', harga_per_minggu: '', display_harga_per_minggu: '', harga_per_hari: '', display_harga_per_hari: '', kapasitas: 1, wa_group_id: '', link_grup_wa: '' },
    editKamarUrl: '',
    openEditKosModal(kos) {
        this.editKosData = {
            id: kos.id,
            mitra_id: kos.mitra_id,
            nama: kos.nama || '',
            alamat: kos.alamat || '',
            link_gmaps: kos.link_gmaps || '',
            bank: kos.bank || '',
            no_rekening: kos.no_rekening || '',
            nama_pemilik_rekening: kos.nama_pemilik_rekening || ''
        };
        const prefix = '{{ $isSuperAdmin ? 'superadmin' : 'admin' }}';
        this.editKosUrl = '/' + prefix + '/kos/' + kos.id;
        this.modalEditKos = true;
    },
    openEditKamarModal(kamar) {
        const hBulan = kamar.harga_per_bulan ? this.parseDigits(kamar.harga_per_bulan) : '';
        const hMinggu = kamar.harga_per_minggu ? this.parseDigits(kamar.harga_per_minggu) : '';
        const hHari = kamar.harga_per_hari ? this.parseDigits(kamar.harga_per_hari) : '';
        this.editKamarData = {
            id: kamar.id,
            kos_id: kamar.kos_id,
            kode_kamar: kamar.kode_kamar || '',
            tipe: kamar.tipe || 'standar',
            detail: kamar.detail || '',
            harga_per_bulan: hBulan,
            harga_per_minggu: hMinggu,
            harga_per_hari: hHari,
            display_harga_per_bulan: hBulan ? this.formatRupiah(hBulan) : '',
            display_harga_per_minggu: hMinggu ? this.formatRupiah(hMinggu) : '',
            display_harga_per_hari: hHari ? this.formatRupiah(hHari) : '',
            kapasitas: kamar.kapasitas || (kamar.tipe === 'berbagi' ? 2 : 1),
            wa_group_id: kamar.wa_group_id || '',
            link_grup_wa: kamar.link_grup_wa || ''
        };
        const prefix = '{{ $isSuperAdmin ? 'superadmin' : 'admin' }}';
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
    <x-page-header title="Pendaftaran Kos & Kamar" subtitle="Kelola kos, kamar, dan penempatan anak kos" backUrl="{{ route('dashboard') }}" />

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

        <button @click="selectedKamarIdForPenghuni = ''; selectedKamarTipe = 'standar'; modalPenghuni = true" class="flex flex-col items-center justify-center p-3 bg-purple-600 hover:bg-purple-700 text-white rounded-2xl shadow-sm active:scale-95 transition-all text-center">
            <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            <span class="text-xs font-bold leading-tight">Penghuni</span>
        </button>
    </div>

    {{-- Filter Bar: Search, Kos, Tipe Kamar, Tipe Sewa & Status Masa Aktif --}}
    @if(!$kosList->isEmpty())
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="grid grid-cols-1 gap-2 pb-2 border-b border-gray-100 dark:border-gray-800">
            <div>
                <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Filter &amp; Pencarian Kos / Kamar
                </label>
                <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 font-mono"
                    x-text="(filterKosId === 'all' ? 'Semua Kos' : 'Kos Terpilih') + ' · ' + (filterTipeKamar === 'all' ? 'Semua Tipe Kamar' : (filterTipeKamar === 'standar' ? 'Standar' : 'Berbagi')) + ' · ' + (filterTipeSewa === 'all' ? 'Semua Tipe Sewa' : (filterTipeSewa === 'bulan' ? 'Bulanan' : (filterTipeSewa === 'minggu' ? 'Mingguan' : 'Harian'))) + ' · ' + (filterMasaAktif === 'all' ? 'Semua Status' : (filterMasaAktif === 'expired' ? 'Jatuh Tempo' : (filterMasaAktif === 'aktif' ? 'Masih Aktif' : 'Kamar Kosong')))"></span>
            </div>
            <button type="button"
                @click="filterKosId = 'all'; filterTipeKamar = 'all'; filterMasaAktif = 'all'; filterTipeSewa = 'all'; search = ''"
                x-show="filterKosId !== 'all' || filterTipeKamar !== 'all' || filterMasaAktif !== 'all' || filterTipeSewa !== 'all' || search !== ''"
                class="text-[10px] w-[75px] font-bold text-red-600 dark:text-red-400 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-900/60 px-2.5 py-1 rounded-lg transition-all self-start sm:self-auto">
                Reset Filter
            </button>
        </div>

        {{-- Search Bar Input --}}
        <div class="relative">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" x-model="search" placeholder="Cari nama kos, kode kamar, nama penghuni, mitra, atau alamat..."
                class="w-full pl-9 pr-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-emerald-500 focus:outline-none">
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-2 gap-2">
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
                <label class="block text-[10px] font-semibold text-gray-500 dark:text-gray-400 mb-0.5">Tipe Kamar:</label>
                <select x-model="filterTipeKamar" class="w-full py-1.5 px-2 bg-gray-50 dark:bg-gray-800 border border-purple-200 dark:border-purple-800/60 rounded-xl text-xs font-bold text-gray-900 dark:text-white focus:ring-purple-500">
                    <option value="all">-- Semua Tipe --</option>
                    <option value="standar">Standar (1 Orang)</option>
                    <option value="berbagi">Berbagi (2 Orang)</option>
                </select>
            </div>

            {{-- Filter Tipe Sewa (Bulanan / Mingguan / Harian) --}}
            <div>
                <label class="block text-[10px] font-semibold text-gray-500 dark:text-gray-400 mb-0.5">Tipe Sewa (Durasi):</label>
                <select x-model="filterTipeSewa" class="w-full py-1.5 px-2 bg-gray-50 dark:bg-gray-800 border border-blue-200 dark:border-blue-800/60 rounded-xl text-xs font-bold text-gray-900 dark:text-white focus:ring-blue-500">
                    <option value="all">-- Semua Tipe Sewa --</option>
                    <option value="bulan">📅 Sewa Bulanan</option>
                    <option value="minggu">📆 Sewa Mingguan</option>
                    <option value="hari">⏱️ Sewa Harian</option>
                </select>
            </div>

            {{-- Filter Status Masa Aktif / Jatuh Tempo --}}
            <div>
                <label class="block text-[10px] font-semibold text-gray-500 dark:text-gray-400 mb-0.5">Status Masa Aktif:</label>
                <select x-model="filterMasaAktif" class="w-full py-1.5 px-2 bg-gray-50 dark:bg-gray-800 border border-amber-200 dark:border-amber-800/60 rounded-xl text-xs font-bold text-gray-900 dark:text-white focus:ring-amber-500">
                    <option value="all">-- Semua Status Sewa --</option>
                    <option value="expired">⚠️ Jatuh Tempo / Masa Aktif Habis</option>
                    <option value="aktif">✅ Masih Aktif (Belum Jatuh Tempo)</option>
                    <option value="kosong">🏠 Kamar Kosong</option>
                </select>
            </div>
        </div>
    </div>
    @endif

    {{-- Daftar Kos List --}}
    @if($kosList->isEmpty())
    <x-empty-state message="Belum ada kos yang terdaftar. Klik + Kos Baru untuk memulainya." />
    @else
    @php
    $todayDate = \Carbon\Carbon::now()->startOfDay();
    $allKosFilterData = $kosList->map(function($kos) use ($todayDate) {
    $rooms = $kos->kamar->map(function($k) use ($todayDate) {
    $activePenghunis = $k->penghuniKamar ? $k->penghuniKamar->where('status', 'aktif') : collect();
    $hasExpiredPenghuni = $activePenghunis->contains(function($pk) {
    return $pk->tanggal_keluar && \Carbon\Carbon::parse($pk->tanggal_keluar)->setTime(14, 0, 0)->isPast();
    });
    $statusMasaAktif = $activePenghunis->isEmpty() ? 'kosong' : ($hasExpiredPenghuni ? 'expired' : 'aktif');
    $penghuniNames = $activePenghunis->map(fn($pk) => $pk->penghuni->nama ?? '')->filter()->implode(' ');

    return [
    'id' => $k->id,
    'kode_kamar' => (string)$k->kode_kamar,
    'tipe' => (string)$k->tipe,
    'statusMasaAktif' => $statusMasaAktif,
    'hasBulan' => (float)($k->harga_per_bulan ?? 0) > 0,
    'hasMinggu' => (float)($k->harga_per_minggu ?? 0) > 0,
    'hasHari' => (float)($k->harga_per_hari ?? 0) > 0,
    'searchableText' => strtolower($k->kode_kamar . ' ' . $k->tipe . ' ' . ($k->detail ?? '') . ' ' . $penghuniNames),
    ];
    })->values()->toArray();

    return [
    'id' => $kos->id,
    'rooms' => $rooms,
    'searchText' => strtolower($kos->nama . ' ' . ($kos->mitra->nama ?? '') . ' ' . ($kos->alamat ?? '')),
    ];
    })->values()->toArray();
    @endphp

    <div class="space-y-4">
        @foreach($kosList as $index => $kos)
        @php
        $kosMeta = $allKosFilterData[$index] ?? [];
        $kamarFilterArray = $kosMeta['rooms'] ?? [];
        $kosSearchText = $kosMeta['searchText'] ?? '';
        @endphp
        <div x-show="matchKos({{ $kos->id }}, @js($kamarFilterArray), @js($kosSearchText))"
            x-transition
            class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">

            {{-- Foto Kos (Cropped Half-Height) --}}
            @if($kos->foto)
            <div class="relative w-full h-36 sm:h-44 overflow-hidden bg-gray-900 group cursor-pointer border-b border-gray-100 dark:border-gray-800"
                @click="openImageModal('{{ str_starts_with($kos->foto, 'http') ? $kos->foto : asset('storage/' . $kos->foto) }}', '{{ addslashes($kos->nama) }}')">
                <img src="{{ str_starts_with($kos->foto, 'http') ? $kos->foto : asset('storage/' . $kos->foto) }}"
                    alt="{{ $kos->nama }}"
                    class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300 opacity-90 group-hover:opacity-100">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                <div class="absolute bottom-2.5 right-2.5 px-2.5 py-1 bg-black/60 backdrop-blur-md rounded-lg text-[10px] font-bold text-white flex items-center gap-1 shadow-sm group-hover:bg-emerald-600 transition-colors">
                    <span>🔍 Klik untuk Perbesar Foto Kos</span>
                </div>
            </div>
            @endif

            <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 space-y-2">
                {{-- Baris Atas: Info Mitra (Kiri) & Tombol Aksi (Kanan) --}}
                <div class="flex flex-wrap items-start justify-between gap-2">
                    {{-- Sisi Kiri: Nama Mitra & (di bawahnya) Kontak WA / Telepon --}}
                    <div class="flex flex-col gap-1 min-w-0">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                            Mitra: {{ $kos->mitra->nama ?? '-' }}
                        </span>
                        @if($kos->mitra && $kos->mitra->no_hp)
                        @php
                        $cleanHp = preg_replace('/[^0-9]/', '', $kos->mitra->no_hp);
                        $waHp = str_starts_with($cleanHp, '0') ? '62' . substr($cleanHp, 1) : $cleanHp;
                        @endphp
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <a href="https://wa.me/{{ $waHp }}"
                                target="_blank"
                                class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 dark:hover:bg-emerald-800/50 border border-emerald-200 dark:border-emerald-800 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 rounded-md transition-all active:scale-95 shadow-2xs"
                                title="Kirim WhatsApp ke Mitra">
                                <span class="[&>svg]:h-3 [&>svg]:w-3">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="currentColor"
                                        viewBox="0 0 448 512">
                                        <path
                                            d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                                    </svg>
                                </span>
                                <span>WhatsApp</span>
                            </a>
                            <a href="tel:{{ $kos->mitra->no_hp }}"
                                class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 dark:hover:bg-blue-800/50 border border-blue-200 dark:border-blue-800 text-[10px] font-bold text-blue-700 dark:text-blue-300 rounded-md transition-all active:scale-95 shadow-2xs"
                                title="Telepon Langsung Mitra">
                                <span>📞</span>
                                <span>Telepon</span>
                            </a>
                        </div>
                        @endif
                    </div>

                    {{-- Sisi Kanan: Action Badges & Buttons --}}
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        {{-- Jumlah Kamar Badge --}}
                        <span class="inline-flex items-center justify-center px-2.5 py-1 text-[10px] font-bold rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                            {{ $kos->kamar->count() }} Kamar
                        </span>

                        {{-- Toggle Lock / Unlock Edit Kamar Mitra --}}
                        <form action="{{ route($p . 'kos.toggle-lock', $kos->slug ?? $kos->id) }}" method="POST" class="inline-flex m-0">
                            @csrf
                            @if($kos->is_locked)
                            <button type="submit"
                                class="px-2.5 py-1 text-[10px] font-bold text-rose-700 dark:text-rose-300 bg-rose-100 hover:bg-rose-200 dark:bg-rose-900/40 dark:hover:bg-rose-900/60 rounded-lg transition-all active:scale-95 inline-flex items-center justify-center gap-1 shadow-2xs cursor-pointer"
                                title="Status Edit Kamar oleh Mitra: TERKUNCI. Klik untuk Membuka Kunci.">
                                <span>🔒</span>
                                <span>Lock</span>
                            </button>
                            @else
                            <button type="submit"
                                class="px-2.5 py-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:hover:bg-emerald-900/60 rounded-lg transition-all active:scale-95 inline-flex items-center justify-center gap-1 shadow-2xs cursor-pointer"
                                title="Status Edit Kamar oleh Mitra: TERBUKA. Klik untuk Mengunci Edit Kamar.">
                                <span>🔓</span>
                                <span>Unlock</span>
                            </button>
                            @endif
                        </form>

                        {{-- Tombol Edit Kos --}}
                        <button type="button"
                            @click="openEditKosModal(@js($kos))"
                            class="px-2.5 py-1 text-[10px] font-bold text-amber-700 dark:text-amber-300 bg-amber-100 hover:bg-amber-200 dark:hover:bg-amber-800/50 dark:bg-amber-900/40 dark:hover:bg-amber-900/60 rounded-lg transition-all inline-flex items-center justify-center gap-1 active:scale-95 cursor-pointer shadow-2xs">
                            <span>✏️</span>
                            <span>Edit</span>
                        </button>

                        {{-- Tombol Hapus Kos --}}
                        <form action="{{ route($p . 'kos.destroy', $kos->slug ?? $kos->id) }}" method="POST" class="inline-flex m-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Kos {{ addslashes($kos->nama) }}? Seluruh kamar dan data di dalamnya juga akan terhapus.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="px-2.5 py-1 text-[10px] font-bold text-red-700 dark:text-red-300 bg-red-100 hover:bg-red-200 dark:hover:bg-red-800/50 dark:bg-red-900/40 dark:hover:bg-red-900/60 rounded-lg transition-all inline-flex items-center justify-center gap-1 active:scale-95 cursor-pointer shadow-2xs"
                                title="Hapus Kos">
                                <span>🗑️</span>
                                <span>Hapus</span>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Baris Bawah: Nama Kos, Alamat & Info Rekening --}}
                <div class="pt-0.5">
                    <h3 class="font-bold text-base text-gray-900 dark:text-white leading-snug truncate">{{ $kos->nama }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $kos->alamat ?? 'Alamat tidak diisi' }}</p>
                    @if($kos->bank && $kos->no_rekening)
                    <p class="text-[11px] font-mono text-gray-400 mt-1 truncate">
                        Rekening: {{ $kos->bank }} - {{ $kos->no_rekening }} (a.n {{ $kos->nama_pemilik_rekening ?? '-' }})
                    </p>
                    @endif
                </div>
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
                <div class="grid grid-cols-1 gap-4">
                    @foreach($kos->kamar as $kIndex => $kamar)
                    @php
                    $kamarMeta = $kamarFilterArray[$kIndex] ?? [];
                    $activePenghunis = $kamar->penghuniKamar ? $kamar->penghuniKamar->where('status', 'aktif') : collect();
                    $isTerisi = $kamar->status === 'terisi' || $activePenghunis->isNotEmpty();

                    $hasExpiredPenghuni = $activePenghunis->contains(function($pk) use ($todayDate) {
                    return $pk->tanggal_keluar && \Carbon\Carbon::parse($pk->tanggal_keluar)->setTime(14, 0, 0)->isPast();
                    });
                    @endphp

                    <div x-show="matchKamar(@js($kamarMeta), @js($kosSearchText))"
                        x-transition
                        class="p-3.5 sm:p-4 rounded-2xl border {{ $hasExpiredPenghuni ? 'bg-red-50/40 dark:bg-red-950/20 border-red-200 dark:border-red-900/50' : ($isTerisi ? 'bg-emerald-50/30 dark:bg-emerald-950/20 border-emerald-200/80 dark:border-emerald-900/50' : 'bg-gray-50/80 dark:bg-gray-800/40 border-gray-200/80 dark:border-gray-800') }} space-y-2.5 shadow-2xs">

                        {{-- Baris Header Kamar & Status --}}
                        <div class="grid grid-cols-[1fr_auto] items-center gap-2 border-b border-gray-200/50 dark:border-gray-700/40 pb-2">
                            <div class="grid grid-flow-col auto-cols-max items-center gap-1.5">
                                <span class="font-bold text-xs font-mono text-gray-900 dark:text-white bg-white dark:bg-gray-900 px-2 py-0.5 rounded-md border border-gray-200/60 dark:border-gray-800">
                                    Kamar {{ $kamar->kode_kamar }}
                                </span>
                                <span class="px-1.5 py-0.5 text-[8px] uppercase font-bold rounded-md {{ $kamar->tipe === 'berbagi' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' }}">
                                    {{ ucfirst($kamar->tipe) }}
                                </span>
                                <x-badge type="{{ $hasExpiredPenghuni ? 'danger' : ($isTerisi ? 'success' : 'warning') }}" size="xs">
                                    {{ $hasExpiredPenghuni ? 'Jatuh Tempo' : ($isTerisi ? 'Terisi' : 'Kosong') }}
                                </x-badge>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 items-center gap-1.5">
                                <a href="{{ route($p . 'kamar.show', $kamar->kode_kamar ?? $kamar->id) }}"
                                    class="col-span-1 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-100/90 hover:bg-emerald-200 dark:hover:bg-emerald-800/50 dark:bg-emerald-900/50 rounded-md transition-all active:scale-95">
                                    <span>Detail</span>
                                </a>
                                <button type="button"
                                    @click="openEditKamarModal(@js($kamar))"
                                    class="col-span-1 px-2 py-0.5 text-[10px] font-bold text-blue-700 dark:text-blue-300 bg-blue-100/90 hover:bg-blue-200 dark:hover:bg-blue-800/50 dark:bg-blue-900/50 rounded-md transition-all active:scale-95 cursor-pointer">
                                    <span>Edit</span>
                                </button>
                                <form action="{{ route($p . 'kamar.destroy', $kamar->kode_kamar ?? $kamar->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Kamar {{ addslashes($kamar->kode_kamar) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-2 py-0.5 text-[10px] font-bold text-red-700 dark:text-red-300 bg-red-100 hover:bg-red-200 dark:hover:bg-red-800/50 dark:bg-red-900/40 dark:hover:bg-red-900/60 rounded-md transition-all active:scale-95 cursor-pointer"
                                        title="Hapus Kamar">
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Detail Perabotan / Fasilitas --}}
                        @php
                        $getFacilityIcon = function($name) {
                        $lower = strtolower($name);
                        if (str_contains($lower, 'kasur') || str_contains($lower, 'bed') || str_contains($lower, 'matras')) return '🛏️';
                        if (str_contains($lower, 'lemari') || str_contains($lower, 'wardrobe') || str_contains($lower, 'kabinet')) return '🗄️';
                        if (str_contains($lower, 'meja') || str_contains($lower, 'kursi') || str_contains($lower, 'desk')) return '🪑';
                        if (str_contains($lower, 'kipas') || str_contains($lower, 'fan')) return '🪭';
                        if (str_contains($lower, 'mandi') || str_contains($lower, 'toilet') || str_contains($lower, 'wc')) return '🚿';
                        if (str_contains($lower, 'ac') || str_contains($lower, 'pendingin')) return '❄️';
                        if (str_contains($lower, 'wifi') || str_contains($lower, 'internet')) return '📶';
                        if (str_contains($lower, 'dapur') || str_contains($lower, 'masak')) return '🍳';
                        if (str_contains($lower, 'tv') || str_contains($lower, 'televisi')) return '📺';
                        return '📦';
                        };
                        $detailsList = array_filter(array_map('trim', explode(',', $kamar->detail ?? '')));
                        @endphp
                        <div class="text-[11px] font-medium text-gray-700 dark:text-gray-300 bg-white/80 dark:bg-gray-900/60 p-2 sm:p-2.5 rounded-xl border border-gray-200/70 dark:border-gray-700/60 flex flex-wrap items-center gap-1.5">
                            <span class="font-bold text-amber-600 dark:text-amber-400 mr-0.5">📦 Perabotan:</span>
                            @if(empty($kamar->detail) || strtolower(trim($kamar->detail)) === 'kosong')
                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-400 rounded-lg text-[10px] italic">
                                Kosong (Tanpa Perabotan)
                            </span>
                            @else
                            @foreach($detailsList as $item)
                            @php
                            $icon = $getFacilityIcon($item);
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 border border-amber-200/70 dark:border-amber-800/50 rounded-lg text-[10px] font-bold">
                                <span>{{ $icon }}</span>
                                <span>{{ $item }}</span>
                            </span>
                            @endforeach
                            @endif
                        </div>

                        {{-- Rincian Biaya & Link WA Group --}}
                        <div class="grid gap-2 p-3 bg-white/60 dark:bg-gray-900/40 rounded-xl border border-gray-200/50 dark:border-gray-800">

                            {{-- Badge Harga --}}
                            <div class="grid grid-cols-[repeat(auto-fit,minmax(0,max-content))] gap-2">
                                <span class="grid place-items-center w-max px-2 py-1 text-[10px] font-bold rounded text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40">
                                    Rp {{ number_format($kamar->harga_per_bulan, 0, ',', '.') }}/bln
                                </span>

                                @if($kamar->harga_per_minggu)
                                <span class="grid place-items-center w-max px-2 py-1 text-[10px] font-bold rounded text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/40">
                                    Rp {{ number_format($kamar->harga_per_minggu, 0, ',', '.') }}/minggu
                                </span>
                                @endif

                                @if($kamar->harga_per_hari)
                                <span class="grid place-items-center w-max px-2 py-1 text-[10px] font-bold rounded text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40">
                                    Rp {{ number_format($kamar->harga_per_hari, 0, ',', '.') }}/hari
                                </span>
                                @endif
                            </div>

                            {{-- WhatsApp Group --}}
                            <div class="grid">
                                @if($kamar->link_grup_wa)
                                <a href="{{ $kamar->link_grup_wa }}" target="_blank"
                                    class="grid grid-flow-col auto-cols-max items-center gap-1.5 w-max px-2.5 py-1 text-[10px] font-bold text-center rounded-md text-emerald-700 dark:text-emerald-300 bg-emerald-100/90 dark:bg-emerald-900/50 hover:underline transition-colors">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 fill-current" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                                    </svg>
                                    <span>WhatsApp Group</span>
                                </a>
                                @elseif($kamar->wa_group_id)
                                <span class="grid place-items-start w-max px-2 py-1 text-xs font-mono text-gray-400 bg-gray-100 dark:bg-gray-800 rounded" title="ID Grup Fonnte: {{ $kamar->wa_group_id }}">
                                    WhatsApp Group Registered
                                </span>
                                @endif
                            </div>

                        </div>

                        {{-- Penghuni --}}
                        @if($activePenghunis->isNotEmpty())
                        <div class="mt-2 pt-2 border-t border-emerald-200/60 dark:border-emerald-900/40 space-y-1.5">
                            <div class="grid grid-cols-1 items-center text-[10px] font-bold text-emerald-700 dark:text-emerald-400">
                                <span>👥 PENGHUNI AKTIF ({{ $activePenghunis->count() }}/{{ $kamar->kapasitas }})</span>
                            </div>

                            @foreach($activePenghunis as $pk)
                            @php
                            $targetKeluar = $pk->tanggal_keluar ? \Carbon\Carbon::parse($pk->tanggal_keluar)->setTime(14, 0, 0) : null;
                            $isPkExpired = $targetKeluar && $targetKeluar->isPast();
                            $overdueDays = $isPkExpired ? max(1, (int) $targetKeluar->diffInDays(now())) : 0;
                            $tglKeluarStr = $pk->tanggal_keluar ? \Carbon\Carbon::parse($pk->tanggal_keluar)->format('d M Y') : '-';
                            $pkDaysLeft = $targetKeluar ? round(now()->diffInDays($targetKeluar, false)) : null;
                            $isPkDueSoon = $isPkExpired || ($pkDaysLeft !== null && $pkDaysLeft <= 3);
                                $paymentStatus=$pk->getStatusPembayaranInfo();
                                @endphp
                                <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2 items-center text-xs p-2 rounded-xl border {{ $isPkExpired ? 'bg-red-100/70 dark:bg-red-950/40 border-red-200 dark:border-red-900/60' : 'bg-white/90 dark:bg-gray-900/80 border-emerald-100 dark:border-emerald-900/30' }}">
                                    <div class="truncate">
                                        <span class="font-bold text-gray-900 dark:text-white block truncate text-[11px]">
                                            {{ $pk->penghuni->nama ?? '-' }}
                                            @if($pk->penghuni && $pk->penghuni->no_hp)
                                            <span class="text-[10px] font-mono font-semibold text-emerald-600 dark:text-emerald-400 ml-1">📞 {{ $pk->penghuni->no_hp }}</span>
                                            @endif
                                        </span>
                                        <span class="text-[9px] font-mono text-gray-500">
                                            Masuk: {{ $pk->tanggal_masuk ? \Carbon\Carbon::parse($pk->tanggal_masuk)->format('d M Y') : '-' }} | {{ ucfirst($pk->durasi) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5 flex-wrap justify-start sm:justify-end">
                                        <span class="px-2 py-0.5 text-[9px] font-bold rounded-md {{ $paymentStatus['badge_class'] }}">
                                            {{ $paymentStatus['label'] }}
                                        </span>
                                        <span class="px-2 py-0.5 text-[9px] font-bold rounded-md {{ $isPkExpired ? 'bg-red-200 text-red-800 dark:bg-red-900/80 dark:text-red-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' }}">
                                            {{ $isPkExpired ? "⚠️ Terlewat {$overdueDays} Hari ({$tglKeluarStr})" : "Sewa s/d {$tglKeluarStr}" }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach

                                {{-- Tombol Aksi Bawah Kamar (Kirim Pengumuman & Kosongkan Kamar) --}}
                                <div class="pt-1.5 flex items-center justify-end gap-2 flex-wrap">
                                    @if($hasExpiredPenghuni)
                                    @php
                                    $pengumumanRoute = route($p . 'pengumuman.create', ['kamar_id' => $kamar->id]);
                                    @endphp
                                    <a href="{{ $pengumumanRoute }}" class="px-2.5 py-1 text-[10px] font-bold text-amber-800 dark:text-amber-200 bg-amber-100 hover:bg-amber-200 dark:bg-amber-950/80 dark:hover:bg-amber-900 rounded-lg border border-amber-300 dark:border-amber-800 flex items-center gap-1 active:scale-95 transition-all animate-pulse" title="Kirim Pengumuman Jatuh Tempo ke Kamar {{ $kamar->kode_kamar }}">
                                        <span>📢 Pengumuman Jatuh Tempo</span>
                                    </a>
                                    @endif

                                    <form action="{{ route($p . 'kamar.kosongkan', $kamar->kode_kamar ?? $kamar->id) }}" method="POST" onsubmit="return confirm('Kosongkan Kamar {{ $kamar->kode_kamar }} dan selesaikan sewa penghuni?')">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-[10px] font-bold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-300 rounded-lg border border-red-200 dark:border-red-900/50 flex items-center gap-1 active:scale-95 transition-all">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            <span>Kosongkan Kamar</span>
                                        </button>
                                    </form>
                                </div>
                        </div>
                        @else
                        <div class="mt-2 pt-2 border-t border-gray-200/60 dark:border-gray-700/60 grid grid-cols-[1fr_auto] items-center text-xs">
                            <span class="text-amber-600 font-bold text-[11px]">🏠 Belum Ada Penghuni</span>
                            <button @click="
                                        selectedKamarIdForPenghuni = '{{ $kamar->id }}'; 
                                        selectedKamarTipe = '{{ $kamar->tipe }}'; 
                                        modalPenghuni = true;
                                    "
                                class="px-2.5 py-1 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/40 dark:hover:bg-purple-900/60 text-purple-700 dark:text-purple-300 font-bold text-[10px] rounded-lg transition-all active:scale-95">
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

        <div x-show="!hasAnyVisibleKos(@js($allKosFilterData))" class="p-8 text-center bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm" x-cloak>
            <x-empty-state message="Tidak ditemukan kos atau kamar yang sesuai dengan filter / pencarian Anda." />
        </div>
    </div>
    @endif

    {{-- Modal Pendaftaran Kos Baru --}}
    <x-modal show="modalKos" title="Daftarkan Kos Baru">
        <form action="{{ route($p . 'kos.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf

            <div x-data="{
            open: false,
            search: '',
            selectedId: '',
            mitras: @js($mitrasJson),
            get filtered() {
                if (!this.search) return this.mitras;
                const q = this.search.toLowerCase();
                return this.mitras.filter(m => (m.nama && m.nama.toLowerCase().includes(q)) || (m.no_hp && m.no_hp.includes(q)) || (m.email && m.email.toLowerCase().includes(q)));
            },
            select(mitra) {
                this.selectedId = mitra.id;
                this.search = mitra.nama + ' (' + (mitra.no_hp !== '-' ? mitra.no_hp : mitra.email) + ')';
                this.open = false;
            }
        }" class="relative">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pemilik / Mitra Kos <span class="text-red-500">*</span></label>
                <input type="text"
                    x-model="search"
                    @focus="open = true"
                    @click.away="open = false"
                    @input="open = true; selectedId = ''"
                    placeholder="Ketik nama atau no. hp mitra..."
                    required
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                <input type="hidden" name="mitra_id" :value="selectedId" required>

                <div x-show="open && filtered.length > 0" x-transition class="absolute left-0 right-0 top-full mt-1 max-h-48 overflow-y-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl z-50 p-1 space-y-1">
                    <template x-for="item in filtered" :key="item.id">
                        <div @click="select(item)" class="p-2 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 rounded-lg cursor-pointer text-xs flex justify-between items-center transition-all">
                            <div class="font-bold text-gray-900 dark:text-white" x-text="item.nama"></div>
                            <div class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400" x-text="'📞 ' + (item.no_hp !== '-' ? item.no_hp : item.email)"></div>
                        </div>
                    </template>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Kos <span class="text-red-500">*</span></label>
                <input type="text" name="nama" required placeholder="Contoh: Kos Mawar Asri" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Foto Kos (Opsional)</label>
                <input type="file" name="foto" accept="image/*" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
                <p class="text-[10px] text-gray-400 mt-0.5 italic">* Format gambar: JPG, PNG, WEBP. Maks 3MB.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Alamat Lengkap <span class="text-red-500">*</span></label>
                <textarea name="alamat" rows="2" required placeholder="Alamat jalan, nomor, kecamatan" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Link Google Maps <span class="text-red-500">*</span></label>
                <input type="text" name="link_gmaps" required placeholder="https://maps.google.com/..." class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Bank Pembayaran <span class="text-red-500">*</span></label>
                    <input type="text" name="bank" required placeholder="Contoh: BCA / Mandiri / GoPay" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">No. Rekening <span class="text-red-500">*</span></label>
                    <input type="text" name="no_rekening" required placeholder="1234567890" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Pemilik Rekening <span class="text-red-500">*</span></label>
                <input type="text" name="nama_pemilik_rekening" required placeholder="Nama Sesuai Rekening" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <x-btn type="button" variant="secondary" size="sm" @click="modalKos = false">Batal</x-btn>
                <x-btn type="submit" variant="primary" size="sm">Simpan Kos</x-btn>
            </div>
        </form>
    </x-modal>

    {{-- Modal Edit Kos --}}
    <x-modal show="modalEditKos" title="Edit Data Kos">
        <form :action="editKosUrl" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            @method('PUT')

            <div x-data="{
            open: false,
            search: '',
            selectedId: '',
            mitras: @js($mitrasJson),
            init() {
                this.$watch('editKosData.mitra_id', val => {
                    this.selectedId = val;
                    const m = this.mitras.find(i => i.id == val);
                    this.search = m ? m.nama + ' (' + (m.no_hp !== '-' ? m.no_hp : m.email) + ')' : '';
                });
                if (this.editKosData.mitra_id) {
                    this.selectedId = this.editKosData.mitra_id;
                    const m = this.mitras.find(i => i.id == this.editKosData.mitra_id);
                    this.search = m ? m.nama + ' (' + (m.no_hp !== '-' ? m.no_hp : m.email) + ')' : '';
                }
            },
            get filtered() {
                if (!this.search) return this.mitras;
                const q = this.search.toLowerCase();
                return this.mitras.filter(m => (m.nama && m.nama.toLowerCase().includes(q)) || (m.no_hp && m.no_hp.includes(q)) || (m.email && m.email.toLowerCase().includes(q)));
            },
            select(mitra) {
                this.selectedId = mitra.id;
                this.search = mitra.nama + ' (' + (mitra.no_hp !== '-' ? mitra.no_hp : mitra.email) + ')';
                this.open = false;
            }
        }" class="relative">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pemilik / Mitra Kos <span class="text-red-500">*</span></label>
                <input type="text"
                    x-model="search"
                    @focus="open = true"
                    @click.away="open = false"
                    @input="open = true; selectedId = ''"
                    placeholder="Ketik nama atau no. hp mitra..."
                    required
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                <input type="hidden" name="mitra_id" :value="selectedId" required>

                <div x-show="open && filtered.length > 0" x-transition class="absolute left-0 right-0 top-full mt-1 max-h-48 overflow-y-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl z-50 p-1 space-y-1">
                    <template x-for="item in filtered" :key="item.id">
                        <div @click="select(item)" class="p-2 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 rounded-lg cursor-pointer text-xs flex justify-between items-center transition-all">
                            <div class="font-bold text-gray-900 dark:text-white" x-text="item.nama"></div>
                            <div class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400" x-text="'📞 ' + (item.no_hp !== '-' ? item.no_hp : item.email)"></div>
                        </div>
                    </template>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Kos <span class="text-red-500">*</span></label>
                <input type="text" name="nama" x-model="editKosData.nama" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Ganti Foto Kos (Opsional)</label>
                <input type="file" name="foto" accept="image/*" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
                <p class="text-[10px] text-gray-400 mt-0.5 italic">* Biarkan kosong jika tidak ingin mengubah foto kos saat ini.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Alamat Lengkap <span class="text-red-500">*</span></label>
                <textarea name="alamat" x-model="editKosData.alamat" rows="2" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Link Google Maps <span class="text-red-500">*</span></label>
                <input type="text" name="link_gmaps" x-model="editKosData.link_gmaps" required placeholder="https://maps.google.com/..." class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Bank Pembayaran <span class="text-red-500">*</span></label>
                    <input type="text" name="bank" x-model="editKosData.bank" required placeholder="Contoh: BCA / Mandiri / GoPay" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">No. Rekening <span class="text-red-500">*</span></label>
                    <input type="text" name="no_rekening" x-model="editKosData.no_rekening" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Pemilik Rekening <span class="text-red-500">*</span></label>
                <input type="text" name="nama_pemilik_rekening" x-model="editKosData.nama_pemilik_rekening" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <x-btn type="button" variant="secondary" size="sm" @click="modalEditKos = false">Batal</x-btn>
                <x-btn type="submit" variant="primary" size="sm">Update Kos</x-btn>
            </div>
        </form>
    </x-modal>

    {{-- Modal Detail Foto Kos --}}
    <x-modal show="showImageModal" title="Detail Foto Kos">
        <div class="space-y-3 text-center">
            <p class="text-xs font-bold text-gray-700 dark:text-gray-300" x-text="previewImageTitle"></p>
            <div class="relative w-full max-h-[70vh] overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800 bg-black flex items-center justify-center">
                <img :src="previewImageUrl" :alt="previewImageTitle" class="max-w-full max-h-[70vh] object-contain rounded-xl">
            </div>
            <div class="pt-2 flex justify-end">
                <x-btn type="button" variant="secondary" size="sm" @click="showImageModal = false">Tutup</x-btn>
            </div>
        </div>
    </x-modal>

    {{-- Modal Pendaftaran Kamar Baru --}}
    <x-modal show="modalKamar" title="Daftarkan Kamar Baru">
        <form action="{{ route($p . 'kamar.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3" x-data="{ kamarTipe: 'standar', kapasitas: 1, displayHargaBulan: '', rawHargaBulan: '', displayHargaMinggu: '', rawHargaMinggu: '', displayHargaHari: '', rawHargaHari: '' }">
            @csrf

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Upload Foto Kamar (Opsional, Bisa Pilih Beberapa)</label>
                <input type="file" name="foto[]" multiple accept="image/*" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
                <p class="text-[10px] text-gray-400 mt-0.5 italic">* Pilih beberapa file gambar (JPG, PNG, WEBP) untuk foto galeri kamar ini.</p>
            </div>

            <div x-data="{
            open: false,
            search: '',
            selectedId: '',
            kosList: @js($kosListJson),
            init() {
                if (typeof selectedKosIdForKamar !== 'undefined' && selectedKosIdForKamar) {
                    this.selectedId = selectedKosIdForKamar;
                    const k = this.kosList.find(i => i.id == selectedKosIdForKamar);
                    this.search = k ? k.nama : '';
                }
            },
            get filtered() {
                if (!this.search) return this.kosList;
                const q = this.search.toLowerCase();
                return this.kosList.filter(k => k.nama && k.nama.toLowerCase().includes(q));
            },
            select(kos) {
                this.selectedId = kos.id;
                this.search = kos.nama;
                selectedKosIdForKamar = kos.id;
                this.open = false;
            }
        }" class="relative">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pilih Kos <span class="text-red-500">*</span></label>
                <input type="text"
                    x-model="search"
                    @focus="open = true"
                    @click.away="open = false"
                    @input="open = true; selectedId = ''"
                    placeholder="Ketik nama kos..."
                    required
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                <input type="hidden" name="kos_id" :value="selectedId" required>

                <div x-show="open && filtered.length > 0" x-transition class="absolute left-0 right-0 top-full mt-1 max-h-48 overflow-y-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl z-50 p-1 space-y-1">
                    <template x-for="item in filtered" :key="item.id">
                        <div @click="select(item)" class="p-2 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 rounded-lg cursor-pointer text-xs font-bold text-gray-900 dark:text-white transition-all" x-text="item.nama"></div>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5 items-end">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate">Kode Kamar <span class="text-red-500">*</span></label>
                    <input type="text" name="kode_kamar" required placeholder="A01 / K01" class="w-full h-9 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate">Jenis Kamar <span class="text-red-500">*</span></label>
                    <select name="tipe" x-model="kamarTipe" @change="kapasitas = (kamarTipe === 'berbagi' ? 2 : 1)" required class="w-full h-9 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                        <option value="standar">Standar (1 Orang)</option>
                        <option value="berbagi">Berbagi (2 Orang)</option>
                    </select>
                </div>
            </div>

            {{-- Checklist Fasilitas / Perabotan --}}
            <div x-data="{
            facilities: [
                { name: 'Kasur', icon: '🛏️' },
                { name: 'Meja', icon: '🪑' },
                { name: 'Kipas', icon: '🪭' },
                { name: 'Lemari', icon: '🗄️' },
                { name: 'Kamar Mandi Dalam', icon: '🚿' },
                { name: 'AC', icon: '❄️' },
                { name: 'Wifi', icon: '📶' },
                { name: 'Dapur Bersama', icon: '🍳' }
            ],
            selected: [],
            customDetail: '',
            get finalDetail() {
                if (this.selected.length === 0 && !this.customDetail.trim()) {
                    return 'Kosong';
                }
                let list = [...this.selected];
                if (this.customDetail.trim()) {
                    list.push(this.customDetail.trim());
                }
                return list.join(', ');
            },
            toggle(name) {
                if (this.selected.includes(name)) {
                    this.selected = this.selected.filter(i => i !== name);
                } else {
                    this.selected.push(name);
                }
            }
        }" class="space-y-2">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                    Detail Perabotan & Fasilitas Kamar
                </label>

                {{-- Grid Checklist --}}
                <div class="grid grid-cols-2 sm:grid-cols-2 gap-1.5">
                    <template x-for="item in facilities" :key="item.name">
                        <div @click="toggle(item.name)"
                            :class="selected.includes(item.name) ? 'bg-amber-50 dark:bg-amber-950/50 border-amber-400 dark:border-amber-600 text-amber-900 dark:text-amber-200 font-bold shadow-xs' : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
                            class="p-2 rounded-xl border text-xs flex items-center gap-1.5 cursor-pointer transition-all select-none">
                            <input type="checkbox" :checked="selected.includes(item.name)" class="w-3.5 h-3.5 rounded text-amber-600 focus:ring-amber-500 pointer-events-none">
                            <span x-text="item.icon" class="text-sm"></span>
                            <span x-text="item.name" class="truncate text-[11px]"></span>
                        </div>
                    </template>
                </div>

                {{-- Input Teks Tambahan --}}
                <div>
                    <input type="text"
                        x-model="customDetail"
                        placeholder="Fasilitas tambahan lainnya (misal: TV 32 Inch)..."
                        class="w-full px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
                </div>

                <input type="hidden" name="detail" :value="finalDetail">
                <p class="text-[10px] text-gray-400 italic">* Jika tidak ada yang dicentang dan input tambahan kosong, perabotan otomatis tertulis "Kosong".</p>
            </div>

            <div class="grid grid-cols-3 gap-2 items-end">
                <div>
                    <label class="block text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate" title="Harga per Bulan">Harga/Bulan <span class="text-red-500">*</span></label>
                    <input type="text"
                        x-model="displayHargaBulan"
                        @input="displayHargaBulan = formatRupiah($event.target.value); rawHargaBulan = parseDigits($event.target.value)"
                        placeholder="Rp 1.000.000"
                        required
                        class="w-full h-9 px-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                    <input type="hidden" name="harga_per_bulan" :value="rawHargaBulan">
                </div>
                <div>
                    <label class="block text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate" title="Harga per Minggu">Harga/Minggu</label>
                    <input type="text"
                        x-model="displayHargaMinggu"
                        @input="displayHargaMinggu = formatRupiah($event.target.value); rawHargaMinggu = parseDigits($event.target.value)"
                        placeholder="Rp 300.000"
                        class="w-full h-9 px-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                    <input type="hidden" name="harga_per_minggu" :value="rawHargaMinggu">
                </div>
                <div>
                    <label class="block text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate" title="Harga per Hari">Harga/Hari</label>
                    <input type="text"
                        x-model="displayHargaHari"
                        @input="displayHargaHari = formatRupiah($event.target.value); rawHargaHari = parseDigits($event.target.value)"
                        placeholder="Rp 100.000"
                        class="w-full h-9 px-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                    <input type="hidden" name="harga_per_hari" :value="rawHargaHari">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Batas Kapasitas Penghuni</label>
                <input type="number" name="kapasitas" x-model="kapasitas" readonly required class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-500 dark:text-gray-400 cursor-not-allowed select-none">
                <p class="text-[10px] text-gray-400 mt-1 italic">* Otomatis terisi 1 orang untuk Standar & 2 orang untuk Berbagi (tidak dapat diubah manual)</p>
            </div>

            <div class="grid grid-cols-1 gap-2 pt-1 border-t border-gray-100 dark:border-gray-800">
                <div>
                    <label class="block text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">Target ID Grup WA (Fonnte) <span class="text-red-500">*</span></label>
                    <input type="text" name="wa_group_id" required placeholder="120363xxx@g.us" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">Link Join Grup WA Kamar <span class="text-red-500">*</span></label>
                    <input type="url" name="link_grup_wa" required placeholder="https://chat.whatsapp.com/..." class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-xs text-gray-900 dark:text-white">
                </div>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <x-btn type="button" variant="secondary" size="sm" @click="modalKamar = false">Batal</x-btn>
                <x-btn type="submit" variant="primary" size="sm">Simpan Kamar</x-btn>
            </div>
        </form>
    </x-modal>

    {{-- Modal Edit Kamar --}}
    <x-modal show="modalEditKamar" title="Edit Data Kamar">
        <form :action="editKamarUrl" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Tambah Foto Kamar Baru (Opsional)</label>
                <input type="file" name="foto[]" multiple accept="image/*" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
                <p class="text-[10px] text-gray-400 mt-0.5 italic">* Foto baru yang diunggah akan ditambahkan ke galeri foto kamar saat ini.</p>
            </div>

            <div x-data="{
            open: false,
            search: '',
            selectedId: '',
            kosList: @js($kosListJson),
            init() {
                this.$watch('editKamarData.kos_id', val => {
                    this.selectedId = val;
                    const k = this.kosList.find(i => i.id == val);
                    this.search = k ? k.nama : '';
                });
                if (this.editKamarData.kos_id) {
                    this.selectedId = this.editKamarData.kos_id;
                    const k = this.kosList.find(i => i.id == this.editKamarData.kos_id);
                    this.search = k ? k.nama : '';
                }
            },
            get filtered() {
                if (!this.search) return this.kosList;
                const q = this.search.toLowerCase();
                return this.kosList.filter(k => k.nama && k.nama.toLowerCase().includes(q));
            },
            select(kos) {
                this.selectedId = kos.id;
                this.search = kos.nama;
                this.editKamarData.kos_id = kos.id;
                this.open = false;
            }
        }" class="relative">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pilih Kos <span class="text-red-500">*</span></label>
                <input type="text"
                    x-model="search"
                    @focus="open = true"
                    @click.away="open = false"
                    @input="open = true; selectedId = ''"
                    placeholder="Ketik nama kos..."
                    required
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                <input type="hidden" name="kos_id" :value="selectedId" required>

                <div x-show="open && filtered.length > 0" x-transition class="absolute left-0 right-0 top-full mt-1 max-h-48 overflow-y-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl z-50 p-1 space-y-1">
                    <template x-for="item in filtered" :key="item.id">
                        <div @click="select(item)" class="p-2 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 rounded-lg cursor-pointer text-xs font-bold text-gray-900 dark:text-white transition-all" x-text="item.nama"></div>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5 items-end">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate">Kode Kamar <span class="text-red-500">*</span></label>
                    <input type="text" name="kode_kamar" x-model="editKamarData.kode_kamar" required class="w-full h-9 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate">Jenis Kamar <span class="text-red-500">*</span></label>
                    <select name="tipe" x-model="editKamarData.tipe" @change="editKamarData.kapasitas = (editKamarData.tipe === 'berbagi' ? 2 : 1)" required class="w-full h-9 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                        <option value="standar">Standar (1 Orang)</option>
                        <option value="berbagi">Berbagi (2 Orang)</option>
                    </select>
                </div>
            </div>

            {{-- Checklist Fasilitas / Perabotan --}}
            <div x-data="{
            facilities: [
                { name: 'Kasur', icon: '🛏️' },
                { name: 'Meja', icon: '🪑' },
                { name: 'Kipas', icon: '🪭' },
                { name: 'Lemari', icon: '🗄️' },
                { name: 'Kamar Mandi Dalam', icon: '🚿' },
                { name: 'AC', icon: '❄️' },
                { name: 'Wifi', icon: '📶' },
                { name: 'Dapur Bersama', icon: '🍳' }
            ],
            selected: [],
            customDetail: '',
            init() {
                this.$watch('modalEditKamar', val => {
                    if (val) {
                        this.parseDetail(this.editKamarData.detail);
                    }
                });
                this.$watch('editKamarData.detail', val => {
                    this.parseDetail(val);
                });
                if (this.editKamarData && this.editKamarData.detail) {
                    this.parseDetail(this.editKamarData.detail);
                }
            },
            parseDetail(val) {
                if (!val || val === 'Kosong') {
                    this.selected = [];
                    this.customDetail = '';
                    return;
                }
                const parts = val.split(',').map(s => s.trim()).filter(Boolean);
                const selectedList = [];
                const customList = [];

                const matchFacility = (part) => {
                    const lower = part.toLowerCase();
                    if (lower.includes('kasur') || lower.includes('bed') || lower.includes('matras')) return 'Kasur';
                    if (lower.includes('lemari') || lower.includes('wardrobe') || lower.includes('kabinet')) return 'Lemari';
                    if (lower.includes('meja') || lower.includes('kursi') || lower.includes('desk')) return 'Meja';
                    if (lower.includes('kipas') || lower.includes('fan')) return 'Kipas';
                    if (lower.includes('mandi') || lower.includes('toilet') || lower.includes('wc')) return 'Kamar Mandi Dalam';
                    if (lower.includes('ac') || lower.includes('pendingin')) return 'AC';
                    if (lower.includes('wifi') || lower.includes('internet')) return 'Wifi';
                    if (lower.includes('dapur') || lower.includes('masak')) return 'Dapur Bersama';
                    return null;
                };

                parts.forEach(part => {
                    const matched = matchFacility(part);
                    if (matched) {
                        if (!selectedList.includes(matched)) {
                            selectedList.push(matched);
                        }
                    } else {
                        customList.push(part);
                    }
                });

                this.selected = selectedList;
                this.customDetail = customList.join(', ');
            },
            get finalDetail() {
                if (this.selected.length === 0 && !this.customDetail.trim()) {
                    return 'Kosong';
                }
                let list = [...this.selected];
                if (this.customDetail.trim()) {
                    list.push(this.customDetail.trim());
                }
                return list.join(', ');
            },
            toggle(name) {
                if (this.selected.includes(name)) {
                    this.selected = this.selected.filter(i => i !== name);
                } else {
                    this.selected.push(name);
                }
            }
        }" class="space-y-2">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                    Detail Perabotan & Fasilitas Kamar
                </label>

                {{-- Grid Checklist --}}
                <div class="grid grid-cols-2 sm:grid-cols-2 gap-1.5">
                    <template x-for="item in facilities" :key="item.name">
                        <div @click="toggle(item.name)"
                            :class="selected.includes(item.name) ? 'bg-amber-50 dark:bg-amber-950/50 border-amber-400 dark:border-amber-600 text-amber-900 dark:text-amber-200 font-bold shadow-xs' : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
                            class="p-2 rounded-xl border text-xs flex items-center gap-1.5 cursor-pointer transition-all select-none">
                            <input type="checkbox" :checked="selected.includes(item.name)" class="w-3.5 h-3.5 rounded text-amber-600 focus:ring-amber-500 pointer-events-none">
                            <span x-text="item.icon" class="text-sm"></span>
                            <span x-text="item.name" class="truncate text-[11px]"></span>
                        </div>
                    </template>
                </div>

                {{-- Input Teks Tambahan --}}
                <div>
                    <input type="text"
                        x-model="customDetail"
                        placeholder="Fasilitas tambahan lainnya (misal: TV 32 Inch)..."
                        class="w-full px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
                </div>

                <input type="hidden" name="detail" :value="finalDetail">
                <p class="text-[10px] text-gray-400 italic">* Jika tidak ada yang dicentang dan input tambahan kosong, perabotan otomatis tertulis "Kosong".</p>
            </div>

            <div class="grid grid-cols-3 gap-2 items-end">
                <div>
                    <label class="block text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate" title="Harga per Bulan">Harga/Bulan <span class="text-red-500">*</span></label>
                    <input type="text"
                        x-model="editKamarData.display_harga_per_bulan"
                        @input="editKamarData.display_harga_per_bulan = formatRupiah($event.target.value); editKamarData.harga_per_bulan = parseDigits($event.target.value)"
                        placeholder="Rp 1.000.000"
                        required
                        class="w-full h-9 px-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                    <input type="hidden" name="harga_per_bulan" :value="editKamarData.harga_per_bulan">
                </div>
                <div>
                    <label class="block text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate" title="Harga per Minggu">Harga/Minggu</label>
                    <input type="text"
                        x-model="editKamarData.display_harga_per_minggu"
                        @input="editKamarData.display_harga_per_minggu = formatRupiah($event.target.value); editKamarData.harga_per_minggu = parseDigits($event.target.value)"
                        placeholder="Rp 300.000"
                        class="w-full h-9 px-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                    <input type="hidden" name="harga_per_minggu" :value="editKamarData.harga_per_minggu">
                </div>
                <div>
                    <label class="block text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate" title="Harga per Hari">Harga/Hari</label>
                    <input type="text"
                        x-model="editKamarData.display_harga_per_hari"
                        @input="editKamarData.display_harga_per_hari = formatRupiah($event.target.value); editKamarData.harga_per_hari = parseDigits($event.target.value)"
                        placeholder="Rp 100.000"
                        class="w-full h-9 px-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                    <input type="hidden" name="harga_per_hari" :value="editKamarData.harga_per_hari">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Batas Kapasitas Penghuni</label>
                <input type="number" name="kapasitas" x-model="editKamarData.kapasitas" readonly required class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-500 dark:text-gray-400 cursor-not-allowed select-none">
                <p class="text-[10px] text-gray-400 mt-1 italic">* Otomatis terisi 1 orang untuk Standar & 2 orang untuk Berbagi (tidak dapat diubah manual)</p>
            </div>

            <div class="grid grid-cols-1 gap-2 pt-1 border-t border-gray-100 dark:border-gray-800">
                <div>
                    <label class="block text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">Target ID Grup WA (Fonnte) <span class="text-red-500">*</span></label>
                    <input type="text" name="wa_group_id" x-model="editKamarData.wa_group_id" required placeholder="120363xxx@g.us" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">Link Join Grup WA Kamar <span class="text-red-500">*</span></label>
                    <input type="url" name="link_grup_wa" x-model="editKamarData.link_grup_wa" required placeholder="https://chat.whatsapp.com/..." class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-xs text-gray-900 dark:text-white">
                </div>
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
        <form action="{{ route($p . 'penghuni.daftar') }}" method="POST" class="space-y-3.5" x-data="{ durasiSewa: 'bulanan' }">
            @csrf

            {{-- 1. Pilih Kamar --}}
            <div x-data="{
            open: false,
            search: '',
            selectedId: '',
            kamars: @js($allKamarsJson),
            init() {
                this.$watch('selectedKamarIdForPenghuni', (id) => {
                    this.syncSelectedKamar(id);
                });
                this.$watch('modalPenghuni', (isOpen) => {
                    if (isOpen) {
                        this.syncSelectedKamar(selectedKamarIdForPenghuni);
                    } else {
                        this.open = false;
                    }
                });
                if (selectedKamarIdForPenghuni) {
                    this.syncSelectedKamar(selectedKamarIdForPenghuni);
                }
            },
            syncSelectedKamar(id) {
                if (id) {
                    const found = this.kamars.find(k => k.id == id);
                    if (found) {
                        this.selectedId = found.id;
                        this.search = found.kode_kamar + ' (' + found.kos_nama + ')';
                        selectedKamarTipe = found.tipe;
                        return;
                    }
                }
                this.selectedId = '';
                this.search = '';
            },
            get filtered() {
                if (!this.search) return this.kamars;
                const q = this.search.toLowerCase();
                return this.kamars.filter(k => (k.kode_kamar && k.kode_kamar.toLowerCase().includes(q)) || (k.kos_nama && k.kos_nama.toLowerCase().includes(q)));
            },
            select(km) {
                if (km.isFull) return;
                this.selectedId = km.id;
                this.search = km.kode_kamar + ' (' + km.kos_nama + ')';
                selectedKamarIdForPenghuni = km.id;
                selectedKamarTipe = km.tipe;
                this.open = false;
            }
        }" class="relative">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        Pilih Kamar Kos <span class="text-red-500">*</span>
                    </label>
                    <span class="text-[10px] text-gray-400 dark:text-gray-500 italic">Khusus Kamar Kosong</span>
                </div>
                <div class="relative">
                    <input type="text"
                        x-model="search"
                        @focus="open = true"
                        @click.away="open = false"
                        @input="open = true; selectedId = ''; selectedKamarIdForPenghuni = ''"
                        placeholder="Ketik kode kamar (misal: A01) atau nama kos..."
                        required
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                    <input type="hidden" name="kamar_id" :value="selectedId" required>
                </div>

                {{-- Dropdown Hasil Pencarian Kamar --}}
                <div x-show="open && filtered.length > 0" x-transition class="absolute left-0 right-0 top-full mt-1 max-h-48 overflow-y-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl z-50 p-1 space-y-1">
                    <template x-for="item in filtered" :key="item.id">
                        <div @click="select(item)"
                            :class="item.isFull ? 'opacity-50 cursor-not-allowed bg-gray-100 dark:bg-gray-800/50' : 'hover:bg-emerald-50 dark:hover:bg-emerald-950/40 cursor-pointer'"
                            class="p-2 rounded-lg text-xs flex justify-between items-center transition-all">
                            <div>
                                <span class="font-bold font-mono text-gray-900 dark:text-white" x-text="'Kode: ' + item.kode_kamar"></span>
                                <span class="text-gray-500 dark:text-gray-400 ml-1.5" x-text="item.kos_nama + ' (' + item.tipe + ')'"></span>
                            </div>
                            <div class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                                :class="item.isFull ? 'bg-red-100 text-red-600 dark:bg-red-900/40' : 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40'"
                                x-text="item.isFull ? 'TERISI' : 'TERSEDIA'"></div>
                        </div>
                    </template>
                </div>

                {{-- Badge Tipe Kamar Terpilih --}}
                <div class="mt-1.5 px-2.5 py-1 rounded-lg border text-[11px] flex items-center justify-between font-medium"
                    :class="selectedKamarTipe === 'berbagi' ? 'bg-purple-50 dark:bg-purple-950/30 border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300' : 'bg-blue-50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300'">
                    <span class="font-bold uppercase tracking-wider text-[10px]" x-text="selectedKamarTipe === 'berbagi' ? '👥 TIPE BERBAGI' : '👤 TIPE STANDAR'"></span>
                    <span x-text="selectedKamarTipe === 'berbagi' ? 'Wajib minimal 2 orang (maks 3)' : 'Kapasitas 1 orang'"></span>
                </div>
            </div>

            {{-- 2. Penghuni 1 (Wajib) --}}
            <div x-data="{
            open: false,
            search: '',
            selectedId: '',
            users: @js($penghuniUsersJson),
            get filtered() {
                if (!this.search) return this.users;
                const q = this.search.toLowerCase();
                return this.users.filter(u => (u.nama && u.nama.toLowerCase().includes(q)) || (u.no_hp && u.no_hp.includes(q)) || (u.email && u.email.toLowerCase().includes(q)));
            },
            select(u) {
                if (u.isDisabled) return;
                this.selectedId = u.id;
                this.search = u.nama + ' (' + (u.no_hp !== '-' ? u.no_hp : u.email) + ')';
                this.open = false;
            }
        }" class="relative">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                    Pilih Penghuni 1 <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="text"
                        x-model="search"
                        @focus="open = true"
                        @click.away="open = false"
                        @input="open = true; selectedId = ''"
                        placeholder="Ketik nama atau no. hp anak kos..."
                        required
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                    <input type="hidden" name="penghuni_id" :value="selectedId" required>
                </div>

                {{-- Dropdown Hasil Pencarian Penghuni 1 --}}
                <div x-show="open && filtered.length > 0" x-transition class="absolute left-0 right-0 top-full mt-1 max-h-48 overflow-y-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl z-50 p-1 space-y-1">
                    <template x-for="item in filtered" :key="item.id">
                        <div @click="select(item)"
                            :class="item.isDisabled ? 'opacity-50 cursor-not-allowed bg-gray-100 dark:bg-gray-800/50' : 'hover:bg-emerald-50 dark:hover:bg-emerald-950/40 cursor-pointer'"
                            class="p-2 rounded-lg text-xs flex justify-between items-center transition-all">
                            <div>
                                <span class="font-bold text-gray-900 dark:text-white" x-text="item.nama"></span>
                                <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-mono ml-1.5" x-text="'📞 ' + (item.no_hp !== '-' ? item.no_hp : item.email)"></span>
                            </div>
                            <div class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300" x-text="item.statusTag"></div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- 3. Rekan Sekamar (Khusus Kamar Tipe Berbagi) --}}
            <div x-show="selectedKamarTipe === 'berbagi'" x-transition class="p-3 bg-purple-50/40 dark:bg-purple-950/20 border border-purple-200/80 dark:border-purple-800/50 rounded-2xl space-y-3">
                <div class="flex items-center gap-1.5 text-xs font-bold text-purple-800 dark:text-purple-300">
                    <span>👥 Rekan Sekamar Berbagi</span>
                </div>

                {{-- Penghuni 2 (Wajib Jika Tipe Berbagi) --}}
                <div x-data="{
                open: false,
                search: '',
                selectedId: '',
                users: @js($penghuniUsersJson),
                get filtered() {
                    if (!this.search) return this.users;
                    const q = this.search.toLowerCase();
                    return this.users.filter(u => (u.nama && u.nama.toLowerCase().includes(q)) || (u.no_hp && u.no_hp.includes(q)) || (u.email && u.email.toLowerCase().includes(q)));
                },
                select(u) {
                    if (u.isDisabled) return;
                    this.selectedId = u.id;
                    this.search = u.nama + ' (' + (u.no_hp !== '-' ? u.no_hp : u.email) + ')';
                    this.open = false;
                }
            }" class="relative">
                    <label class="block text-xs font-bold text-purple-800 dark:text-purple-300 uppercase tracking-wider mb-1">
                        Pilih Penghuni 2 <span class="text-red-500">* (Wajib)</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                            x-model="search"
                            @focus="open = true"
                            @click.away="open = false"
                            @input="open = true; selectedId = ''"
                            placeholder="Ketik nama atau no. hp anak kos ke-2..."
                            :required="selectedKamarTipe === 'berbagi'"
                            class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-xl text-xs font-semibold text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                        <input type="hidden" name="penghuni_id_2" :value="selectedId" :required="selectedKamarTipe === 'berbagi'">
                    </div>

                    <div x-show="open && filtered.length > 0" x-transition class="absolute left-0 right-0 top-full mt-1 max-h-48 overflow-y-auto bg-white dark:bg-gray-900 border border-purple-200 dark:border-purple-800 rounded-xl shadow-xl z-50 p-1 space-y-1">
                        <template x-for="item in filtered" :key="item.id">
                            <div @click="select(item)"
                                :class="item.isDisabled ? 'opacity-50 cursor-not-allowed bg-gray-100 dark:bg-gray-800/50' : 'hover:bg-purple-50 dark:hover:bg-purple-950/40 cursor-pointer'"
                                class="p-2 rounded-lg text-xs flex justify-between items-center transition-all">
                                <div>
                                    <span class="font-bold text-gray-900 dark:text-white" x-text="item.nama"></span>
                                    <span class="text-[11px] text-purple-600 dark:text-purple-400 font-mono ml-1.5" x-text="'📞 ' + (item.no_hp !== '-' ? item.no_hp : item.email)"></span>
                                </div>
                                <div class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300" x-text="item.statusTag"></div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Penghuni 3 (Opsional) --}}
                <div x-data="{
                open: false,
                search: '',
                selectedId: '',
                users: @js($penghuniUsersJson),
                get filtered() {
                    if (!this.search) return this.users;
                    const q = this.search.toLowerCase();
                    return this.users.filter(u => (u.nama && u.nama.toLowerCase().includes(q)) || (u.no_hp && u.no_hp.includes(q)) || (u.email && u.email.toLowerCase().includes(q)));
                },
                select(u) {
                    if (u.isDisabled) return;
                    this.selectedId = u.id;
                    this.search = u.nama + ' (' + (u.no_hp !== '-' ? u.no_hp : u.email) + ')';
                    this.open = false;
                }
            }" class="relative">
                    <label class="block text-xs font-bold text-purple-800 dark:text-purple-300 uppercase tracking-wider mb-1">
                        Pilih Penghuni 3 <span class="text-gray-500 dark:text-gray-400 font-normal">(Opsional - Maks 3 Orang)</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                            x-model="search"
                            @focus="open = true"
                            @click.away="open = false"
                            @input="open = true; selectedId = ''"
                            placeholder="Ketik nama atau no. hp anak kos ke-3..."
                            class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-xl text-xs font-semibold text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                        <input type="hidden" name="penghuni_id_3" :value="selectedId">
                    </div>

                    <div x-show="open && filtered.length > 0" x-transition class="absolute left-0 right-0 top-full mt-1 max-h-48 overflow-y-auto bg-white dark:bg-gray-900 border border-purple-200 dark:border-purple-800 rounded-xl shadow-xl z-50 p-1 space-y-1">
                        <template x-for="item in filtered" :key="item.id">
                            <div @click="select(item)"
                                :class="item.isDisabled ? 'opacity-50 cursor-not-allowed bg-gray-100 dark:bg-gray-800/50' : 'hover:bg-purple-50 dark:hover:bg-purple-950/40 cursor-pointer'"
                                class="p-2 rounded-lg text-xs flex justify-between items-center transition-all">
                                <div>
                                    <span class="font-bold text-gray-900 dark:text-white" x-text="item.nama"></span>
                                    <span class="text-[11px] text-purple-600 dark:text-purple-400 font-mono ml-1.5" x-text="'📞 ' + (item.no_hp !== '-' ? item.no_hp : item.email)"></span>
                                </div>
                                <div class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300" x-text="item.statusTag"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- 4. Durasi Sewa --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Durasi Sewa <span class="text-red-500">*</span></label>
                <select name="durasi" x-model="durasiSewa" required class="w-full max-w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                    <option value="bulanan">Bulanan (Auto 30 Hari)</option>
                    <option value="mingguan">Mingguan (Auto 7 Hari)</option>
                    <option value="harian">Harian (Tentukan Selesai)</option>
                </select>
            </div>

            {{-- 5. Tanggal Masuk --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Tanggal Masuk <span class="text-red-500">*</span></label>
                <input type="date" name="tanggal_masuk" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
            </div>

            {{-- 5. Tanggal Selesai (Khusus Harian) --}}
            <div x-show="durasiSewa === 'harian'" x-transition class="space-y-1">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                    Tanggal Selesai / Jatuh Tempo <span class="text-red-500">*</span>
                </label>
                <input type="date" name="tanggal_keluar" :required="durasiSewa === 'harian'" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                <p class="text-[10px] text-gray-400 dark:text-gray-500 italic">* Tentukan tanggal selesai untuk sewa harian.</p>
            </div>

            {{-- 6. Tombol Aksi --}}
            <div class="pt-2.5 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                <x-btn type="button" variant="secondary" size="sm" @click="modalPenghuni = false">Batal</x-btn>
                <x-btn type="submit" variant="primary" size="sm">Daftarkan Penghuni</x-btn>
            </div>
        </form>
    </x-modal>
</div>
@endsection