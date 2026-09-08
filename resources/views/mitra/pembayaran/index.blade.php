@extends('layouts.app')

@section('title', 'Verifikasi Pembayaran - Mitra Pro')

@php
$kosListVerif = $terverifikasi->map(fn($p) => $p->penghuniKamar->kamar->kos->nama ?? null)->filter()->unique()->values();
$kosListTolak = $ditolak->map(fn($p) => $p->penghuniKamar->kamar->kos->nama ?? null)->filter()->unique()->values();
@endphp

@section('content')
<div class="space-y-5" x-data="{ 
    tab: 'pending', 
    showReviewModal: false,
    showRejectReason: false,
    showImageFullscreen: false,
    searchVerif: '',
    filterKosVerif: '',
    searchTolak: '',
    filterKosTolak: '',
    matchVerif(text, kosNama) {
        const s = this.searchVerif.toLowerCase().trim();
        const matchesSearch = !s || text.toLowerCase().includes(s);
        const matchesKos = !this.filterKosVerif || kosNama === this.filterKosVerif;
        return matchesSearch && matchesKos;
    },
    matchTolak(text, kosNama) {
        const s = this.searchTolak.toLowerCase().trim();
        const matchesSearch = !s || text.toLowerCase().includes(s);
        const matchesKos = !this.filterKosTolak || kosNama === this.filterKosTolak;
        return matchesSearch && matchesKos;
    },
    zoomLevel: 1,
    panX: 0,
    panY: 0,
    isDragging: false,
    startX: 0,
    startY: 0,
    selectedPenghuni: '',
    selectedKosKamar: '',
    selectedJumlah: '',
    selectedTanggal: '',
    selectedBuktiUrl: '',
    verifyUrl: '',
    rejectUrl: '',
    zoomIn() {
        if (this.zoomLevel < 3.5) this.zoomLevel = +(this.zoomLevel + 0.5).toFixed(1);
    },
    zoomOut() {
        if (this.zoomLevel > 0.8) {
            this.zoomLevel = +(this.zoomLevel - 0.5).toFixed(1);
            if (this.zoomLevel <= 1) this.resetPan();
        }
    },
    resetPan() {
        this.panX = 0;
        this.panY = 0;
    },
    openFullscreen() {
        this.zoomLevel = 1;
        this.resetPan();
        this.showImageFullscreen = true;
    },
    startDrag(e) {
        if (this.zoomLevel <= 1) return;
        this.isDragging = true;
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        this.startX = clientX - this.panX;
        this.startY = clientY - this.panY;
    },
    onDrag(e) {
        if (!this.isDragging || this.zoomLevel <= 1) return;
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        this.panX = clientX - this.startX;
        this.panY = clientY - this.startY;
    },
    endDrag() {
        this.isDragging = false;
    }
}">
    {{-- Header --}}
    <x-page-header title="Verifikasi Pembayaran" subtitle="Kelola konfirmasi bukti transfer dari penghuni kos Anda" backUrl="{{ route('dashboard') }}" />

    {{-- Tabs --}}
    <div class="grid grid-cols-3 gap-2 p-1.5 bg-gray-100/90 dark:bg-gray-800/90 rounded-2xl shadow-xs">
        <button @click="tab = 'pending'"
            :class="tab === 'pending' ? 'bg-white dark:bg-gray-900 text-amber-700 dark:text-amber-400 shadow-sm font-bold scale-[1.01]' : 'text-gray-500 dark:text-gray-400 font-medium hover:text-gray-700 dark:hover:text-gray-200'"
            class="py-2.5 px-3 text-xs rounded-xl transition-all text-center">
            Pending ({{ $pending->count() }})
        </button>
        <button @click="tab = 'terverifikasi'"
            :class="tab === 'terverifikasi' ? 'bg-white dark:bg-gray-900 text-emerald-700 dark:text-emerald-400 shadow-sm font-bold scale-[1.01]' : 'text-gray-500 dark:text-gray-400 font-medium hover:text-gray-700 dark:hover:text-gray-200'"
            class="py-2.5 px-3 text-xs rounded-xl transition-all text-center">
            Verifikasi ({{ $terverifikasi->count() }})
        </button>
        <button @click="tab = 'ditolak'"
            :class="tab === 'ditolak' ? 'bg-white dark:bg-gray-900 text-red-700 dark:text-red-400 shadow-sm font-bold scale-[1.01]' : 'text-gray-500 dark:text-gray-400 font-medium hover:text-gray-700 dark:hover:text-gray-200'"
            class="py-2.5 px-3 text-xs rounded-xl transition-all text-center">
            Ditolak ({{ $ditolak->count() }})
        </button>
    </div>

    {{-- Tab Pending --}}
    <div x-show="tab === 'pending'" class="space-y-4" x-transition>
        @forelse($pending as $p)
        @php
        $penghuniNama = $p->penghuniKamar->penghuni->nama ?? 'Penghuni';
        $kosKamar = ($p->penghuniKamar->kamar->kode_kamar ?? '-') . ' · ' . ($p->penghuniKamar->kamar->kos->nama ?? '-');
        $jumlahFormatted = 'Rp ' . number_format($p->jumlah, 0, ',', '.');
        $tanggalFormatted = $p->tanggal_bayar ? $p->tanggal_bayar->format('d M Y') : '-';
        $buktiUrl = $p->bukti_transfer_url ? asset('storage/' . $p->bukti_transfer_url) : '';
        $vUrl = route('mitra.pembayaran.verify', $p->id);
        $rUrl = route('mitra.pembayaran.reject', $p->id);
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 sm:p-5 border border-amber-200/90 dark:border-amber-900/60 shadow-sm hover:shadow-md transition-all space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200/70 dark:border-amber-900/50 uppercase tracking-wider">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Menunggu Verifikasi
                    </span>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-bold text-base text-gray-900 dark:text-white pt-0.5">{{ $penghuniNama }}</h3>
                        @if($p->penghuniKamar && $p->penghuniKamar->penghuni && $p->penghuniKamar->penghuni->no_hp)
                        @php
                        $waUrlP = \App\Services\WhatsAppService::generatePenghuniUrl($p->penghuniKamar->penghuni, $p->penghuniKamar, null, $p->penghuniKamar?->kamar, $p->penghuniKamar?->kamar?->kos);
                        @endphp
                        <a href="{{ $waUrlP }}" target="_blank" class="px-2 py-0.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[10px] font-bold inline-flex items-center gap-1 shadow-xs group" title="Chat WhatsApp ke {{ $penghuniNama }}">
                            <svg class="w-3.5 h-3.5 fill-current text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform flex-shrink-0" viewBox="0 0 24 24">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                            </svg>
                            <span>{{ $p->penghuniKamar->penghuni->no_hp }}</span>
                        </a>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>{{ $kosKamar }}</span>
                    </p>
                </div>

                <div class="sm:text-right flex sm:flex-col items-start sm:items-end justify-between gap-1.5">
                    <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 text-base sm:text-lg block">
                        {{ $jumlahFormatted }}
                    </span>
                    @if($p->penghuniKamar && $p->penghuniKamar->kamar && $p->penghuniKamar->kamar->tipe === 'berbagi')
                    @php
                    $badgeInfo = $p->getTarifBadgeInfo();
                    @endphp
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md {{ $badgeInfo['class'] }}">
                        {{ $badgeInfo['text'] }}
                    </span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:items-center gap-3 pt-3.5 border-t border-gray-100 dark:border-gray-800 text-xs">
                <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 font-mono text-xs">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Tgl Transfer: <strong>{{ $tanggalFormatted }}</strong></span>
                </div>

                <x-btn type="button" size="sm" variant="primary"
                    @click="
                        selectedPenghuni = '{{ addslashes($penghuniNama) }}';
                        selectedKosKamar = '{{ addslashes($kosKamar) }}';
                        selectedJumlah = '{{ $jumlahFormatted }}';
                        selectedTanggal = '{{ $tanggalFormatted }}';
                        selectedBuktiUrl = '{{ $buktiUrl }}';
                        verifyUrl = '{{ $vUrl }}';
                        rejectUrl = '{{ $rUrl }}';
                        showReviewModal = true;
                        showRejectReason = false;
                    "
                    class="!py-2 !px-4 text-xs font-bold shadow-xs hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span>Tinjau & Verifikasi Bukti</span>
                </x-btn>
            </div>
        </div>
        @empty
        <x-empty-state message="Tidak ada transaksi pembayaran yang menunggu verifikasi saat ini." />
        @endforelse
    </div>

    {{-- Tab Terverifikasi --}}
    <div x-show="tab === 'terverifikasi'" class="space-y-4" x-transition x-cloak>
        @if($terverifikasi->count() > 0)
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3 border border-gray-200 dark:border-gray-800 shadow-sm space-y-2">
            <div class="flex flex-col sm:flex-row gap-2">
                <div class="relative flex-1">
                    <input type="text" x-model="searchVerif" placeholder="Cari nama penghuni, kos, kamar, nominal, invoice..."
                        class="w-full pl-9 pr-8 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <button type="button" x-show="searchVerif" @click="searchVerif = ''" class="absolute right-2.5 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xs font-bold" style="display: none;">✕</button>
                </div>

                @if($kosListVerif->count() > 0)
                <div class="w-full sm:w-44">
                    <select x-model="filterKosVerif" class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
                        <option value="">Semua Kos ({{ $kosListVerif->count() }})</option>
                        @foreach($kosListVerif as $kNama)
                        <option value="{{ $kNama }}">{{ $kNama }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            <div x-show="searchVerif || filterKosVerif" class="flex items-center justify-between pt-1 text-[11px] text-gray-500 dark:text-gray-400" style="display: none;">
                <span>Menampilkan hasil pencarian / filter</span>
                <button type="button" @click="searchVerif = ''; filterKosVerif = '';" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                    Reset Filter
                </button>
            </div>
        </div>
        @endif

        @forelse($terverifikasi as $p)
        @php
        $penghuniNama = $p->penghuniKamar->penghuni->nama ?? 'Penghuni';
        $kosPureNama = $p->penghuniKamar->kamar->kos->nama ?? '';
        $kamarKode = $p->penghuniKamar->kamar->kode_kamar ?? '-';
        $kosKamar = $kamarKode . ' · ' . ($kosPureNama ?: '-');
        $jumlahFormatted = 'Rp ' . number_format($p->jumlah, 0, ',', '.');
        $tanggalTransferFormatted = $p->tanggal_bayar ? $p->tanggal_bayar->format('d M Y') : '-';
        $waktuVerifFormatted = $p->tanggal_verifikasi ? $p->tanggal_verifikasi->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB' : ($p->updated_at ? $p->updated_at->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-');
        $buktiUrl = $p->bukti_transfer_url ? asset('storage/' . $p->bukti_transfer_url) : '';
        $invoiceKode = $p->kode_invoice ?? '';
        $searchString = strtolower($penghuniNama . ' ' . $kosPureNama . ' ' . $kamarKode . ' ' . $p->jumlah . ' ' . $tanggalTransferFormatted . ' ' . $waktuVerifFormatted . ' ' . $invoiceKode);
        @endphp

        <div x-show="matchVerif('{{ addslashes($searchString) }}', '{{ addslashes($kosPureNama) }}')" class="bg-white dark:bg-gray-900 rounded-2xl p-4 sm:p-5 border border-emerald-100 dark:border-emerald-900/40 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200/70 dark:border-emerald-900/50 uppercase tracking-wider">
                        ✓ Terverifikasi Lunas
                    </span>
                    <h3 class="font-bold text-base text-gray-900 dark:text-white pt-0.5">{{ $penghuniNama }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>{{ $kosKamar }}</span>
                    </p>
                </div>

                <div class="sm:text-right flex sm:flex-col items-start sm:items-end justify-between gap-1.5">
                    <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 text-base sm:text-lg block">
                        {{ $jumlahFormatted }}
                    </span>
                    @if($p->penghuniKamar && $p->penghuniKamar->kamar && $p->penghuniKamar->kamar->tipe === 'berbagi')
                    @php $badgeInfo = $p->getTarifBadgeInfo(); @endphp
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md {{ $badgeInfo['class'] }}">
                        {{ $badgeInfo['text'] }}
                    </span>
                    @endif
                </div>
            </div>

            <div class="space-y-1 pt-3 border-t border-gray-100 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 font-mono">
                <div>
                    <p class="text-gray-400 dark:text-gray-500 font-mono text-[11px] flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Tgl Transfer: {{ $tanggalTransferFormatted }}</span>
                    </p>
                </div>
                <div>
                    <p class="text-emerald-700 dark:text-emerald-400 font-mono text-xs font-semibold flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 flex-shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Diverifikasi: <strong>{{ $waktuVerifFormatted }}</strong></span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                @if($p->effective_bukti_transfer_url)
                <a href="{{ asset('storage/' . $p->effective_bukti_transfer_url) }}" target="_blank" class="flex-1 inline-flex items-center justify-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 font-bold bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all shadow-2xs text-center">
                    <svg class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Bukti Transfer</span>
                </a>
                @endif
                <a href="{{ route('pembayaran.nota', $p->kode_invoice ?? $p->id) }}" class="flex-1 inline-flex items-center justify-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-300 font-bold bg-emerald-50 dark:bg-emerald-950/60 px-3 py-2 rounded-xl border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition-all shadow-2xs text-center">
                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Nota Pembayaran</span>
                </a>
            </div>
        </div>
        @empty
        <x-empty-state message="Belum ada riwayat pembayaran yang terverifikasi." />
        @endforelse
    </div>

    {{-- Tab Ditolak --}}
    <div x-show="tab === 'ditolak'" class="space-y-4" x-transition x-cloak>
        @forelse($ditolak as $p)
        @php
        $penghuniNama = $p->penghuniKamar->penghuni->nama ?? 'Penghuni';
        $kosKamar = ($p->penghuniKamar->kamar->kode_kamar ?? '-') . ' · ' . ($p->penghuniKamar->kamar->kos->nama ?? '-');
        $jumlahFormatted = 'Rp ' . number_format($p->jumlah, 0, ',', '.');
        $tanggalFormatted = $p->tanggal_bayar ? $p->tanggal_bayar->format('d M Y') : '-';
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 sm:p-5 border border-red-100 dark:border-red-900/40 shadow-sm space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300 border border-red-200/70 dark:border-red-900/50 uppercase tracking-wider">
                        ✕ Pembayaran Ditolak
                    </span>
                    <h3 class="font-bold text-base text-gray-900 dark:text-white pt-0.5">{{ $penghuniNama }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $kosKamar }}</p>
                </div>
                <span class="font-bold font-mono text-red-600 dark:text-red-400 text-base sm:text-lg block">
                    {{ $jumlahFormatted }}
                </span>
            </div>

            @if($p->catatan_verifikasi)
            <div class="p-3 bg-red-50/70 dark:bg-red-950/30 rounded-xl border border-red-100 dark:border-red-900/40 text-xs text-red-700 dark:text-red-300 space-y-1">
                <p class="font-bold">Alasan Penolakan:</p>
                <p class="italic text-[11px]">{{ $p->catatan_verifikasi }}</p>
            </div>
            @endif
        </div>
        @empty
        <x-empty-state message="Tidak ada transaksi pembayaran yang ditolak." />
        @endforelse
    </div>

    {{-- Modal Tinjau & Verifikasi Bukti --}}
    <x-modal show="showReviewModal" title="Tinjau & Verifikasi Bukti Pembayaran">
        <div class="space-y-4">
            <div class="p-3.5 bg-gray-50 dark:bg-gray-800/60 rounded-2xl border border-gray-100 dark:border-gray-800 space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-gray-400">Penghuni:</span>
                    <strong class="text-gray-900 dark:text-white" x-text="selectedPenghuni"></strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Kos & Kamar:</span>
                    <strong class="text-gray-900 dark:text-white" x-text="selectedKosKamar"></strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Nominal Transfer:</span>
                    <strong class="text-emerald-600 dark:text-emerald-400 font-mono font-bold" x-text="selectedJumlah"></strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Tanggal Transfer:</span>
                    <span class="text-gray-700 dark:text-gray-300 font-mono" x-text="selectedTanggal"></span>
                </div>
            </div>

            {{-- Bukti Gambar --}}
            <div class="relative bg-gray-100 dark:bg-gray-800 rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700 flex items-center justify-center min-h-[220px] max-h-[340px]">
                <template x-if="selectedBuktiUrl">
                    <img :src="selectedBuktiUrl" alt="Bukti Transfer" class="w-full h-full object-contain max-h-[320px] cursor-pointer hover:opacity-95 transition-opacity" @click="openFullscreen()">
                </template>
                <template x-if="!selectedBuktiUrl">
                    <p class="text-xs text-gray-400 p-8 text-center">Bukti transfer belum diunggah.</p>
                </template>
            </div>

            <div class="flex justify-between items-center text-[11px] text-gray-400 px-1">
                <span>Klik foto untuk melihat ukuran penuh / zoom</span>
            </div>

            {{-- Form Tolak dengan Catatan --}}
            <div x-show="showRejectReason" x-transition class="space-y-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                <form :action="rejectUrl" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-red-600 dark:text-red-400 mb-1">
                            Alasan Penolakan Pembayaran <span class="text-red-500">*</span>
                        </label>
                        <textarea name="catatan" rows="3" required placeholder="Contoh: Bukti buram / nominal tidak sesuai / nomor rekening salah..."
                            class="w-full px-3 py-2 bg-red-50/50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-red-500"></textarea>
                    </div>

                    <div class="flex gap-2 justify-end">
                        <x-btn type="button" variant="secondary" size="sm" @click="showRejectReason = false">Batal Tolak</x-btn>
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-xs active:scale-95 transition-all">
                            Konfirmasi Tolak Pembayaran
                        </button>
                    </div>
                </form>
            </div>

            {{-- Action Buttons Utama --}}
            <div x-show="!showRejectReason" class="flex gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                <form :action="verifyUrl" method="POST" class="flex-1">
                    @csrf
                    <button type="submit"
                        class="w-full py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm active:scale-95 transition-all">
                        ✓ Verifikasi
                    </button>
                </form>

                <button type="button" @click="showRejectReason = true"
                    class="flex-1 py-2.5 px-3 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-900/60 text-red-600 dark:text-red-400 text-xs font-bold rounded-xl active:scale-95 transition-all">
                    ✕ Tolak
                </button>
            </div>
        </div>
    </x-modal>

    {{-- Modal Preview Gambar Fullscreen dengan Zooming & Pan/Geser --}}
    <div x-show="showImageFullscreen"
        x-cloak
        x-transition.opacity.duration.200ms
        @keydown.window.escape="showImageFullscreen = false; zoomLevel = 1; resetPan();"
        @click="showImageFullscreen = false; zoomLevel = 1; resetPan();"
        class="fixed inset-0 z-[999999] w-screen h-screen bg-black/95 backdrop-blur-lg flex flex-col items-center justify-between p-3 sm:p-4 box-border overflow-hidden select-none">

        {{-- Top Bar: Info & Zoom Controls --}}
        <div class="w-full flex items-center justify-between gap-2 text-white z-20 pt-1 pb-1 box-border">
            <div class="flex items-center gap-2 min-w-0 flex-1">
                <span class="text-xs font-bold font-mono bg-white/10 px-3 py-1 rounded-full backdrop-blur-md border border-white/10 truncate max-w-[140px] sm:max-w-[220px]">
                    <span x-text="selectedPenghuni"></span>
                </span>
                <span class="text-[11px] font-mono text-emerald-400 font-bold bg-white/10 px-2 py-0.5 rounded-lg border border-white/10 flex-shrink-0" x-text="Math.round(zoomLevel * 100) + '%'"></span>
            </div>

            {{-- Zoom & Close Action Buttons --}}
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <button type="button" @click.stop="zoomIn()" class="p-2 text-white/90 hover:text-white bg-white/10 hover:bg-white/20 rounded-full transition-all active:scale-95" title="Perbesar (+)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                    </svg>
                </button>
                <button type="button" @click.stop="zoomOut()" class="p-2 text-white/90 hover:text-white bg-white/10 hover:bg-white/20 rounded-full transition-all active:scale-95" title="Perkecil (-)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                    </svg>
                </button>
                <button type="button" @click.stop="zoomLevel = 1; resetPan();" class="px-2.5 py-1.5 text-[10px] font-bold text-white/90 hover:text-white bg-white/10 hover:bg-white/20 rounded-full transition-all active:scale-95" title="Reset Zoom">
                    Reset
                </button>
                <button type="button" @click="showImageFullscreen = false; zoomLevel = 1; resetPan();" class="p-2 text-white/90 hover:text-white bg-white/20 hover:bg-white/30 rounded-full transition-all active:scale-95 ml-1" title="Tutup">
                    <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Image Display Area with Wheel Zoom & Pan/Geser Dragging --}}
        <div class="flex-1 w-full flex items-center justify-center p-2 my-auto overflow-hidden no-scrollbar"
            @wheel.prevent="if ($event.deltaY < 0) { zoomIn(); } else { zoomOut(); }">
            <img :src="selectedBuktiUrl"
                :style="`transform: translate(${panX}px, ${panY}px) scale(${zoomLevel}); transition: ${isDragging ? 'none' : 'transform 0.15s ease-out'}; cursor: ${zoomLevel > 1 ? (isDragging ? 'grabbing' : 'grab') : 'pointer'};`"
                class="max-w-full max-h-[82vh] object-contain rounded-xl shadow-2xl origin-center touch-none select-none"
                alt="Bukti Transfer Fullscreen"
                @mousedown.stop="startDrag($event)"
                @mousemove.window="onDrag($event)"
                @mouseup.window="endDrag()"
                @touchstart.stop="startDrag($event)"
                @touchmove.window="onDrag($event)"
                @touchend.window="endDrag()"
                @click.stop="if (!isDragging) { if (zoomLevel === 1) { zoomLevel = 2; } else { zoomLevel = 1; resetPan(); } }">
        </div>

        {{-- Bottom Hint Bar --}}
        <div class="text-center pb-2 z-20 max-w-full px-3">
            <p class="text-[11px] text-white/80 font-medium bg-black/60 px-4 py-1.5 rounded-full backdrop-blur-md border border-white/10 truncate">
                🖐️ Drag untuk geser | Scroll / Zoom (+/-) | Klik area hitam untuk menutup
            </p>
        </div>
    </div>
</div>
@endsection