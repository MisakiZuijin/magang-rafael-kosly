@extends('layouts.app')

@section('title', 'Verifikasi Pembayaran - Super Admin')

@section('content')
<div class="space-y-4" x-data="{ 
    tab: 'pending', 
    showReviewModal: false,
    showRejectReason: false,
    showImageFullscreen: false,
    zoomLevel: 1,
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
        if (this.zoomLevel > 0.8) this.zoomLevel = +(this.zoomLevel - 0.5).toFixed(1);
    },
    openFullscreen() {
        this.zoomLevel = 1;
        this.showImageFullscreen = true;
    }
}">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Verifikasi Pembayaran</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kelola konfirmasi bukti pembayaran dari anak kos</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="grid grid-cols-3 gap-1.5 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
        <button @click="tab = 'pending'"
            :class="tab === 'pending' ? 'bg-white dark:bg-gray-900 text-amber-700 dark:text-amber-400 shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400 font-medium'"
            class="py-2 text-xs rounded-lg transition-all text-center">
            Pending ({{ $pending->count() }})
        </button>
        <button @click="tab = 'terverifikasi'"
            :class="tab === 'terverifikasi' ? 'bg-white dark:bg-gray-900 text-emerald-700 dark:text-emerald-400 shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400 font-medium'"
            class="py-2 text-xs rounded-lg transition-all text-center">
            Verifikasi ({{ $terverifikasi->count() }})
        </button>
        <button @click="tab = 'ditolak'"
            :class="tab === 'ditolak' ? 'bg-white dark:bg-gray-900 text-red-700 dark:text-red-400 shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400 font-medium'"
            class="py-2 text-xs rounded-lg transition-all text-center">
            Ditolak ({{ $ditolak->count() }})
        </button>
    </div>

    {{-- Tab Pending --}}
    <div x-show="tab === 'pending'" class="space-y-3" x-transition>
        @forelse($pending as $p)
        @php
        $penghuniNama = $p->penghuniKamar->penghuni->nama ?? 'Anak Kos';
        $kosKamar = ($p->penghuniKamar->kamar->kode_kamar ?? '-') . ' · ' . ($p->penghuniKamar->kamar->kos->nama ?? '-');
        $jumlahFormatted = 'Rp ' . number_format($p->jumlah, 0, ',', '.');
        $tanggalFormatted = $p->tanggal_bayar ? $p->tanggal_bayar->format('d M Y') : '-';
        $buktiUrl = $p->bukti_transfer_url ? asset('storage/' . $p->bukti_transfer_url) : '';
        $vUrl = route('superadmin.pembayaran.verify', $p->id);
        $rUrl = route('superadmin.pembayaran.reject', $p->id);
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-amber-200 dark:border-amber-900/50 shadow-sm space-y-3">
            <div class="flex justify-between items-start">
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] uppercase font-bold tracking-wider text-amber-600 dark:text-amber-400">Menunggu Verifikasi</span>
                        <span class="px-2 py-0.5 rounded-lg text-[9px] font-bold {{ ($p->tipe_perpanjangan ?? 'bulanan') === 'harian' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' }}">
                            {{ ($p->tipe_perpanjangan ?? 'bulanan') === 'harian' ? 'Harian (' . ($p->jumlah_hari ?? 1) . ' Hari)' : '1 Bulan (30 Hari)' }}
                        </span>
                    </div>
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white mt-0.5">
                        {{ $penghuniNama }}
                    </h3>
                    <p class="text-xs text-gray-500 font-mono">
                        {{ $kosKamar }}
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-base font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                        {{ $jumlahFormatted }}
                    </p>
                    <p class="text-[10px] text-gray-400 font-mono">
                        {{ $tanggalFormatted }}
                    </p>
                </div>
            </div>

            {{-- Button Tinjau & Cek Bukti Pembayaran --}}
            <div class="pt-1">
                <button type="button"
                    @click="
                                selectedPenghuni = '{{ addslashes($penghuniNama) }}';
                                selectedKosKamar = '{{ addslashes($kosKamar) }}';
                                selectedJumlah = '{{ $jumlahFormatted }}';
                                selectedTanggal = '{{ $tanggalFormatted }}';
                                selectedBuktiUrl = '{{ $buktiUrl }}';
                                verifyUrl = '{{ $vUrl }}';
                                rejectUrl = '{{ $rUrl }}';
                                showRejectReason = false;
                                showReviewModal = true;
                            "
                    class="w-full py-2.5 px-4 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 font-bold text-xs rounded-xl border border-emerald-200 dark:border-emerald-800 flex items-center justify-center gap-2 active:scale-[0.98] transition-all">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>Cek Bukti Pembayaran &amp; Verifikasi</span>
                </button>
            </div>
        </div>
        @empty
        <x-empty-state message="Tidak ada pembayaran yang menunggu verifikasi." />
        @endforelse
    </div>

    {{-- Tab Terverifikasi --}}
    <div x-show="tab === 'terverifikasi'" class="space-y-3" x-transition x-cloak>
        @forelse($terverifikasi as $p)
        @php
        $penghuniNama = $p->penghuniKamar->penghuni->nama ?? 'Anak Kos';
        $kosKamar = ($p->penghuniKamar->kamar->kode_kamar ?? '-') . ' · ' . ($p->penghuniKamar->kamar->kos->nama ?? '-');
        $jumlahFormatted = 'Rp ' . number_format($p->jumlah, 0, ',', '.');
        $tanggalFormatted = $p->tanggal_bayar ? $p->tanggal_bayar->format('d M Y') : '-';
        $buktiUrl = $p->bukti_transfer_url ? asset('storage/' . $p->bukti_transfer_url) : '';
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-2">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white">
                        {{ $penghuniNama }}
                    </h3>
                    <p class="text-xs text-gray-500 font-mono">
                        {{ $kosKamar }}
                    </p>
                    <p class="text-xs text-gray-500 font-mono">
                        {{ $p->tanggal_bayar ? $p->tanggal_bayar->format('d-m-Y') : '-' }}
                    </p>
                </div>
                <x-badge type="success">
                    Terverifikasi
                </x-badge>
            </div>

            <div class="flex justify-between items-center text-xs pt-2 border-t border-gray-100 dark:border-gray-800">
                <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400">
                    {{ $jumlahFormatted }}
                </span>
                @if($buktiUrl)
                <button type="button" @click="selectedPenghuni = '{{ addslashes($penghuniNama) }}'; selectedBuktiUrl = '{{ $buktiUrl }}'; openFullscreen();" class="text-xs text-emerald-600 font-bold hover:underline">
                    🔍 Lihat Bukti (Zoom/Full)
                </button>
                @else
                <span class="text-gray-400 font-mono">
                    Diverifikasi oleh: {{ $p->verifier->nama ?? 'Super Admin' }}
                </span>
                @endif
            </div>
        </div>
        @empty
        <x-empty-state message="Belum ada pembayaran yang terverifikasi." />
        @endforelse
    </div>

    {{-- Tab Ditolak --}}
    <div x-show="tab === 'ditolak'" class="space-y-3" x-transition x-cloak>
        @forelse($ditolak as $p)
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-red-200 dark:border-red-900/50 shadow-sm space-y-2">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white">
                        {{ $p->penghuniKamar->penghuni->nama ?? 'Anak Kos' }}
                    </h3>
                    <p class="text-xs text-gray-500 font-mono">
                        {{ $p->penghuniKamar->kamar->kode_kamar ?? '-' }} · {{ $p->penghuniKamar->kamar->kos->nama ?? '-' }}
                    </p>
                </div>
                <x-badge type="danger">Ditolak</x-badge>
            </div>

            <div class="p-2.5 bg-red-50/50 dark:bg-red-950/30 rounded-xl border border-red-100 dark:border-red-900/40 text-xs">
                <span class="font-bold text-red-700 dark:text-red-300">Catatan Penolakan:</span>
                <p class="text-red-600 dark:text-red-400 mt-0.5">{{ $p->catatan ?? '-' }}</p>
            </div>
        </div>
        @empty
        <x-empty-state message="Tidak ada catatan pembayaran yang ditolak." />
        @endforelse
    </div>

    {{-- Modal Tinjau Bukti & Verifikasi Pembayaran --}}
    <x-modal show="showReviewModal" title="Tinjau Bukti Pembayaran">
        <div class="space-y-4">
            {{-- Display Gambar Bukti Pembayaran --}}
            <div class="bg-gray-100 dark:bg-gray-800 rounded-2xl p-2 border border-gray-200 dark:border-gray-700 text-center overflow-hidden relative">
                <template x-if="selectedBuktiUrl">
                    <div class="relative cursor-pointer group" @click="openFullscreen()">
                        <img :src="selectedBuktiUrl" class="w-full max-h-[300px] object-contain rounded-xl shadow-sm mx-auto transition-transform duration-200 group-hover:scale-[1.02]" alt="Bukti Transfer">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity rounded-xl flex items-center justify-center gap-1.5 text-white text-xs font-bold backdrop-blur-[2px]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                            </svg>
                            <span>🔍 Klik untuk Perbesar / Zoom</span>
                        </div>
                    </div>
                </template>
                <template x-if="!selectedBuktiUrl">
                    <div class="py-8 text-xs text-gray-400 font-medium">
                        (User tidak menyertakan foto bukti transfer)
                    </div>
                </template>
            </div>

            {{-- Detail Rincian Pembayaran --}}
            <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3 border border-gray-100 dark:border-gray-800 space-y-1.5 text-xs">
                <div class="flex justify-between">
                    <span class="text-gray-500 font-semibold">Nama Penghuni:</span>
                    <span class="font-bold text-gray-900 dark:text-white" x-text="selectedPenghuni"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-semibold">Kamar &amp; Kos:</span>
                    <span class="font-mono text-gray-800 dark:text-gray-200" x-text="selectedKosKamar"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-semibold">Jumlah Nominal:</span>
                    <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 text-sm" x-text="selectedJumlah"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-semibold">Tanggal Bayar:</span>
                    <span class="font-mono text-gray-600 dark:text-gray-400" x-text="selectedTanggal"></span>
                </div>
            </div>

            {{-- Form Penolakan --}}
            <div x-show="showRejectReason" x-transition class="space-y-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                <label class="block text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-wider">
                    Alasan Penolakan Pembayaran
                </label>
                <form :action="rejectUrl" method="POST" class="space-y-2">
                    @csrf
                    <textarea name="catatan" rows="3" required placeholder="Tuliskan catatan penolakan untuk anak kos..."
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-red-200 dark:border-red-800/60 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-red-500 focus:outline-none"></textarea>

                    <div class="flex gap-2">
                        <x-btn type="button" variant="secondary" size="sm" @click="showRejectReason = false" class="flex-1 !min-h-[36px] text-xs">
                            Batal
                        </x-btn>
                        <x-btn type="submit" variant="danger" size="sm" class="flex-1 !min-h-[36px] text-xs">
                            Kirim Penolakan
                        </x-btn>
                    </div>
                </form>
            </div>

            {{-- Action Buttons Utama Modal --}}
            <div x-show="!showRejectReason" class="flex flex-col sm:flex-row gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                {{-- Form Confirm / Verifikasi --}}
                <form :action="verifyUrl" method="POST" class="flex-1">
                    @csrf
                    <x-btn type="submit" variant="primary" size="sm" class="w-full !min-h-[40px] text-xs font-bold">
                        ✓ Verifikasi
                    </x-btn>
                </form>

                {{-- Button Tolak Pembayaran --}}
                <x-btn type="button" variant="danger" size="sm" @click="showRejectReason = true" class="flex-1 !min-h-[40px] text-xs font-bold">
                    ✕ Tolak Pembayaran
                </x-btn>

                {{-- Button Batal / Tutup Modal --}}
                <x-btn type="button" variant="secondary" size="sm" @click="showReviewModal = false" class="flex-1 !min-h-[40px] text-xs font-bold">
                    Batal
                </x-btn>
            </div>
        </div>
    </x-modal>

    {{-- Modal Preview Gambar Fullscreen dengan Zooming --}}
    <div x-show="showImageFullscreen"
        x-cloak
        x-transition.opacity.duration.200ms
        @keydown.window.escape="showImageFullscreen = false; zoomLevel = 1;"
        @click="showImageFullscreen = false; zoomLevel = 1;"
        class="fixed inset-0 z-[150] bg-black/95 backdrop-blur-lg flex flex-col items-center justify-between p-3 select-none">

        {{-- Top Bar: Info & Zoom Controls --}}
        <div class="w-full flex items-center justify-between text-white z-20 pt-2 px-2">
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-xs font-bold font-mono bg-white/10 px-3 py-1.5 rounded-full backdrop-blur-md border border-white/10 truncate max-w-[180px]">
                    Bukti Transfer - <span x-text="selectedPenghuni"></span>
                </span>
                <span class="text-[11px] font-mono text-emerald-400 font-bold bg-white/10 px-2 py-1 rounded-lg border border-white/10" x-text="Math.round(zoomLevel * 100) + '%'"></span>
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
                <button type="button" @click.stop="zoomLevel = 1" class="px-2.5 py-1 text-[10px] font-bold text-white/90 hover:text-white bg-white/10 hover:bg-white/20 rounded-full transition-all active:scale-95" title="Reset Zoom">
                    Reset
                </button>
                <button type="button" @click="showImageFullscreen = false; zoomLevel = 1;" class="p-2 text-white/80 hover:text-white bg-white/10 hover:bg-white/20 rounded-full transition-all active:scale-95 ml-1" title="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Image Display Area with Wheel Zoom & Click Zoom --}}
        <div class="flex-1 w-full flex items-center justify-center p-2 my-auto overflow-auto no-scrollbar"
            @wheel.prevent="if ($event.deltaY < 0) { zoomIn(); } else { zoomOut(); }">
            <img :src="selectedBuktiUrl"
                :style="`transform: scale(${zoomLevel}); transition: transform 0.15s ease-out;`"
                class="max-w-full max-h-[80vh] object-contain rounded-xl shadow-2xl origin-center cursor-pointer"
                alt="Bukti Transfer Fullscreen"
                @click.stop="zoomLevel = (zoomLevel === 1 ? 2 : 1)">
        </div>

        {{-- Bottom Hint Bar --}}
        <div class="text-center pb-2 z-20">
            <p class="text-[11px] text-white/80 font-medium bg-black/60 px-4 py-1.5 rounded-full backdrop-blur-md border border-white/10">
                🔍 Klik gambar / Gunakan scroll / Tombol (+/-) untuk Zoom | Klik area hitam untuk menutup
            </p>
        </div>
    </div>
</div>
@endsection