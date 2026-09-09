@extends('layouts.app')

@php
$user = Auth::user();
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
'searchText' => strtolower($kos->nama . ' ' . ($kos->alamat ?? '')),
];
})->values()->toArray();
@endphp

@section('title', 'Daftar Kos & Kamar')

@section('content')
<div class="space-y-4" x-data="{ 
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
    }
}">
    {{-- Header --}}
    <x-page-header title="Daftar Kos & Kamar" subtitle="Kelola dan pantau seluruh kos serta unit kamar yang Anda miliki" backUrl="{{ route('mitra.dashboard') }}" />

    {{-- Filter Bar: Search, Kos, Tipe Kamar, Tipe Sewa & Status Masa Aktif --}}
    @if(!$kosList->isEmpty())
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-800">
            <label class="block text-[11px] py-0.5 font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                Filter & Pencarian Kos / Kamar Anda
            </label>
            <button type="button"
                @click="filterKosId = 'all'; filterTipeKamar = 'all'; filterMasaAktif = 'all'; filterTipeSewa = 'all'; search = ''"
                x-show="filterKosId !== 'all' || filterTipeKamar !== 'all' || filterMasaAktif !== 'all' || filterTipeSewa !== 'all' || search !== ''"
                class="text-[10px] font-bold text-red-600 dark:text-red-400 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-900/60 px-2.5 py-0.5 rounded-lg transition-all cursor-pointer">
                Reset Filter
            </button>
        </div>

        {{-- Search Bar Input --}}
        <div class="relative">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" x-model="search" placeholder="Cari nama kos, kode kamar, nama penghuni, atau alamat..."
                class="w-full pl-9 pr-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-emerald-500 focus:outline-none">
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-2 gap-2">
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
    <x-empty-state message="Belum ada properti kos yang terdaftar untuk akun Anda." />
    @else
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
                {{-- Baris 1: Nama Kos & Badge Kamar --}}
                <div class="space-y-1.5">
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
            </div>

            {{-- Rooms List in this Kos --}}
            <div class="p-3">
                @if($kos->kamar->isEmpty())
                <div class="p-4 text-center">
                    <p class="text-xs text-gray-400">Belum ada kamar di kos ini.</p>
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
                        $targetKeluar = \Carbon\Carbon::parse($pk->tanggal_keluar)->setTime(14, 0, 0);
                        return (int) $targetKeluar->diffInDays(now());
                    })->max() ?? 0) : 0;
                    @endphp

                    <div x-show="matchKamar(@js($kamarMeta), @js($kosSearchText))"
                        x-transition
                        class="p-3.5 sm:p-4 rounded-xl border {{ $hasExpiredPenghuni ? 'bg-red-50/30 dark:bg-red-950/20 border-red-200 dark:border-red-900/40' : ($isTerisi ? 'bg-emerald-50/20 dark:bg-emerald-950/10 border-emerald-200/70 dark:border-emerald-900/40' : 'bg-gray-50/50 dark:bg-gray-800/30 border-gray-200/80 dark:border-gray-800') }} space-y-3 shadow-2xs">

                        {{-- Baris Header Kamar & Aksi --}}
                        <div class="flex items-center justify-between gap-2 border-b border-gray-200/60 dark:border-gray-700/60 pb-2.5">
                            {{-- Info Kamar (Kode Kamar + Icon WA, Tipe, Status) --}}
                            <div class="flex items-center gap-1.5 flex-wrap min-w-0">
                                @if($kamar->link_grup_wa)
                                <a href="{{ $kamar->link_grup_wa }}" target="_blank"
                                    class="font-bold text-xs font-mono text-emerald-800 dark:text-emerald-200 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/80 px-2.5 py-1 rounded-md border border-emerald-300 dark:border-emerald-800 shadow-2xs inline-flex items-center justify-center gap-1.5 leading-none transition-all active:scale-95 group"
                                    title="Buka Grup WhatsApp Kamar {{ $kamar->kode_kamar }}">
                                    <svg class="w-3.5 h-3.5 fill-current text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform flex-shrink-0" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                                    </svg>
                                    <span class="leading-none">Kamar {{ $kamar->kode_kamar }}</span>
                                </a>
                                @else
                                <span class="font-bold text-xs font-mono text-gray-900 dark:text-white bg-white dark:bg-gray-900 px-2.5 py-1 rounded-md border border-gray-200 dark:border-gray-700 shadow-2xs inline-flex items-center justify-center leading-none">
                                    Kamar {{ $kamar->kode_kamar }}
                                </span>
                                @endif

                                @if($hasExpiredPenghuni)
                                <span class="px-2 py-1 text-[10px] font-bold rounded-md bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300 border border-red-200 dark:border-red-800 inline-flex items-center gap-1 leading-none">
                                    <svg class="w-3 h-3 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ $kamarOverdueDays > 0 ? 'Jatuh Tempo (Terlewat ' . $kamarOverdueDays . ' Hari)' : 'Jatuh Tempo Hari Ini' }}</span>
                                </span>
                                @elseif($isTerisi)
                                <span class="px-2 py-1 text-[10px] font-bold rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 inline-flex items-center leading-none">
                                    Terisi ({{ $activePenghunis->count() }}/{{ $kamar->kapasitas }})
                                </span>
                                @else
                                <span class="px-2 py-1 text-[10px] font-bold rounded-md bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800 inline-flex items-center leading-none">
                                    Kosong
                                </span>
                                @endif
                            </div>

                            {{-- Tombol Aksi (Detail) --}}
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <a href="{{ route('mitra.kamar.show', $kamar->kode_kamar ?? $kamar->id) }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-100/90 hover:bg-emerald-200 dark:hover:bg-emerald-800 dark:bg-emerald-900/50 rounded-xl transition-all active:scale-95 shadow-2xs">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span>Detail</span>
                                </a>
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
                            $overdueDays = $isPkExpired ? (int) $targetKeluar->diffInDays(now()) : 0;
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
                        </div>
                        @else
                        <div class="mt-2 pt-2 border-t border-gray-200/60 dark:border-gray-700/60 flex justify-between items-center text-xs">
                            <span class="text-amber-600 dark:text-amber-400 font-bold text-[11px]">🏠 Kamar Kosong (Siap Dihuni)</span>
                            <span class="text-[10px] font-mono font-bold text-gray-400">Kapasitas {{ $kamar->kapasitas }} Orang</span>
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

    {{-- Modal Preview Foto (Zoom) --}}
    <x-modal show="showImageModal" title="Detail Foto Kos">
        <div class="p-2 space-y-3">
            <p class="text-xs font-bold text-gray-700 dark:text-gray-300" x-text="previewImageTitle"></p>
            <div class="relative w-full max-h-[75vh] overflow-hidden rounded-xl bg-black grid place-items-center">
                <img :src="previewImageUrl" :alt="previewImageTitle" class="max-w-full max-h-[75vh] object-contain rounded-lg">
            </div>
            <div class="flex justify-end">
                <x-btn type="button" variant="secondary" size="sm" @click="showImageModal = false">Tutup</x-btn>
            </div>
        </div>
    </x-modal>
</div>
@endsection