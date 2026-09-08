@extends('layouts.app')

@php
$isSuperAdmin = request()->is('superadmin*');
$p = $isSuperAdmin ? 'superadmin.' : 'admin.';

$penghuniUsers = $penghuniUsers ?? collect();
$allKamars = $kosList->flatMap->kamar;

$mitrasJson = $mitras->filter(fn($m) => empty($m->is_pro))->map(function($m) {
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

$allKamarsJson = $kosList->flatMap(function($k) {
return $k->kamar->map(function($km) use ($k) {
$km->setRelation('kos', $k);
$isFull = $km->status === 'terisi';
return [
'id' => $km->id,
'kode_kamar' => $km->kode_kamar,
'kos_nama' => $k->nama ?? 'Kos',
'tipe' => $km->tipe,
'status' => $km->status,
'isFull' => $isFull,
];
});
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
        <button @click="modalKos = true" class="py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs active:scale-95 transition-all text-center truncate">
            Tambah Kos
        </button>

        <button @click="selectedKosIdForKamar = ''; modalKamar = true" class="py-2.5 px-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs active:scale-95 transition-all text-center truncate">
            Tambah Kamar
        </button>

        <button @click="selectedKamarIdForPenghuni = ''; selectedKamarTipe = 'standar'; modalPenghuni = true" class="py-2.5 px-3 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-xl shadow-xs active:scale-95 transition-all text-center truncate">
            Tambah Penghuni
        </button>
    </div>

    {{-- Filter Bar: Search, Kos, Tipe Kamar, Tipe Sewa & Status Masa Aktif --}}
    @if(!$kosList->isEmpty())
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-800">
            <label class="block text-[11px] py-0.5 font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                Filter & Pencarian Kos / Kamar
            </label>
            <button type="button"
                @click="filterKosId = 'all'; filterTipeKamar = 'all'; filterMasaAktif = 'all'; filterTipeSewa = 'all'; search = ''"
                x-show="filterKosId !== 'all' || filterTipeKamar !== 'all' || filterMasaAktif !== 'all' || filterTipeSewa !== 'all' || search !== ''"
                class="text-[10px] font-bold text-red-600 dark:text-red-400 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-900/60 px-2.5 py-0.5 rounded-lg transition-all">
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
                    <option value="bulan">Sewa Bulanan</option>
                    <option value="minggu">Sewa Mingguan</option>
                    <option value="hari">Sewa Harian</option>
                </select>
            </div>

            {{-- Filter Status Masa Aktif / Jatuh Tempo --}}
            <div>
                <label class="block text-[10px] font-semibold text-gray-500 dark:text-gray-400 mb-0.5">Status Masa Aktif:</label>
                <select x-model="filterMasaAktif" class="w-full py-1.5 px-2 bg-gray-50 dark:bg-gray-800 border border-amber-200 dark:border-amber-800/60 rounded-xl text-xs font-bold text-gray-900 dark:text-white focus:ring-amber-500">
                    <option value="all">-- Semua Status Sewa --</option>
                    <option value="expired">Jatuh Tempo / Masa Aktif Habis</option>
                    <option value="aktif">Masih Aktif (Belum Jatuh Tempo)</option>
                    <option value="kosong">Kamar Kosong</option>
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
    $activeDurations = $activePenghunis->pluck('durasi')->map(fn($d) => strtolower($d))->unique()->values()->toArray();

    return [
    'id' => $k->id,
    'kode_kamar' => (string)$k->kode_kamar,
    'tipe' => (string)$k->tipe,
    'statusMasaAktif' => $statusMasaAktif,
    'durasiList' => $activeDurations,
    'hasBulan' => in_array('bulanan', $activeDurations) || in_array('bulan', $activeDurations),
    'hasMinggu' => in_array('mingguan', $activeDurations) || in_array('minggu', $activeDurations),
    'hasHari' => in_array('harian', $activeDurations) || in_array('hari', $activeDurations),
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
        $kosongCount = $kos->kamar->where('status', 'kosong')->count();
        $expiredCount = collect($kamarFilterArray)->where('statusMasaAktif', 'expired')->count();
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
                <div class="absolute bottom-2.5 right-2.5 px-2.5 py-1 bg-black/60 backdrop-blur-md rounded-lg text-[10px] font-medium text-white flex items-center gap-1.5 shadow-sm group-hover:bg-emerald-600 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                    </svg>
                    <span>Perbesar Foto</span>
                </div>
            </div>
            @endif

            {{-- Header Kos Card --}}
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40 space-y-3">
                {{-- Baris 1: Nama Kos & Badge Kamar di Kiri, Dropdown Aksi di Kanan --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1 space-y-1.5">
                        <h3 class="font-bold text-base text-gray-900 dark:text-white leading-snug break-words">{{ $kos->nama }}</h3>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                {{ $kos->kamar->count() }} Kamar ({{ $kosongCount }} kosong)
                            </span>
                            @if($expiredCount > 0)
                            <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold rounded-md bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300 border border-red-200 dark:border-red-800">
                                {{ $expiredCount }} jatuh tempo
                            </span>
                            @endif
                        </div>
                    </div>

                    {{-- Dropdown Aksi Kos (Edit, Hapus) --}}
                    <div x-data="{ openKosDropdown: false }" class="relative flex-shrink-0" @click.outside="openKosDropdown = false">
                        <button type="button"
                            @click="openKosDropdown = !openKosDropdown"
                            class="p-1.5 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 border border-gray-200/80 dark:border-gray-700 shadow-2xs transition-colors cursor-pointer"
                            title="Menu Aksi Kos">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>

                        <div x-show="openKosDropdown"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-1 w-44 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-30"
                            x-cloak>

                            {{-- Tombol Edit Kos --}}
                            <button type="button"
                                @click="openKosDropdown = false; openEditKosModal(@js($kos))"
                                class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-gray-700 dark:text-gray-200 hover:bg-amber-50 dark:hover:bg-amber-950/50 hover:text-amber-700 dark:hover:text-amber-300 transition-colors cursor-pointer text-left">
                                <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                <span>Edit Kos</span>
                            </button>

                            {{-- Tombol Tambah Kamar --}}
                            <button type="button"
                                @click="openKosDropdown = false; selectedKosIdForKamar = '{{ $kos->id }}'; modalKamar = true"
                                class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:text-blue-700 dark:hover:text-blue-300 transition-colors cursor-pointer text-left">
                                <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Tambah Kamar</span>
                            </button>

                            <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>

                            {{-- Tombol Hapus Kos --}}
                            <form action="{{ route($p . 'kos.destroy', $kos->slug ?? $kos->id) }}" method="POST" class="m-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Kos {{ addslashes($kos->nama) }}? Seluruh kamar dan data di dalamnya juga akan terhapus.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 transition-colors cursor-pointer text-left">
                                    <svg class="w-3.5 h-3.5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Hapus Kos</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Baris 2: Alamat & Rekening --}}
                <div class="flex flex-col gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-1.5">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="leading-relaxed">{{ $kos->alamat ?? 'Alamat tidak diisi' }}</span>
                    </div>

                    @if($kos->bank && $kos->no_rekening)
                    <div class="flex items-center gap-1.5 text-[11px] font-mono text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>{{ $kos->bank }} <strong class="text-gray-800 dark:text-gray-200">{{ $kos->no_rekening }}</strong> (a.n {{ $kos->nama_pemilik_rekening ?? '-' }})</span>
                    </div>
                    @endif
                </div>

                {{-- Baris 3: Info Mitra Pemilik (Khusus Admin / Super Admin) --}}
                @if($kos->mitra)
                <div class="pt-2 border-t border-gray-200/60 dark:border-gray-700/60 space-y-1">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Mitra Pemilik:</span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ $kos->mitra->nama ?? '-' }}</span>
                    </div>
                    @if($kos->mitra->no_hp)
                    @php
                    $waUrl = \App\Services\WhatsAppService::generateMitraUrl($kos->mitra, $kos);
                    @endphp
                    <div class="flex items-center gap-2 text-[11px]">
                        <a href="{{ $waUrl }}"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 hover:underline font-bold group"
                            title="Chat WhatsApp ke Mitra {{ $kos->mitra->nama }}">
                            <svg class="w-3.5 h-3.5 fill-current text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform flex-shrink-0" viewBox="0 0 24 24">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                            </svg>
                            <span>WhatsApp</span>
                        </a>
                        <span class="text-gray-300 dark:text-gray-600 font-bold">/</span>
                        <a href="tel:{{ $kos->mitra->no_hp }}"
                            class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 hover:underline font-bold group"
                            title="Telepon Mitra">
                            <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span>Telepon ({{ $kos->mitra->no_hp }})</span>
                        </a>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            {{-- Rooms List in this Kos --}}
            <div class="p-3">
                @if($kos->kamar->isEmpty())
                <div class="p-3 text-center">
                    <p class="text-xs text-gray-400">Belum ada kamar di kos ini.</p>
                    <button @click="selectedKosIdForKamar = '{{ $kos->id }}'; modalKamar = true" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 mt-1 inline-block">
                        Tambah Kamar
                    </button>
                </div>
                @else
                <div class="grid grid-cols-1 gap-3">
                    @foreach($kos->kamar as $kIndex => $kamar)
                    @php
                    $kamarMeta = $kamarFilterArray[$kIndex] ?? [];
                    $activePenghunis = $kamar->penghuniKamar ? $kamar->penghuniKamar->where('status', 'aktif') : collect();
                    $isTerisi = $kamar->status === 'terisi' || $activePenghunis->isNotEmpty();

                    $hasExpiredPenghuni = $activePenghunis->contains(function($pk) use ($todayDate) {
                    return $pk->tanggal_keluar && \Carbon\Carbon::parse($pk->tanggal_keluar)->setTime(14, 0, 0)->isPast();
                    });
                    $kamarOverdueDays = $hasExpiredPenghuni ? ($activePenghunis->filter(function($pk) {
                    return $pk->tanggal_keluar && \Carbon\Carbon::parse($pk->tanggal_keluar)->setTime(14, 0, 0)->isPast();
                    })->map(function($pk) {
                    return max(1, (int) \Carbon\Carbon::parse($pk->tanggal_keluar)->setTime(14, 0, 0)->diffInDays(now()));
                    })->max() ?: 0) : 0;
                    @endphp

                    <div x-show="matchKamar(@js($kamarMeta), @js($kosSearchText))"
                        x-transition
                        class="p-3.5 sm:p-4 rounded-xl border {{ $hasExpiredPenghuni ? 'bg-red-50/30 dark:bg-red-950/20 border-red-200 dark:border-red-900/40' : ($isTerisi ? 'bg-emerald-50/20 dark:bg-emerald-950/10 border-emerald-200/70 dark:border-emerald-900/40' : 'bg-gray-50/50 dark:bg-gray-800/30 border-gray-200/80 dark:border-gray-800') }} space-y-3 shadow-2xs">

                        {{-- Baris Header Kamar & Dropdown Aksi --}}
                        <div class="flex items-start justify-between gap-2 border-b border-gray-200/60 dark:border-gray-700/60 pb-2.5">
                            {{-- Info Kamar (Kode Kamar + Icon WA, Tipe, Status) --}}
                            <div class="flex items-center gap-1.5 flex-wrap min-w-0">
                                @if($kamar->link_grup_wa)
                                <a href="{{ $kamar->link_grup_wa }}" target="_blank"
                                    class="font-bold text-xs font-mono text-emerald-800 dark:text-emerald-200 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/80 px-2.5 py-0.5 rounded-md border border-emerald-300 dark:border-emerald-800 shadow-2xs inline-flex items-center gap-1.5 transition-all active:scale-95 group"
                                    title="Buka Grup WhatsApp Kamar {{ $kamar->kode_kamar }}">
                                    <span>Kamar {{ $kamar->kode_kamar }}</span>
                                    <svg class="w-3.5 h-3.5 fill-current text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                                    </svg>
                                </a>
                                @else
                                <span class="font-bold text-xs font-mono text-gray-900 dark:text-white bg-white dark:bg-gray-900 px-2.5 py-0.5 rounded-md border border-gray-200 dark:border-gray-700 shadow-2xs">
                                    Kamar {{ $kamar->kode_kamar }}
                                </span>
                                @endif

                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-md {{ $kamar->tipe === 'berbagi' ? 'bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800' : 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800' }}">
                                    {{ ucfirst($kamar->tipe) }}
                                </span>
                                @if($hasExpiredPenghuni)
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300 border border-red-200 dark:border-red-800 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Jatuh Tempo ({{ $kamarOverdueDays }} Hari)</span>
                                </span>
                                @elseif($isTerisi)
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    Terisi ({{ $activePenghunis->count() }}/{{ $kamar->kapasitas }})
                                </span>
                                @else
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    Kosong
                                </span>
                                @endif
                            </div>

                            {{-- Dropdown Aksi (Detail, Edit, Hapus) --}}
                            <div x-data="{ openDropdown: false }" class="relative flex-shrink-0" @click.outside="openDropdown = false">
                                <button type="button"
                                    @click="openDropdown = !openDropdown"
                                    class="p-1.5 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 border border-gray-200/80 dark:border-gray-700 shadow-2xs transition-colors cursor-pointer"
                                    title="Menu Aksi Kamar">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                    </svg>
                                </button>

                                <div x-show="openDropdown"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute right-0 mt-1 w-32 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-30"
                                    x-cloak>
                                    <a href="{{ route($p . 'kamar.show', $kamar->kode_kamar ?? $kamar->id) }}"
                                        class="flex items-center gap-2 px-3 py-1.5 text-xs text-gray-700 dark:text-gray-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors">
                                        <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Detail</span>
                                    </a>
                                    <button type="button"
                                        @click="openDropdown = false; openEditKamarModal(@js($kamar))"
                                        class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:text-blue-700 dark:hover:text-blue-300 transition-colors cursor-pointer text-left">
                                        <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        <span>Edit</span>
                                    </button>
                                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                    <form action="{{ route($p . 'kamar.destroy', $kamar->kode_kamar ?? $kamar->id) }}" method="POST" class="m-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Kamar {{ addslashes($kamar->kode_kamar) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 transition-colors cursor-pointer text-left">
                                            <svg class="w-3.5 h-3.5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Hapus</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Fasilitas / Perabotan (List Kiri Kanan) --}}
                        @php
                        $detailsList = array_filter(array_map('trim', explode(',', $kamar->detail ?? '')));
                        @endphp
                        <div class="space-y-1.5">
                            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block">Fasilitas:</span>
                            @if(empty($detailsList) || (count($detailsList) === 1 && strtolower(trim($detailsList[0])) === 'kosong'))
                            <span class="text-[11px] text-gray-400 dark:text-gray-500 italic block">
                                Tanpa perabotan / kosong
                            </span>
                            @else
                            <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-gray-700 dark:text-gray-300">
                                @foreach($detailsList as $item)
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="truncate text-[11px] font-medium">{{ $item }}</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        {{-- Rincian Biaya (Grid 3 Box Rapi) --}}
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                {{-- Bulanan --}}
                                <div class="flex items-center justify-between sm:flex-col sm:items-start px-2.5 py-1.5 rounded-lg bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-800/60">
                                    <span class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-400">Bulanan</span>
                                    <span class="text-xs font-bold text-emerald-800 dark:text-emerald-200">
                                        Rp {{ number_format($kamar->harga_per_bulan, 0, ',', '.') }}
                                    </span>
                                </div>

                                {{-- Mingguan --}}
                                <div class="flex items-center justify-between sm:flex-col sm:items-start px-2.5 py-1.5 rounded-lg bg-purple-50/70 dark:bg-purple-950/40 border border-purple-200/80 dark:border-purple-800/60">
                                    <span class="text-[10px] font-semibold text-purple-700 dark:text-purple-400">Mingguan</span>
                                    <span class="text-xs font-bold text-purple-800 dark:text-purple-200">
                                        {{ $kamar->harga_per_minggu ? 'Rp ' . number_format($kamar->harga_per_minggu, 0, ',', '.') : '-' }}
                                    </span>
                                </div>

                                {{-- Harian --}}
                                <div class="flex items-center justify-between sm:flex-col sm:items-start px-2.5 py-1.5 rounded-lg bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-800/60">
                                    <span class="text-[10px] font-semibold text-blue-700 dark:text-blue-400">Harian</span>
                                    <span class="text-xs font-bold text-blue-800 dark:text-blue-200">
                                        {{ $kamar->harga_per_hari ? 'Rp ' . number_format($kamar->harga_per_hari, 0, ',', '.') : '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Penghuni Aktif --}}
                        @if($activePenghunis->isNotEmpty())
                        @php
                        $kamarPaymentStatuses = $activePenghunis->map(function($pk) use ($kamar) {
                            return $pk->getStatusPembayaranInfo($kamar);
                        });
                        $kamarMainPaymentStatus = null;
                        if ($kamarPaymentStatuses->isNotEmpty()) {
                            if ($kamarPaymentStatuses->contains(function($s) { return $s['status'] === 'belum_bayar_awal'; })) {
                                $kamarMainPaymentStatus = [
                                    'label' => 'Belum Bayar Biaya Awal',
                                    'badge_class' => 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300 border border-red-200 dark:border-red-900/50'
                                ];
                            } elseif ($kamarPaymentStatuses->contains(function($s) { return ($s['unpaid_type'] ?? null) === 'roommate'; })) {
                                $kamarMainPaymentStatus = [
                                    'label' => 'Sudah Membayar (1/2)',
                                    'badge_class' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-900/50'
                                ];
                            } elseif ($kamarPaymentStatuses->contains(function($s) { return $s['status'] === 'belum_bayar_perpanjangan'; })) {
                                $kamarMainPaymentStatus = [
                                    'label' => 'Belum Bayar Perpanjangan',
                                    'badge_class' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-900/50'
                                ];
                            } else {
                                $kamarMainPaymentStatus = [
                                    'label' => 'Lunas',
                                    'badge_class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900/50'
                                ];
                            }
                        }
                        @endphp
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700/80 space-y-2">
                            <div class="flex items-center justify-between text-xs font-semibold text-gray-600 dark:text-gray-400">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span>Penghuni Aktif ({{ $activePenghunis->count() }}/{{ $kamar->kapasitas }})</span>
                                </div>
                                @if($kamarMainPaymentStatus)
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg {{ $kamarMainPaymentStatus['badge_class'] }}">
                                    {{ $kamarMainPaymentStatus['label'] }}
                                </span>
                                @endif
                            </div>

                            @foreach($activePenghunis as $pk)
                            @php
                            $targetKeluar = $pk->tanggal_keluar ? \Carbon\Carbon::parse($pk->tanggal_keluar)->setTime(14, 0, 0) : null;
                            $isPkExpired = $targetKeluar && $targetKeluar->isPast();
                            $overdueDays = $isPkExpired ? max(1, (int) $targetKeluar->diffInDays(now())) : 0;
                            $tglKeluarStr = $pk->tanggal_keluar ? \Carbon\Carbon::parse($pk->tanggal_keluar)->format('d M Y') : '-';
                            @endphp
                            <div class="p-3 sm:p-3.5 rounded-2xl border bg-gray-50/70 dark:bg-gray-800/50 border-gray-200/80 dark:border-gray-700/70 space-y-2.5 shadow-2xs">
                                {{-- Baris 1: Profil Penghuni (Avatar, Nama, No HP) & Tombol WhatsApp --}}
                                <div class="flex items-center justify-between gap-2.5">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center flex-shrink-0 text-xs font-extrabold shadow-2xs">
                                            {{ strtoupper(substr($pk->penghuni->nama ?? 'P', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-gray-900 dark:text-white text-xs truncate leading-tight" title="{{ $pk->penghuni->nama ?? '-' }}">
                                                {{ $pk->penghuni->nama ?? '-' }}
                                            </p>
                                            <p class="text-[10px] text-gray-500 dark:text-gray-400 font-mono mt-0.5 truncate">
                                                {{ $pk->penghuni->no_hp ?? '-' }}
                                            </p>
                                        </div>
                                    </div>

                                    @if($pk->penghuni && $pk->penghuni->no_hp)
                                    @php
                                    $waUrl = \App\Services\WhatsAppService::generatePenghuniUrl($pk->penghuni, $pk, null, $kamar, $kos);
                                    @endphp
                                    <a href="{{ $waUrl }}" target="_blank"
                                        class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[10px] font-bold flex items-center gap-1.5 flex-shrink-0 active:scale-95 transition-all shadow-xs"
                                        title="Chat WhatsApp ke {{ $pk->penghuni->nama }}">
                                        <svg class="w-3 h-3 fill-current text-white" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                                        </svg>
                                        <span>WhatsApp</span>
                                    </a>
                                    @endif
                                </div>

                                {{-- Baris 2: Tanggal Periode Sewa & Durasi --}}
                                <div class="pt-2 border-t border-gray-200/60 dark:border-gray-700/60 flex items-center justify-between text-[11px] text-gray-600 dark:text-gray-400 flex-wrap gap-1">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="font-medium">
                                            {{ $pk->tanggal_masuk ? \Carbon\Carbon::parse($pk->tanggal_masuk)->format('d M Y') : '-' }} s/d {{ $tglKeluarStr }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-gray-200/70 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ ucfirst($pk->durasi) }} · Batas Checkout 14.00 WIB
                                    </span>
                                </div>
                            </div>
                            @endforeach

                            {{-- Action Buttons at bottom of Occupied Room --}}
                            <div class="pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center gap-2">
                                @if($hasExpiredPenghuni)
                                @php
                                $pengumumanRoute = route($p . 'pengumuman.create', ['kamar_id' => $kamar->id]);
                                @endphp
                                <a href="{{ $pengumumanRoute }}"
                                    class="flex-1 min-w-0 px-2.5 py-1.5 text-[10px] font-semibold text-amber-800 dark:text-amber-200 bg-amber-100 hover:bg-amber-200 dark:bg-amber-950/80 dark:hover:bg-amber-900 rounded-xl border border-amber-300 dark:border-amber-800 inline-flex items-center justify-center gap-1.5 active:scale-95 transition-all text-center shadow-2xs"
                                    title="Kirim Pengumuman Jatuh Tempo ke Kamar {{ $kamar->kode_kamar }}">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0 text-amber-700 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                    </svg>
                                    <span class="truncate">Pengumuman Jatuh Tempo</span>
                                </a>
                                @endif

                                <form action="{{ route($p . 'kamar.kosongkan', $kamar->kode_kamar ?? $kamar->id) }}" method="POST" onsubmit="return confirm('Kosongkan Kamar {{ $kamar->kode_kamar }} dan selesaikan sewa penghuni?')" class="{{ $hasExpiredPenghuni ? 'flex-1 min-w-0' : 'w-full' }}">
                                    @csrf
                                    <button type="submit" class="w-full px-2.5 py-1.5 text-[10px] font-semibold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-300 rounded-xl border border-red-200 dark:border-red-900/50 inline-flex items-center justify-center gap-1.5 active:scale-95 transition-all text-center shadow-2xs">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        <span class="truncate">Kosongkan Kamar</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                        @else
                        {{-- Empty Room State --}}
                        <div class="mt-3 pt-3 border-t border-dashed border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Belum Ada Penghuni</span>
                            <button @click="
                                        selectedKamarIdForPenghuni = '{{ $kamar->id }}'; 
                                        selectedKamarTipe = '{{ $kamar->tipe }}'; 
                                        modalPenghuni = true;
                                    "
                                class="px-2.5 py-1 bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/50 dark:hover:bg-purple-900/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800 font-semibold text-xs rounded-lg transition-all active:scale-95">
                                Tambah Penghuni
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
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Upload Foto Kamar</label>
                <input type="file" name="foto[]" multiple accept="image/*" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
                <p class="text-[10px] text-gray-400 mt-0.5 italic">* Pilih beberapa file gambar (JPG, PNG, WEBP) untuk foto galeri kamar ini.</p>
            </div>

            <div x-data="{
            open: false,
            search: '',
            selectedId: '',
            kosList: @js($kosListJson),
            init() {
                this.$watch('selectedKosIdForKamar', (id) => {
                    this.syncSelectedKos(id);
                });
                this.$watch('modalKamar', (isOpen) => {
                    if (isOpen) {
                        this.syncSelectedKos(selectedKosIdForKamar);
                    } else {
                        this.open = false;
                        selectedKosIdForKamar = '';
                    }
                });
                if (selectedKosIdForKamar) {
                    this.syncSelectedKos(selectedKosIdForKamar);
                }
            },
            syncSelectedKos(id) {
                if (id) {
                    const found = this.kosList.find(k => k.id == id);
                    if (found) {
                        this.selectedId = found.id;
                        this.search = found.nama;
                        return;
                    }
                }
                this.selectedId = '';
                this.search = '';
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
                <x-btn type="button" variant="secondary" size="sm" @click="modalKamar = false; selectedKosIdForKamar = ''">Batal</x-btn>
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