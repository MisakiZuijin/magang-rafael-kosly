@extends('layouts.app')

@section('title', 'Daftar Kos & Kamar')

@section('content')
<div class="max-w-md mx-auto space-y-3.5 pb-10" x-data="{ 
    search: '', 
    selectedKos: 'semua', 
    statusFilter: 'semua',
    tipeFilter: 'semua',
    modalEditKamar: false,
    editKamarData: {},
    editKamarUrl: '',
    formatRupiah(val) {
        if (!val) return '';
        const num = val.toString().replace(/[^0-9]/g, '');
        return num ? 'Rp ' + new Intl.NumberFormat('id-ID').format(num) : '';
    },
    parseDigits(val) {
        if (!val) return '';
        return val.toString().replace(/[^0-9]/g, '');
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
        this.editKamarUrl = '/mitra/kamar/' + kamar.id;
        this.modalEditKamar = true;
    },
    matchSearch(text) {
        if (!this.search) return true;
        return text.toLowerCase().includes(this.search.toLowerCase());
    }
}">
    {{-- Header --}}
    <x-page-header title="Kos & Kamar Kos" subtitle="Kelola dan lihat status kamar kos Anda" backUrl="{{ route('mitra.dashboard') }}" />

    {{-- Filter & Search Section --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        {{-- Search Input --}}
        <div class="relative">
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text"
                x-model="search"
                placeholder="Cari kode kamar atau nama penghuni..."
                class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all">
        </div>

        {{-- Filter Selects --}}
        <div class="grid grid-cols-3 gap-2">
            <div>
                <label class="block text-[9px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Pilih Kos</label>
                <select x-model="selectedKos" class="w-full py-1.5 px-2 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-800 dark:text-gray-200 focus:ring-emerald-500">
                    <option value="semua">Semua Kos</option>
                    @foreach($kosList as $k)
                    <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Status</label>
                <select x-model="statusFilter" class="w-full py-1.5 px-2 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-800 dark:text-gray-200 focus:ring-emerald-500">
                    <option value="semua">Semua</option>
                    <option value="terisi">Terisi</option>
                    <option value="kosong">Kosong</option>
                </select>
            </div>

            <div>
                <label class="block text-[9px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Jenis Kamar</label>
                <select x-model="tipeFilter" class="w-full py-1.5 px-2 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-800 dark:text-gray-200 focus:ring-emerald-500">
                    <option value="semua">Semua</option>
                    <option value="standar">Standar</option>
                    <option value="berbagi">Berbagi</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Data List --}}
    @if($kamarData->isEmpty())
    <x-empty-state message="Belum ada data kamar kos yang terdaftar." />
    @else
    <div class="space-y-3">
        @foreach($kamarData as $kamar)
        @php
        $penghuniAktifList = $kamar->penghuniKamar ? $kamar->penghuniKamar->where('status', 'aktif') : collect();
        $hasPenghuni = $penghuniAktifList->isNotEmpty();
        $hasExpiredPenghuni = $penghuniAktifList->contains(function($pk) {
        return $pk->tanggal_keluar && \Carbon\Carbon::parse($pk->tanggal_keluar)->setTime(14, 0, 0)->isPast();
        });
        $allPenghuniNames = $penghuniAktifList->map(fn($p) => $p->penghuni->nama ?? '')->implode(' ');
        $isKosLocked = $kamar->kos && $kamar->kos->is_locked;
        @endphp

        <div x-show="
                        (selectedKos === 'semua' || selectedKos == '{{ $kamar->kos_id }}') &&
                        (statusFilter === 'semua' || statusFilter === '{{ $kamar->status }}') &&
                        (tipeFilter === 'semua' || tipeFilter === '{{ $kamar->tipe }}') &&
                        matchSearch(@js($kamar->kode_kamar . ' ' . $allPenghuniNames . ' ' . ($kamar->kos->nama ?? '')))
                     "
            x-transition
            class="p-3.5 sm:p-4 rounded-2xl border {{ $hasExpiredPenghuni ? 'bg-red-50/40 dark:bg-red-950/20 border-red-200 dark:border-red-900/50' : ($hasPenghuni ? 'bg-emerald-50/30 dark:bg-emerald-950/20 border-emerald-200/80 dark:border-emerald-900/50' : 'bg-gray-50/80 dark:bg-gray-800/40 border-gray-200/80 dark:border-gray-800') }} space-y-2.5 shadow-2xs">

            {{-- Baris Header Kamar & Status --}}
            <div class="flex items-center justify-between gap-3 border-b border-gray-200/50 dark:border-gray-700/40 pb-2.5">
                <div class="flex flex-col gap-1.5 min-w-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="font-bold text-xs font-mono text-gray-900 dark:text-white bg-white dark:bg-gray-900 px-2.5 py-1 rounded-lg border border-gray-200/60 dark:border-gray-800 truncate shadow-2xs">
                            {{ $kamar->kos->nama ?? 'Kos' }} · {{ $kamar->kode_kamar }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="px-2 py-0.5 text-[9px] uppercase font-bold rounded-md {{ $kamar->tipe === 'berbagi' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' }}">
                            {{ ucfirst($kamar->tipe) }}
                        </span>
                        <x-badge type="{{ $hasExpiredPenghuni ? 'danger' : ($hasPenghuni ? 'success' : 'warning') }}" size="xs">
                            {{ $hasExpiredPenghuni ? 'Jatuh Tempo' : ($hasPenghuni ? 'Terisi' : 'Kosong') }}
                        </x-badge>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <a href="{{ route('mitra.kamar.show', $kamar->kode_kamar ?? $kamar->id) }}"
                        class="px-2.5 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-100/90 hover:bg-emerald-200 dark:hover:bg-emerald-800 dark:bg-emerald-900/50 rounded-xl transition-all flex items-center gap-1 active:scale-95 shadow-2xs">
                        <span>Detail</span>
                    </a>

                    @if($isKosLocked)
                    <span class="px-2 py-1 text-xs font-bold text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 rounded-xl flex items-center gap-1 cursor-not-allowed select-none" title="Akses edit kamar untuk kos ini sedang dikunci oleh Admin/SuperAdmin">
                        <span>🔒</span>
                    </span>
                    @else
                    <button type="button"
                        @click="openEditKamarModal(@js($kamar))"
                        class="px-2.5 py-1 text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-100/90 hover:bg-blue-200 dark:hover:bg-blue-800/50 dark:bg-blue-900/50 rounded-xl transition-all flex items-center gap-1 active:scale-95 shadow-2xs">
                        <span>Edit</span>
                    </button>
                    @endif
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
            <div class="text-[11px] font-medium text-gray-700 dark:text-gray-300 bg-white/80 dark:bg-gray-900/60 p-2 rounded-xl border border-gray-200/70 dark:border-gray-700/60 flex flex-wrap items-center gap-1.5">
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
            <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] items-center gap-1.5 text-[10px] font-mono bg-white/60 dark:bg-gray-900/40 p-2 rounded-xl border border-gray-200/50 dark:border-gray-800">
                <div class="grid grid-cols-1 gap-2">
                    <p class="text-xs text-mono text-black dark:text-white">Jenis Biaya</p>
                    <div class="grid grid-cols-3 items-center gap-1.5 text-[10px]">
                        <span class="text-center text-emerald-700 dark:text-emerald-300 font-bold bg-emerald-50 dark:bg-emerald-950/40 px-1.5 py-0.5 rounded">
                            Rp {{ number_format($kamar->harga_per_bulan, 0, ',', '.') }}/bln
                        </span>
                        @if($kamar->harga_per_minggu)
                        <span class="text-center text-purple-600 dark:text-purple-400 font-bold bg-purple-50 dark:bg-purple-950/40 px-1.5 py-0.5 rounded">
                            Rp {{ number_format($kamar->harga_per_minggu, 0, ',', '.') }}/minggu
                        </span>
                        @endif
                        @if($kamar->harga_per_hari)
                        <span class="text-center text-blue-600 dark:text-blue-400 font-bold bg-blue-50 dark:bg-blue-950/40 px-1.5 py-0.5 rounded">
                            Rp {{ number_format($kamar->harga_per_hari, 0, ',', '.') }}/hari
                        </span>
                        @endif
                    </div>
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

            {{-- Occupants Section --}}
            @if($hasPenghuni)
            <div class="mt-2 pt-2 border-t border-emerald-200/60 dark:border-emerald-900/40 space-y-1.5">
                <div class="grid grid-cols-1 items-center text-[10px] font-bold text-emerald-700 dark:text-emerald-400">
                    <span>👥 PENGHUNI AKTIF ({{ $penghuniAktifList->count() }}/{{ $kamar->kapasitas }})</span>
                </div>

                @foreach($penghuniAktifList as $pk)
                @php
                $pUser = $pk->penghuni;
                $targetKeluar = $pk->tanggal_keluar ? \Carbon\Carbon::parse($pk->tanggal_keluar)->setTime(14, 0, 0) : null;
                $isPkExpired = $targetKeluar && $targetKeluar->isPast();
                $overdueDays = $isPkExpired ? max(1, (int) $targetKeluar->diffInDays(now())) : 0;
                $tglKeluarStr = $pk->tanggal_keluar ? \Carbon\Carbon::parse($pk->tanggal_keluar)->format('d M Y') : '-';
                @endphp
                @if($pUser)
                <div class="grid grid-cols-[1fr_auto] items-center text-xs p-2 rounded-xl border {{ $isPkExpired ? 'bg-red-100/70 dark:bg-red-950/40 border-red-200 dark:border-red-900/60' : 'bg-white/90 dark:bg-gray-900/80 border-emerald-100 dark:border-emerald-900/30' }}">
                    <div class="truncate min-w-0 flex-1">
                        <span class="font-bold text-gray-900 dark:text-white block truncate text-[11px]">
                            {{ $pUser->nama }}
                        </span>
                        <span class="text-[9px] font-mono block {{ $isPkExpired ? 'text-red-600 dark:text-red-400 font-bold' : 'text-gray-500' }}">
                            {{ $isPkExpired ? "⚠️ Terlewat {$overdueDays} Hari (s/d {$tglKeluarStr})" : "Sewa " . ucfirst($pk->durasi) . " · s/d {$tglKeluarStr}" }}
                        </span>
                    </div>

                    @if($pUser->no_hp)
                    @php
                    $phone = preg_replace('/[^0-9]/', '', $pUser->no_hp);
                    if(str_starts_with($phone, '0')) {
                    $phone = '62' . substr($phone, 1);
                    }
                    $waMessage = rawurlencode("Halo Kak {$pUser->nama}, pengingat dari Pemilik Kos {$kamar->kos->nama} mengenai Kamar {$kamar->kode_kamar}.");
                    @endphp
                    <a href="https://wa.me/{{ $phone }}?text={{ $waMessage }}"
                        target="_blank"
                        class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-lg text-[10px] font-bold shadow-2xs transition-transform grid grid-flow-col auto-cols-max items-center gap-1 justify-self-end ml-2">
                        <svg class="w-3 h-3 text-white fill-current" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                            <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                        </svg>
                        <span>WhatsApp</span>
                    </a>
                    @endif
                </div>
                @endif
                @endforeach
            </div>
            @else
            <div class="mt-2 pt-2 border-t border-gray-200/60 dark:border-gray-700/60 flex justify-between items-center text-xs">
                <span class="text-amber-600 font-bold text-[11px]">🏠 Kamar Kosong (Siap Dihuni)</span>
                <span class="text-[10px] font-mono font-bold text-gray-400">Kapasitas {{ $kamar->kapasitas }} Org</span>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Modal Edit Kamar untuk Mitra --}}
    <x-modal show="modalEditKamar" title="Edit Data Kamar">
        <form :action="editKamarUrl" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Tambah Foto Kamar Baru (Opsional)</label>
                <input type="file" name="foto[]" multiple accept="image/*" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
                <p class="text-[10px] text-gray-400 mt-0.5 italic">* Foto baru yang diunggah akan ditambahkan ke galeri foto kamar ini.</p>
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
</div>
@endsection