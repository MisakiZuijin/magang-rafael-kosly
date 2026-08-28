@extends('layouts.app')

@php
$isSuperAdmin = request()->is('superadmin*');
$p = $isSuperAdmin ? 'superadmin.' : 'admin.';

$listBank = [
'Bank BCA',
'Bank BRI',
'Bank Mandiri',
'Bank BNI',
'Bank BSI (Bank Syariah Indonesia)',
'Bank BTN',
'Bank CIMB Niaga',
'Bank Permata',
'Bank Danamon',
'Bank Mega',
'Bank OCBC NISP',
'Bank Panin',
'Bank Maybank Indonesia',
'Bank Sinarmas',
'Bank BTPN / Jenius',
'Bank Jago',
'SeaBank Indonesia',
'Blu by BCA Digital',
'Allo Bank Indonesia',
'Bank Neo Commerce (BNC)',
'Bank DKI',
'Bank BJB',
'Bank Jateng',
'Bank Jatim',
'Bank Nagari',
'Bank Sumut',
'Bank Kalbar',
'Bank Kaltimtara',
'Bank Sulselbar',
'Bank Papua',
'DANA (E-Wallet)',
'OVO (E-Wallet)',
'GoPay (E-Wallet)',
'ShopeePay (E-Wallet)',
'LinkAja (E-Wallet)',
'BCA',
'BRI',
'Mandiri',
'BNI',
'BSI',
'BTN',
];
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
    editKosData: { id: '', mitra_id: '', nama: '', alamat: '', latitude: '', longitude: '', bank: '', no_rekening: '', nama_pemilik_rekening: '' },
    editKosUrl: '',
    editKamarData: { id: '', kos_id: '', kode_kamar: '', tipe: 'standar', detail: '', harga_per_bulan: '', display_harga_per_bulan: '', harga_per_minggu: '', display_harga_per_minggu: '', harga_per_hari: '', display_harga_per_hari: '', kapasitas: 1, wa_group_id: '', link_grup_wa: '' },
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

        <button @click="modalPenghuni = true" class="flex flex-col items-center justify-center p-3 bg-purple-600 hover:bg-purple-700 text-white rounded-2xl shadow-sm active:scale-95 transition-all text-center">
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
    $hasExpiredPenghuni = $activePenghunis->contains(function($pk) use ($todayDate) {
    return $pk->tanggal_keluar && \Carbon\Carbon::parse($pk->tanggal_keluar)->startOfDay()->lessThanOrEqualTo($todayDate);
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

            <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
                <div class="flex justify-between items-start gap-2 mb-1">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 flex-wrap mb-0.5">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                                Mitra: {{ $kos->mitra->nama ?? '-' }}
                            </span>
                            @if($kos->mitra && $kos->mitra->no_hp)
                            @php
                            $cleanHp = preg_replace('/[^0-9]/', '', $kos->mitra->no_hp);
                            $waHp = str_starts_with($cleanHp, '0') ? '62' . substr($cleanHp, 1) : $cleanHp;
                            @endphp
                            <div class="inline-flex items-center gap-1">
                                <a href="https://wa.me/{{ $waHp }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 dark:hover:bg-emerald-800/50 border border-emerald-200 dark:border-emerald-800 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 rounded-md transition-all active:scale-95"
                                    title="Kirim WhatsApp ke Mitra">
                                    <span class="[&>svg]:h-4 [&>svg]:w-4">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="currentColor"
                                            viewBox="0 0 448 512">
                                            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
                                            <path
                                                d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                                        </svg>
                                    </span>
                                    {{ $kos->mitra->no_hp }}
                                </a>
                                <a href="tel:{{ $kos->mitra->no_hp }}"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 dark:hover:bg-blue-800/50 border border-blue-200 dark:border-blue-800 text-[10px] font-bold text-blue-700 dark:text-blue-300 rounded-md transition-all active:scale-95"
                                    title="Telepon Langsung Mitra">
                                    📞 Telepon
                                </a>
                            </div>
                            @endif
                        </div>
                        <h3 class="font-bold text-base text-gray-900 dark:text-white leading-snug truncate">{{ $kos->nama }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $kos->alamat ?? 'Alamat tidak diisi' }}</p>
                    </div>

                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                            {{ $kos->kamar->count() }} Kamar
                        </span>
                        <button type="button"
                            @click="openEditKosModal(@js($kos))"
                            class="px-2 py-1 text-[10px] font-bold text-amber-700 dark:text-amber-300 bg-amber-100 hover:bg-amber-200 dark:hover:bg-amber-800/50 dark:bg-amber-900/40 dark:hover:bg-amber-900/60 rounded-lg transition-all gap-0.5 active:scale-95">
                            <span class="text-center">Edit</span>
                        </button>
                    </div>
                </div>

                @if($kos->bank && $kos->no_rekening)
                <p class="text-[11px] font-mono text-gray-400 mt-2 truncate">
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
                <div class="grid grid-cols-1 gap-4">
                    @foreach($kos->kamar as $kIndex => $kamar)
                    @php
                    $kamarMeta = $kamarFilterArray[$kIndex] ?? [];
                    $activePenghunis = $kamar->penghuniKamar ? $kamar->penghuniKamar->where('status', 'aktif') : collect();
                    $isTerisi = $kamar->status === 'terisi' || $activePenghunis->isNotEmpty();

                    $hasExpiredPenghuni = $activePenghunis->contains(function($pk) use ($todayDate) {
                    return $pk->tanggal_keluar && \Carbon\Carbon::parse($pk->tanggal_keluar)->startOfDay()->lessThanOrEqualTo($todayDate);
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

                            <div class="grid grid-cols-2 items-center gap-2">
                                <a href="{{ route($p . 'kamar.show', $kamar->id) }}"
                                    class="px-1.5 py-0.5 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-100/90 hover:bg-emerald-200 dark:hover:bg-emerald-800/50 dark:bg-emerald-900/50 rounded-md transition-all gap-0.5 active:scale-95">
                                    <span class="text-center">Detail</span>
                                </a>
                                <button type="button"
                                    @click="openEditKamarModal(@js($kamar))"
                                    class="px-1.5 py-0.5 text-[10px] font-bold text-blue-700 dark:text-blue-300 bg-blue-100/90 hover:bg-blue-200 dark:hover:bg-blue-800/50 dark:bg-blue-900/50 rounded-md transition-all gap-0.5 active:scale-95">
                                    <span class="text-center">Edit</span>
                                </button>
                            </div>
                        </div>

                        {{-- Detail Perabotan / Fasilitas --}}
                        <div class="text-[11px] font-medium text-gray-700 dark:text-gray-300 bg-white/80 dark:bg-gray-900/60 p-2 sm:p-2.5 rounded-xl border border-gray-200/70 dark:border-gray-700/60 grid grid-cols-[auto_1fr] items-start gap-1.5">
                            <span class="font-bold text-amber-600 dark:text-amber-400">📦 Perabotan:</span>
                            @if(!empty($kamar->detail))
                            <span class="text-gray-900 dark:text-white font-semibold truncate">{{ $kamar->detail }}</span>
                            @else
                            <span class="text-gray-400 italic font-mono">Kosong (Tanpa Perabotan)</span>
                            @endif
                        </div>

                        {{-- Rincian Biaya & Link WA Group --}}
                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] items-center gap-1.5 text-[10px] font-mono bg-white/60 dark:bg-gray-900/40 p-2 rounded-xl border border-gray-200/50 dark:border-gray-800">
                            <div class="grid grid-flow-col auto-cols-max items-center gap-1.5 text-[10px]">
                                <span class="text-emerald-700 dark:text-emerald-300 font-bold bg-emerald-50 dark:bg-emerald-950/40 px-1.5 py-0.5 rounded">
                                    Rp {{ number_format($kamar->harga_per_bulan, 0, ',', '.') }}/bln
                                </span>
                                @if($kamar->harga_per_minggu)
                                <span class="text-purple-600 dark:text-purple-400 font-bold bg-purple-50 dark:bg-purple-950/40 px-1.5 py-0.5 rounded">
                                    Rp {{ number_format($kamar->harga_per_minggu, 0, ',', '.') }}/minggu
                                </span>
                                @endif
                                @if($kamar->harga_per_hari)
                                <span class="text-blue-600 dark:text-blue-400 font-bold bg-blue-50 dark:bg-blue-950/40 px-1.5 py-0.5 rounded">
                                    Rp {{ number_format($kamar->harga_per_hari, 0, ',', '.') }}/hari
                                </span>
                                @endif
                            </div>

                            @if($kamar->link_grup_wa)
                            <a href="{{ $kamar->link_grup_wa }}" target="_blank" class="text-[9px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-100/90 dark:bg-emerald-900/50 px-2 py-0.5 rounded-md hover:underline grid grid-flow-col auto-cols-max items-center gap-1 justify-self-start sm:justify-self-auto">
                                <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400 fill-current" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                                </svg>
                                <span>WhatsApp Group</span>
                            </a>
                            @elseif($kamar->wa_group_id)
                            <span class="text-[9px] font-mono text-gray-400 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded justify-self-start sm:justify-self-auto" title="ID Grup Fonnte: {{ $kamar->wa_group_id }}">
                                WhatsApp Group Registered
                            </span>
                            @endif
                        </div>

                        {{-- Penghuni --}}
                        @if($activePenghunis->isNotEmpty())
                        <div class="mt-2 pt-2 border-t border-emerald-200/60 dark:border-emerald-900/40 space-y-1.5">
                            <div class="grid grid-cols-1 items-center text-[10px] font-bold text-emerald-700 dark:text-emerald-400">
                                <span>👥 PENGHUNI AKTIF ({{ $activePenghunis->count() }}/{{ $kamar->kapasitas }})</span>
                            </div>

                            @foreach($activePenghunis as $pk)
                            @php
                            $isPkExpired = $pk->tanggal_keluar && \Carbon\Carbon::parse($pk->tanggal_keluar)->startOfDay()->lessThanOrEqualTo($todayDate);
                            $tglKeluarStr = $pk->tanggal_keluar ? \Carbon\Carbon::parse($pk->tanggal_keluar)->format('d M Y') : '-';
                            $pkDaysLeft = $pk->tanggal_keluar ? round(now()->diffInDays(\Carbon\Carbon::parse($pk->tanggal_keluar)->startOfDay(), false)) : null;
                            $isPkDueSoon = $isPkExpired || ($pkDaysLeft !== null && $pkDaysLeft <= 3);
                                @endphp
                                <div class="grid grid-cols-[1fr_auto] items-center text-xs p-2 rounded-xl border {{ $isPkExpired ? 'bg-red-100/70 dark:bg-red-950/40 border-red-200 dark:border-red-900/60' : 'bg-white/90 dark:bg-gray-900/80 border-emerald-100 dark:border-emerald-900/30' }}">
                                <div class="truncate">
                                    <span class="font-bold text-gray-900 dark:text-white block truncate text-[11px]">
                                        {{ $pk->penghuni->nama ?? '-' }}
                                    </span>
                                    <span class="text-[9px] font-mono text-gray-500">
                                        Masuk: {{ $pk->tanggal_masuk ? \Carbon\Carbon::parse($pk->tanggal_masuk)->format('d M Y') : '-' }}
                                    </span>
                                </div>

                                <div class="text-right flex items-center gap-1.5 justify-end">
                                    <div>
                                        <span class="px-2 py-0.5 text-[9px] font-bold rounded-md {{ $isPkExpired ? 'bg-red-200 text-red-800 dark:bg-red-900/80 dark:text-red-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' }}">
                                            {{ $isPkExpired ? '⚠️ Jatuh Tempo (' . $tglKeluarStr . ')' : 'Sewa s/d ' . $tglKeluarStr }}
                                        </span>
                                        <span class="text-[9px] text-gray-400 font-mono mt-0.5 block">
                                            {{ ucfirst($pk->durasi) }}
                                        </span>
                                    </div>

                                    @if($isPkDueSoon)
                                    @php
                                    $singlePengumumanRoute = route($p . 'pengumuman.create', ['kamar_id' => $kamar->id]);
                                    @endphp
                                    <a href="{{ $singlePengumumanRoute }}" title="Kirim Pengumuman Jatuh Tempo ke Kamar {{ $kamar->kode_kamar }}" class="p-1 rounded-md bg-amber-100 hover:bg-amber-200 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-800 transition-all active:scale-95 flex-shrink-0">
                                        <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16v-5.5A3.5 3.5 0 0 1 14.5 7H18v9h-3.5a3.5 3.5 0 0 1-3.5-3.5ZM6 8h2v8H6V8Zm-2 2h2v4H4v-4Z" />
                                        </svg>
                                    </a>
                                    @endif
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

                            <form action="{{ route($p . 'kamar.kosongkan', $kamar->id) }}" method="POST" onsubmit="return confirm('Kosongkan Kamar {{ $kamar->kode_kamar }} dan selesaikan sewa penghuni?')">
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

        <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pemilik / Mitra Kos</label>
            <select name="mitra_id" required class="w-full max-w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white truncate" style="max-width: 100%; box-sizing: border-box;">
                <option value="">-- Pilih Mitra --</option>
                @foreach($mitras as $mitra)
                <option value="{{ $mitra->id }}">{{ \Illuminate\Support\Str::limit($mitra->nama, 20) }} ({{ \Illuminate\Support\Str::limit($mitra->email, 22) }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Kos</label>
            <input type="text" name="nama" required placeholder="Contoh: Kos Mawar Asri" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Foto Kos (Opsional)</label>
            <input type="file" name="foto" accept="image/*" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
            <p class="text-[10px] text-gray-400 mt-0.5 italic">* Format gambar: JPG, PNG, WEBP. Maks 3MB.</p>
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
                <select name="bank" class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white focus:ring-emerald-500">
                    <option value="">-- Pilih Bank / E-Wallet --</option>
                    @foreach($listBank as $b)
                    <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>
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
    <form :action="editKosUrl" method="POST" enctype="multipart/form-data" class="space-y-3">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pemilik / Mitra Kos</label>
            <select name="mitra_id" x-model="editKosData.mitra_id" required class="w-full max-w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white truncate" style="max-width: 100%; box-sizing: border-box;">
                <option value="">-- Pilih Mitra --</option>
                @foreach($mitras as $mitra)
                <option value="{{ $mitra->id }}">{{ \Illuminate\Support\Str::limit($mitra->nama, 20) }} ({{ \Illuminate\Support\Str::limit($mitra->email, 22) }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Kos</label>
            <input type="text" name="nama" x-model="editKosData.nama" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Ganti Foto Kos (Opsional)</label>
            <input type="file" name="foto" accept="image/*" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
            <p class="text-[10px] text-gray-400 mt-0.5 italic">* Biarkan kosong jika tidak ingin mengubah foto kos saat ini.</p>
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
                <select name="bank" x-model="editKosData.bank" class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white focus:ring-emerald-500">
                    <option value="">-- Pilih Bank / E-Wallet --</option>
                    @foreach($listBank as $b)
                    <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>
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

        <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pilih Kos</label>
            <select name="kos_id" x-model="selectedKosIdForKamar" required class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                <option value="">-- Pilih Kos --</option>
                @foreach($kosList as $k)
                <option value="{{ $k->id }}">{{ $k->nama }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-2.5 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate">Kode Kamar</label>
                <input type="text" name="kode_kamar" required placeholder="A01 / K01" class="w-full h-9 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate">Jenis Kamar</label>
                <select name="tipe" x-model="kamarTipe" @change="kapasitas = (kamarTipe === 'berbagi' ? 2 : 1)" required class="w-full h-9 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                    <option value="standar">Standar (1 Orang)</option>
                    <option value="berbagi">Berbagi (2 Orang)</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Detail Perabotan / Fasilitas Kamar (Opsional)</label>
            <textarea name="detail" rows="2" placeholder="Contoh: Kasur Springbed 160x200, Lemari 2 Pintu, Meja Belajar, AC (Biarkan kosong jika tanpa perabotan)" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white"></textarea>
            <p class="text-[10px] text-gray-400 mt-0.5 italic">* Jika dikosongkan, detail perabotan kamar akan otomatis tertulis "Kosong".</p>
        </div>

        <div class="grid grid-cols-3 gap-2 items-end">
            <div>
                <label class="block text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate" title="Harga per Bulan">Harga/Bulan</label>
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
                <label class="block text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">Target ID Grup WA (Fonnte)</label>
                <input type="text" name="wa_group_id" placeholder="120363xxx@g.us" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">Link Join Grup WA Kamar</label>
                <input type="url" name="link_grup_wa" placeholder="https://chat.whatsapp.com/..." class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-xs text-gray-900 dark:text-white">
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

        <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pilih Kos</label>
            <select name="kos_id" x-model="editKamarData.kos_id" required class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                <option value="">-- Pilih Kos --</option>
                @foreach($kosList as $k)
                <option value="{{ $k->id }}">{{ $k->nama }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-2.5 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate">Kode Kamar</label>
                <input type="text" name="kode_kamar" x-model="editKamarData.kode_kamar" required class="w-full h-9 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate">Jenis Kamar</label>
                <select name="tipe" x-model="editKamarData.tipe" @change="editKamarData.kapasitas = (editKamarData.tipe === 'berbagi' ? 2 : 1)" required class="w-full h-9 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                    <option value="standar">Standar (1 Orang)</option>
                    <option value="berbagi">Berbagi (2 Orang)</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Detail Perabotan / Fasilitas Kamar (Opsional)</label>
            <textarea name="detail" x-model="editKamarData.detail" rows="2" placeholder="Contoh: Kasur Springbed 160x200, Lemari 2 Pintu, Meja Belajar, AC (Biarkan kosong jika tanpa perabotan)" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white"></textarea>
            <p class="text-[10px] text-gray-400 mt-0.5 italic">* Jika dikosongkan, detail perabotan kamar akan otomatis tertulis "Kosong".</p>
        </div>

        <div class="grid grid-cols-3 gap-2 items-end">
            <div>
                <label class="block text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1 whitespace-nowrap truncate" title="Harga per Bulan">Harga/Bulan</label>
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
                <label class="block text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">Target ID Grup WA (Fonnte)</label>
                <input type="text" name="wa_group_id" x-model="editKamarData.wa_group_id" placeholder="120363xxx@g.us" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">Link Join Grup WA Kamar</label>
                <input type="url" name="link_grup_wa" x-model="editKamarData.link_grup_wa" placeholder="https://chat.whatsapp.com/..." class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-xs text-gray-900 dark:text-white">
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
    <form action="{{ route($p . 'penghuni.daftar') }}" method="POST" class="space-y-3" x-data="{ durasiSewa: 'bulanan' }">
        @csrf

        {{-- Pilih Kamar (Kamar Terisi Ditandai Disabled) --}}
        <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pilih Kamar Kos (Hanya Kamar Kosong)</label>
            <select id="select-kamar-penghuni"
                name="kamar_id"
                x-model="selectedKamarIdForPenghuni"
                @change="updateKamarTipe()"
                required
                class="w-full max-w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white truncate focus:ring-emerald-500">
                <option value="" data-tipe="standar">-- Pilih Kamar Kos --</option>
                @foreach($allKamars as $km)
                @php
                $isFull = $km->status === 'terisi';
                @endphp
                <option value="{{ $km->id }}"
                    data-tipe="{{ $km->tipe }}"
                    {{ $isFull ? 'disabled' : '' }}
                    class="{{ $isFull ? 'text-gray-400 bg-gray-100 dark:bg-gray-800' : 'text-gray-900 dark:text-white font-bold' }}">
                    Kamar {{ $km->kode_kamar }} · {{ $km->kos->nama ?? 'Kos' }} ({{ ucfirst($km->tipe) }}) {{ $isFull ? '[TERISI]' : '[TERSEDIA]' }}
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
            <select name="penghuni_id" required class="w-full max-w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white truncate focus:ring-emerald-500" style="max-width: 100%; width: 100%; box-sizing: border-box;">
                <option value="">-- Pilih Anak Kos Ke-1 --</option>
                @foreach($penghuniUsers as $pu)
                @php
                $activePk = $pu->penghuniKamar ? $pu->penghuniKamar->where('status', 'aktif')->first() : null;
                $alreadyOccupying = $activePk !== null;
                $isDisabled = $alreadyOccupying || !$pu->is_active;
                $statusTag = !$pu->is_active ? '[NONAKTIF]' : ($alreadyOccupying ? '[' . ($activePk->kamar->kode_kamar ?? 'TERISI') . ']' : '[READY]');
                $shortNama = \Illuminate\Support\Str::limit($pu->nama, 20);
                @endphp
                <option value="{{ $pu->id }}"
                    {{ $isDisabled ? 'disabled' : '' }}
                    class="{{ $isDisabled ? 'text-gray-400 bg-gray-100 dark:bg-gray-800' : 'text-gray-900 dark:text-white font-bold' }}">
                    {{ $shortNama }} {{ $statusTag }}
                </option>
                @endforeach
            </select>
        </div>

        {{-- Penghuni 2 (Wajib Jika Tipe Berbagi) --}}
        <div x-show="selectedKamarTipe === 'berbagi'" x-transition class="space-y-1">
            <label class="block text-xs font-bold text-purple-700 dark:text-purple-300 uppercase tracking-wider mb-1">
                Pilih Penghuni 2 <span class="text-red-500">* (Wajib - Minimal 2 Orang)</span>
            </label>
            <select name="penghuni_id_2" :required="selectedKamarTipe === 'berbagi'" class="w-full max-w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-xl text-xs font-semibold text-gray-900 dark:text-white truncate focus:ring-purple-500" style="max-width: 100%; width: 100%; box-sizing: border-box;">
                <option value="">-- Pilih Anak Kos Ke-2 --</option>
                @foreach($penghuniUsers as $pu)
                @php
                $activePk = $pu->penghuniKamar ? $pu->penghuniKamar->where('status', 'aktif')->first() : null;
                $alreadyOccupying = $activePk !== null;
                $isDisabled = $alreadyOccupying || !$pu->is_active;
                $statusTag = !$pu->is_active ? '[NONAKTIF]' : ($alreadyOccupying ? '[' . ($activePk->kamar->kode_kamar ?? 'TERISI') . ']' : '[READY]');
                $shortNama = \Illuminate\Support\Str::limit($pu->nama, 20);
                @endphp
                <option value="{{ $pu->id }}"
                    {{ $isDisabled ? 'disabled' : '' }}
                    class="{{ $isDisabled ? 'text-gray-400 bg-gray-100 dark:bg-gray-800' : 'text-gray-900 dark:text-white font-bold' }}">
                    {{ $shortNama }} {{ $statusTag }}
                </option>
                @endforeach
            </select>
        </div>

        {{-- Penghuni 3 (Opsional - Maksimal 3 Orang) --}}
        <div x-show="selectedKamarTipe === 'berbagi'" x-transition class="space-y-1">
            <label class="block text-xs font-bold text-purple-700 dark:text-purple-300 uppercase tracking-wider mb-1">
                Pilih Penghuni 3 <span class="text-gray-500 dark:text-gray-400 font-normal">(Opsional - Maksimal 3 Orang)</span>
            </label>
            <select name="penghuni_id_3" class="w-full max-w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-xl text-xs font-semibold text-gray-900 dark:text-white truncate focus:ring-purple-500" style="max-width: 100%; width: 100%; box-sizing: border-box;">
                <option value="">-- Tanpa Penghuni Ke-3 (Kapasitas 2 Orang) --</option>
                @foreach($penghuniUsers as $pu)
                @php
                $activePk = $pu->penghuniKamar ? $pu->penghuniKamar->where('status', 'aktif')->first() : null;
                $alreadyOccupying = $activePk !== null;
                $isDisabled = $alreadyOccupying || !$pu->is_active;
                $statusTag = !$pu->is_active ? '[NONAKTIF]' : ($alreadyOccupying ? '[' . ($activePk->kamar->kode_kamar ?? 'TERISI') . ']' : '[READY]');
                $shortNama = \Illuminate\Support\Str::limit($pu->nama, 20);
                @endphp
                <option value="{{ $pu->id }}"
                    {{ $isDisabled ? 'disabled' : '' }}
                    class="{{ $isDisabled ? 'text-gray-400 bg-gray-100 dark:bg-gray-800' : 'text-gray-900 dark:text-white font-bold' }}">
                    {{ $shortNama }} {{ $statusTag }}
                </option>
                @endforeach
            </select>
            <p class="text-[10px] text-purple-600 dark:text-purple-400 italic mt-0.5">Kamar tipe berbagi wajib diisi minimal 2 orang dan maksimal 3 orang.</p>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Durasi Sewa</label>
                <select name="durasi" x-model="durasiSewa" required class="w-full max-w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white truncate">
                    <option value="bulanan">Bulanan (Auto 30 Hari)</option>
                    <option value="mingguan">Mingguan (Auto 7 Hari)</option>
                    <option value="harian">Harian (Tentukan Selesai)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Tanggal Masuk</label>
                <input type="date" name="tanggal_masuk" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
            </div>
        </div>

        <div x-show="durasiSewa === 'harian'" x-transition class="space-y-1">
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Tanggal Selesai / Jatuh Tempo <span class="text-red-500">*</span></label>
            <input type="date" name="tanggal_keluar" :required="durasiSewa === 'harian'" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 italic">* Tentukan tanggal selesai untuk sewa harian.</p>
        </div>

        <div class="pt-2 flex justify-end gap-2">
            <x-btn type="button" variant="secondary" size="sm" @click="modalPenghuni = false">Batal</x-btn>
            <x-btn type="submit" variant="primary" size="sm">Daftarkan Penghuni</x-btn>
        </div>
    </form>
</x-modal>
</div>
@endsection