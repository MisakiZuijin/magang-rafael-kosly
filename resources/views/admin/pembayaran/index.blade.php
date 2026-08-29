@extends('layouts.app')

@section('title', 'Verifikasi Pembayaran')

@section('content')
<div class="space-y-5" x-data="{ 
    tab: 'pending', 
    showReviewModal: false,
    showRejectReason: false,
    showImageFullscreen: false,
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
    <x-page-header title="Verifikasi Pembayaran" subtitle="Kelola konfirmasi bukti pembayaran dari anak kos" backUrl="{{ route('dashboard') }}" />

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
        $penghuniNama = $p->penghuniKamar->penghuni->nama ?? 'Anak Kos';
        $kosKamar = ($p->penghuniKamar->kamar->kode_kamar ?? '-') . ' · ' . ($p->penghuniKamar->kamar->kos->nama ?? '-');
        $jumlahFormatted = 'Rp ' . number_format($p->jumlah, 0, ',', '.');
        $tanggalFormatted = $p->tanggal_bayar ? $p->tanggal_bayar->format('d M Y') : '-';
        $buktiUrl = $p->bukti_transfer_url ? asset('storage/' . $p->bukti_transfer_url) : '';
        $isSuperAdmin = request()->is('superadmin*');
        $vUrl = $isSuperAdmin ? route('superadmin.pembayaran.verify', $p->id) : route('admin.pembayaran.verify', $p->id);
        $rUrl = $isSuperAdmin ? route('superadmin.pembayaran.reject', $p->id) : route('admin.pembayaran.reject', $p->id);
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 sm:p-5 border border-amber-200/90 dark:border-amber-900/60 shadow-sm hover:shadow-md transition-all space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200/70 dark:border-amber-900/50 uppercase tracking-wider">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Menunggu Verifikasi
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
                    @php
                    $kapasitasKamar = $p->penghuniKamar->kamar->kapasitas ?? 2;
                    @endphp
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md {{ $kapasitasKamar >= 3 ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : ($p->porsi_bayar == 50 ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300') }}">
                        {{ $kapasitasKamar >= 3 ? 'Tarif 3 Orang' : ($p->porsi_bayar == 50 ? 'Tarif 1 Orang' : 'Tarif 2 Orang') }}
                    </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3.5 border-t border-gray-100 dark:border-gray-800 text-xs">
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
                    class="!py-2 !px-4 text-xs font-bold shadow-xs hover:scale-[1.02] active:scale-95 transition-all">
                    🔍 Tinjau &amp; Verifikasi Bukti
                </x-btn>
            </div>
        </div>
        @empty
        <x-empty-state message="Tidak ada transaksi pembayaran yang menunggu verifikasi saat ini." />
        @endforelse
    </div>

    {{-- Tab Terverifikasi --}}
    <div x-show="tab === 'terverifikasi'" class="space-y-4" x-transition x-cloak>
        @forelse($terverifikasi as $p)
        @php
        $penghuniNama = $p->penghuniKamar->penghuni->nama ?? 'Anak Kos';
        $kosKamar = ($p->penghuniKamar->kamar->kode_kamar ?? '-') . ' · ' . ($p->penghuniKamar->kamar->kos->nama ?? '-');
        $jumlahFormatted = 'Rp ' . number_format($p->jumlah, 0, ',', '.');
        $isCoveredByRoommate = $p->catatan_verifikasi && str_contains($p->catatan_verifikasi, 'Lunas (Dibayar');
        $uploaderName = $isCoveredByRoommate ? trim(preg_replace('/^Lunas \(Dibayar (?:Full|Tarif 2 Orang|Tarif 3 Orang|Tarif 1 Kamar) oleh (.+)\)$/', '$1', $p->catatan_verifikasi)) : null;
        $tanggalFormatted = $p->tanggal_bayar ? $p->tanggal_bayar->format('d M Y') : ($p->tanggal_verifikasi ? $p->tanggal_verifikasi->format('d M Y') : '-');
        $buktiUrl = $p->bukti_transfer_url ? asset('storage/' . $p->bukti_transfer_url) : '';
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 sm:p-5 border border-emerald-100 dark:border-emerald-900/40 shadow-sm space-y-4">
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
                    @if($isCoveredByRoommate)
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                        Wakil: {{ $uploaderName }}
                    </span>
                    @elseif($p->penghuniKamar && $p->penghuniKamar->kamar && $p->penghuniKamar->kamar->tipe === 'berbagi')
                    @php
                    $kapasitasKamar = $p->penghuniKamar->kamar->kapasitas ?? 2;
                    @endphp
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md {{ $kapasitasKamar >= 3 ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : ($p->porsi_bayar == 50 ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300') }}">
                        {{ $kapasitasKamar >= 3 ? 'Tarif 3 Orang' : ($p->porsi_bayar == 50 ? 'Tarif 1 Orang' : 'Tarif 2 Orang') }}
                    </span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-6 items-center gap-3 pt-3.5 border-t border-gray-100 dark:border-gray-800 text-xs">
                <div class="grid col-span-3">
                    @if($isCoveredByRoommate)
                    <span class="text-blue-600 dark:text-blue-400 font-mono text-xs font-medium items-center gap-1.5">
                        <span>👤 Dibayar oleh: <strong>{{ $uploaderName }}</strong> ({{ $tanggalFormatted }})</span>
                    </span>
                    @else
                    <span class="grid grid-cols-1 sm:grid-cols-7 text-left text-gray-500 dark:text-gray-400 font-mono text-xs items-center gap-1.5">
                        <svg class="col-span-1 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="col-span-6">Tgl: {{ $tanggalFormatted }}</span>
                    </span>
                    @endif
                </div>

                <div class="grid col-span-3">
                    @if($buktiUrl)
                    <button type="button" @click="selectedPenghuni = '{{ addslashes($penghuniNama) }}'; selectedBuktiUrl = '{{ $buktiUrl }}'; openFullscreen();" class="items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 font-bold bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all">
                        <span>🔍 Bukti Transfer</span>
                    </button>
                    @endif
                </div>

                <div class="grid col-start-2 col-span-4">
                    <a href="{{ route('pembayaran.nota', $p->kode_invoice ?? $p->id) }}" class="items-center text-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-300 font-bold bg-emerald-50 dark:bg-emerald-950/60 px-3 py-1 rounded-xl border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition-all shadow-2xs">
                        <span>📄 Nota Pembayaran</span>
                    </a>
                </div>
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
        $penghuniNama = $p->penghuniKamar->penghuni->nama ?? 'Anak Kos';
        $kosKamar = ($p->penghuniKamar->kamar->kode_kamar ?? '-') . ' · ' . ($p->penghuniKamar->kamar->kos->nama ?? '-');
        $jumlahFormatted = 'Rp ' . number_format($p->jumlah, 0, ',', '.');
        $tanggalFormatted = $p->tanggal_bayar ? $p->tanggal_bayar->format('d M Y') : '-';
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 sm:p-5 border border-red-100 dark:border-red-900/40 shadow-sm space-y-3.5">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300 border border-red-200/70 dark:border-red-900/50 uppercase tracking-wider">
                        ✕ Ditolak
                    </span>
                    <h3 class="font-bold text-base text-gray-900 dark:text-white pt-0.5">{{ $penghuniNama }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>{{ $kosKamar }}</span>
                    </p>
                </div>
                <span class="font-bold font-mono text-red-600 dark:text-red-400 text-base sm:text-lg">
                    {{ $jumlahFormatted }}
                </span>
            </div>

            @if($p->catatan)
            <div class="p-3.5 bg-red-50/80 dark:bg-red-950/30 rounded-xl text-xs text-red-700 dark:text-red-300 border border-red-100 dark:border-red-900/50 leading-relaxed">
                <span class="font-bold block mb-0.5">Alasan Penolakan:</span>
                <p>{{ $p->catatan }}</p>
            </div>
            @endif
        </div>
        @empty
        <x-empty-state message="Belum ada transaksi pembayaran yang ditolak." />
        @endforelse
    </div>

    {{-- Modal Tinjau Bukti Pembayaran --}}
    <x-modal show="showReviewModal" title="Tinjau Bukti Pembayaran">
        <div class="space-y-5">
            {{-- Display Gambar Bukti Pembayaran --}}
            <div class="bg-gray-50 dark:bg-gray-800/70 rounded-2xl p-3 border border-gray-200/80 dark:border-gray-700 text-center overflow-hidden relative shadow-inner">
                <template x-if="selectedBuktiUrl">
                    <div class="relative cursor-pointer group" @click="openFullscreen()">
                        <img :src="selectedBuktiUrl" class="w-full max-h-[320px] object-contain rounded-xl shadow-xs mx-auto transition-transform duration-200 group-hover:scale-[1.01]" alt="Bukti Transfer">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity rounded-xl flex items-center justify-center gap-2 text-white text-xs font-bold backdrop-blur-[2px]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                            </svg>
                            <span>🔍 Klik untuk Perbesar / Zoom</span>
                        </div>
                    </div>
                </template>
                <template x-if="!selectedBuktiUrl">
                    <div class="py-10 text-xs text-gray-400 font-medium">
                        (User tidak menyertakan foto bukti transfer)
                    </div>
                </template>
            </div>

            {{-- Detail Rincian Pembayaran --}}
            <div class="bg-gray-50/90 dark:bg-gray-800/50 rounded-2xl p-4 border border-gray-200/70 dark:border-gray-700/70 space-y-2.5 text-xs">
                <div class="flex justify-between items-center py-0.5">
                    <span class="text-gray-500 dark:text-gray-400 font-semibold">Nama Penghuni</span>
                    <span class="font-bold text-gray-900 dark:text-white" x-text="selectedPenghuni"></span>
                </div>
                <div class="flex justify-between items-center py-0.5 border-t border-gray-200/50 dark:border-gray-700/50">
                    <span class="text-gray-500 dark:text-gray-400 font-semibold">Kamar &amp; Kos</span>
                    <span class="font-mono text-gray-800 dark:text-gray-200" x-text="selectedKosKamar"></span>
                </div>
                <div class="flex justify-between items-center py-0.5 border-t border-gray-200/50 dark:border-gray-700/50">
                    <span class="text-gray-500 dark:text-gray-400 font-semibold">Jumlah Nominal</span>
                    <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 text-sm" x-text="selectedJumlah"></span>
                </div>
                <div class="flex justify-between items-center py-0.5 border-t border-gray-200/50 dark:border-gray-700/50">
                    <span class="text-gray-500 dark:text-gray-400 font-semibold">Tanggal Transfer</span>
                    <span class="font-mono text-gray-700 dark:text-gray-300 font-medium" x-text="selectedTanggal"></span>
                </div>
            </div>

            {{-- Form Penolakan --}}
            <div x-show="showRejectReason" x-transition class="space-y-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                <label class="block text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-wider">
                    Alasan Penolakan Pembayaran
                </label>
                <form :action="rejectUrl" method="POST" class="space-y-3">
                    @csrf
                    <textarea name="catatan" rows="3" required placeholder="Tuliskan catatan penolakan untuk anak kos..."
                        class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-red-200 dark:border-red-800/60 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500/20 focus:outline-none"></textarea>

                    <div class="flex gap-2.5">
                        <x-btn type="button" variant="secondary" size="sm" @click="showRejectReason = false" class="flex-1 !min-h-[38px] text-xs font-bold">
                            Batal
                        </x-btn>
                        <x-btn type="submit" variant="danger" size="sm" class="flex-1 !min-h-[38px] text-xs font-bold">
                            Kirim Penolakan
                        </x-btn>
                    </div>
                </form>
            </div>

            {{-- Action Buttons --}}
            <div x-show="!showRejectReason" class="flex flex-col sm:flex-row gap-2.5 pt-3 border-t border-gray-100 dark:border-gray-800">
                <form :action="verifyUrl" method="POST" class="flex-1">
                    @csrf
                    <x-btn type="submit" variant="primary" size="sm" class="w-full !min-h-[42px] text-xs font-bold shadow-xs">
                        ✓ Konfirmasi Verifikasi
                    </x-btn>
                </form>

                <x-btn type="button" variant="danger" size="sm" @click="showRejectReason = true" class="flex-1 !min-h-[42px] text-xs font-bold shadow-xs">
                    ✕ Tolak Pembayaran
                </x-btn>

                <x-btn type="button" variant="secondary" size="sm" @click="showReviewModal = false" class="sm:flex-none sm:px-4 !min-h-[42px] text-xs font-bold">
                    Tutup
                </x-btn>
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