@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div x-data="{ showCheckoutModal: false, showImageModal: false, modalImageSrc: '' }" class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Selamat datang,</p>
            <h1 class="text-xl font-bold dark:text-white">{{ Auth::user()->nama }}</h1>
        </div>
    </div>

    @if($data['kos'])
    {{-- Info Card --}}
    <x-card class="border-l-4 border-l-emerald-500 space-y-3">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Kos Anda</p>
                <h2 class="font-bold text-lg leading-tight dark:text-white">{{ $data['kos']->nama }}</h2>
                <div class="flex items-center gap-2 mt-1 flex-wrap">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Kamar {{ $data['kamar']->kode_kamar }}</p>
                    <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                        Penghuni : {{ $data['jumlah_penghuni'] }}/{{ $data['kamar']->kapasitas }} Orang
                    </span>
                </div>
            </div>
            <x-badge type="{{ $data['is_future'] ? 'info' : 'success' }}">{{ $data['is_future'] ? 'Reservasi' : ucfirst($data['durasi']) }}</x-badge>
        </div>

        @php
        $kamarFotos = is_array($data['kamar']->foto) ? array_values($data['kamar']->foto) : [];
        $kamarFotoUrls = array_map(function($f) {
        return str_starts_with($f, 'http') ? $f : asset('storage/' . $f);
        }, $kamarFotos);
        @endphp

        @if(!empty($kamarFotoUrls))
        {{-- Half-height Animated Room Photo Banner (without thumbnail cards below) --}}
        <div class="relative w-full h-32 sm:h-36 bg-gray-900 rounded-xl overflow-hidden shadow-sm group"
            x-data="{
                 photoIndex: 0,
                 photoList: {{ json_encode($kamarFotoUrls) }},
                 photoTimer: null,
                 startPhotoTimer() {
                     this.stopPhotoTimer();
                     if (this.photoList.length > 1) {
                         this.photoTimer = setInterval(() => {
                             this.photoIndex = (this.photoIndex + 1) % this.photoList.length;
                         }, 3000);
                     }
                 },
                 stopPhotoTimer() {
                     if (this.photoTimer) {
                         clearInterval(this.photoTimer);
                         this.photoTimer = null;
                     }
                 }
             }"
            x-init="startPhotoTimer()"
            @mouseenter="stopPhotoTimer()"
            @mouseleave="startPhotoTimer()">

            <template x-for="(url, idx) in photoList" :key="idx">
                <div x-show="photoIndex === idx"
                    x-transition:enter="transition ease-out duration-700"
                    x-transition:enter-start="opacity-0 scale-105"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-500"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute inset-0 w-full h-full">
                    <img :src="url" :alt="'Foto Kamar ' + (idx + 1)" class="w-full h-full object-cover cursor-pointer" @click="modalImageSrc = url; showImageModal = true">
                </div>
            </template>

            {{-- Top-Left Badge --}}
            <div class="absolute top-2 left-2 bg-black/60 backdrop-blur-md text-white text-[9px] font-mono px-2 py-0.5 rounded-md z-10 shadow-xs">
                <span x-text="(photoIndex + 1) + '/' + photoList.length + ' Foto'"></span>
            </div>

            {{-- Bottom-Right Zoom Hint --}}
            <div class="absolute bottom-2 right-2 bg-black/70 backdrop-blur-md text-white text-[9px] font-bold px-2 py-0.5 rounded-lg grid grid-flow-col auto-cols-max items-center gap-1 shadow-sm cursor-pointer z-10"
                @click="modalImageSrc = photoList[photoIndex]; showImageModal = true">
                <span>🔍 Perbesar</span>
            </div>

            {{-- Dots Bar (no thumbnail cards) --}}
            <template x-if="photoList.length > 1">
                <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex items-center gap-1 z-10 bg-black/40 backdrop-blur-md px-1.5 py-0.5 rounded-full">
                    <template x-for="(p, i) in photoList" :key="i">
                        <button type="button" @click="photoIndex = i" class="h-1 rounded-full transition-all duration-300" :class="photoIndex === i ? 'w-3.5 bg-emerald-400' : 'w-1 bg-white/60'"></button>
                    </template>
                </div>
            </template>
        </div>
        @endif

        <x-stat-grid :items="[
            ['label' => 'Biaya Sewa', 'value' => 'Rp ' . number_format($data['total_biaya'], 0, ',', '.')],
            ['label' => 'Masa Sewa', 'value' => ucfirst($data['durasi'])],
        ]" />

        @if($data['is_future'])
        {{-- Tampilan khusus jika tanggal masuk belum tiba (Future Reservation) --}}
        <div class="p-3.5 bg-blue-50 dark:bg-blue-950/30 rounded-xl border border-blue-100 dark:border-blue-900/50 space-y-2">
            <div class="flex items-center gap-2">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                </span>
                <p class="text-[11px] font-bold text-blue-700 dark:text-blue-300 uppercase tracking-wider">
                    📅 Reservasi Sewa Mendatang
                </p>
            </div>
            <p class="text-xs text-blue-900 dark:text-blue-200 font-medium leading-relaxed">
                Pendaftaran kamar ini telah berhasil atas nama Anda untuk periode mulai
                <strong>{{ $data['tanggal_masuk']->format('d M Y') }}</strong> s/d <strong>{{ $data['tanggal_keluar']->format('d M Y') }}</strong>.
            </p>
            <div class="p-2.5 bg-white dark:bg-gray-900 rounded-lg border border-blue-200/60 dark:border-blue-900/40 flex items-center justify-between text-xs">
                <span class="text-gray-600 dark:text-gray-300 font-medium">Status Kamar:</span>
                <span class="font-bold text-blue-600 dark:text-blue-400">
                    Belum Dapat Dihuni (Mulai {{ $data['sisa_hari_masuk'] }} Hari Lagi)
                </span>
            </div>
        </div>
        @elseif($data['tanggal_keluar'])
        {{-- Countdown jika masa sewa sudah berjalan --}}
        @php
        $diff = now()->diff($data['tanggal_keluar']);
        $isExpired = now()->gt($data['tanggal_keluar']);
        $initialText = $isExpired
        ? 'Sudah habis'
        : ($diff->d > 0
        ? $diff->d . ' hari ' . $diff->h . ' jam ' . $diff->i . ' menit'
        : ($diff->h > 0
        ? $diff->h . ' jam ' . $diff->i . ' menit'
        : $diff->i . ' menit'));
        @endphp

        <div x-data="{ 
                        target: new Date('{{ $data['tanggal_keluar']->format('Y-m-d H:i:s') }}').getTime(),
                        formatted: '{{ $initialText }}',
                        timer: null,
                        start() {
                            this.update();
                            this.timer = setInterval(() => this.update(), 60000);
                        },
                        update() {
                            const distance = this.target - new Date().getTime();
                            if (distance < 0) { 
                                this.formatted = 'Sudah habis'; 
                                clearInterval(this.timer); 
                                return; 
                            }
                            const d = Math.floor(distance / (1000*60*60*24));
                            const h = Math.floor((distance % (1000*60*60*24)) / (1000*60*60));
                            const m = Math.floor((distance % (1000*60*60)) / (1000*60));
                            this.formatted = d > 0 ? `${d} hari ${h} jam ${m} menit` : (h > 0 ? `${h} jam ${m} menit` : `${m} menit`);
                        }
                    }" x-init="start()" class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-800/50 flex flex-col justify-center">
            <p class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">Sisa Waktu Masa Sewa</p>
            <p class="text-xl font-bold text-emerald-700 dark:text-emerald-300 font-mono" x-text="formatted">{{ $initialText }}</p>
        </div>
        @endif

        {{-- Tombol Checkout Self Service --}}
        <div class="pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <span class="text-xs text-gray-500 font-medium">{{ $data['is_future'] ? 'Reservasi Terjadwal' : 'Sewa Kamar Aktif' }}</span>
            <button type="button"
                @click="showCheckoutModal = true"
                class="px-3 py-1.5 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-900/50 text-red-600 dark:text-red-300 font-bold text-xs rounded-xl border border-red-200 dark:border-red-900/50 flex items-center gap-1.5 active:scale-95 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Checkout Sewa Kamar</span>
            </button>
        </div>
    </x-card>

    {{-- Rekening Card --}}
    @if($data['kos'] && $data['kos']->no_rekening)
    <x-card class="dark:text-white">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-950/60 rounded-xl flex items-center justify-center flex-shrink-0 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M6 14h2m3 0h4M5 5h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1" x-data="{ copied: false }">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">{{ $data['kos']->bank ?? 'Bank' }}</span>
                        <button type="button"
                            @click="navigator.clipboard.writeText('{{ $data['kos']->no_rekening }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 transition-all active:scale-95 cursor-pointer"
                            title="Salin Nomor Rekening">
                            <template x-if="!copied">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 4h3a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h3m0 3h6m-6 5h6m-6 4h4M9 3v4h6V3H9Z" />
                                    </svg>
                                    <span>Salin</span>
                                </span>
                            </template>
                            <template x-if="copied">
                                <span class="flex items-center gap-1 text-emerald-700 dark:text-emerald-300">
                                    <svg x-show="copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Tersalin!</span>
                                </span>
                            </template>
                        </button>
                    </div>
                    <p class="text-xs font-mono font-bold text-gray-800 dark:text-gray-200 truncate mt-0.5">{{ $data['kos']->no_rekening }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">a.n {{ $data['kos']->nama_pemilik_rekening ?? '-' }}</p>
                </div>
            </div>
            <x-btn href="{{ route('penghuni.pembayaran') }}" size="sm" class="flex-shrink-0">Bayar</x-btn>
        </div>
    </x-card>
    @endif

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('penghuni.aturan') }}" class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm active:scale-[0.98] transition-transform">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <p class="text-sm font-bold dark:text-white">Aturan</p>
            <p class="text-xs text-gray-500 mt-0.5">Lihat aturan kos</p>
        </a>
        <a href="{{ route('penghuni.pembayaran') }}" class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm active:scale-[0.98] transition-transform">
            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <p class="text-sm font-bold dark:text-white">Riwayat</p>
            <p class="text-xs text-gray-500 mt-0.5">Cek pembayaran</p>
        </a>
    </div>

    {{-- Modal Confirmation Checkout --}}
    <x-modal show="showCheckoutModal" title="Konfirmasi Checkout Sewa Kos">
        <div class="space-y-4">
            <div class="p-3.5 bg-red-50 dark:bg-red-950/30 rounded-2xl border border-red-200 dark:border-red-800/50 text-xs text-red-700 dark:text-red-300 space-y-1.5">
                <p class="font-bold text-sm">⚠️ Konfirmasi Akhiri Sewa</p>
                <p>Apakah Anda yakin ingin mengakhiri masa sewa dan checkout dari <strong>Kamar {{ $data['kamar']->kode_kamar ?? '' }}</strong> di <strong>{{ $data['kos']->nama ?? '' }}</strong>?</p>
                <p class="text-[11px] text-red-600/80 dark:text-red-400/80 leading-relaxed">
                    Setelah melakukan checkout, masa huni Anda akan diselesaikan, kamar ini akan kembali kosong untuk disewa pengguna lain, dan riwayat sewa Anda akan tersimpan di sistem.
                </p>
            </div>

            <form action="{{ route('penghuni.checkout') }}" method="POST" class="pt-2 flex justify-end gap-2">
                @csrf
                <x-btn type="button" variant="secondary" size="sm" @click="showCheckoutModal = false">Batal</x-btn>
                <x-btn type="submit" variant="danger" size="sm">Ya, Checkout Sekarang</x-btn>
            </form>
        </div>
    </x-modal>

    {{-- Lightbox Modal Foto --}}
    <x-modal show="showImageModal" title="Detail Foto Kamar">
        <div class="space-y-3 text-center">
            <div class="relative w-full max-h-[75vh] overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800 bg-black grid place-items-center">
                <img :src="modalImageSrc" alt="Foto Kamar" class="max-w-full max-h-[75vh] object-contain rounded-xl">
            </div>
            <div class="pt-2 grid justify-items-end">
                <x-btn type="button" variant="secondary" size="sm" @click="showImageModal = false">Tutup</x-btn>
            </div>
        </div>
    </x-modal>
    @else
    <x-empty-state message="Anda belum terdaftar di kamar manapun." />
    @endif
</div>
@endsection