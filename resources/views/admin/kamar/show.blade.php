@extends('layouts.app')

@section('content')
@php
$p = $isSuperAdmin ? 'superadmin.' : (Auth::user()->hasRole('mitra') ? 'mitra.' : 'admin.');
$activePenghunis = $kamar->penghuniKamar ? $kamar->penghuniKamar->where('status', 'aktif') : collect();
$isTerisi = $kamar->status === 'terisi' || $activePenghunis->isNotEmpty();
$todayDate = \Carbon\Carbon::now()->startOfDay();

$hasExpiredPenghuni = $activePenghunis->contains(function($pk) {
return $pk->tanggal_keluar && \Carbon\Carbon::parse($pk->tanggal_keluar)->setTime(14, 0, 0)->isPast();
});
$fotos = is_array($kamar->foto) ? array_values($kamar->foto) : [];
$fotoUrls = array_map(function($f) {
return str_starts_with($f, 'http') ? $f : asset('storage/' . $f);
}, $fotos);
@endphp

<div class="max-w-md mx-auto space-y-3.5 pb-10" x-data="{ 
    activePhotoIndex: 0,
    photos: {{ json_encode($fotoUrls) }},
    timer: null,
    showImageModal: false, 
    modalImageSrc: '', 
    modalImageTitle: '',
    init() {
        if (this.photos.length > 1) {
            this.startAutoSlide();
        }
    },
    startAutoSlide() {
        this.stopAutoSlide();
        this.timer = setInterval(() => {
            this.nextPhoto();
        }, 3000);
    },
    stopAutoSlide() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    },
    nextPhoto() {
        if (this.photos.length > 0) {
            this.activePhotoIndex = (this.activePhotoIndex + 1) % this.photos.length;
        }
    },
    prevPhoto() {
        if (this.photos.length > 0) {
            this.activePhotoIndex = (this.activePhotoIndex - 1 + this.photos.length) % this.photos.length;
        }
    },
    selectPhoto(index) {
        this.activePhotoIndex = index;
        this.startAutoSlide();
    },
    openImage(src, title) {
        this.modalImageSrc = src;
        this.modalImageTitle = title;
        this.showImageModal = true;
    }
}">
    {{-- Header Mobile Sticky App Bar --}}
    <x-page-header title="Detail Kamar {{ $kamar->kode_kamar }}" backUrl="{{ Auth::user()->hasRole('mitra') ? route('mitra.kamar') : route($p . 'kos.index') }}">
        <div class="grid grid-flow-col auto-cols-max items-center gap-1.5">
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg uppercase tracking-wider {{ $kamar->tipe === 'berbagi' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' }}">
                Tipe {{ ucfirst($kamar->tipe) }}
            </span>
            <x-badge type="{{ $hasExpiredPenghuni ? 'danger' : ($isTerisi ? 'success' : 'warning') }}" size="xs">
                {{ $hasExpiredPenghuni ? 'Jatuh Tempo' : ($isTerisi ? 'Terisi (' . $activePenghunis->count() . '/' . $kamar->kapasitas . ')' : 'Kosong') }}
            </x-badge>
        </div>
    </x-page-header>

    {{-- Hero Photo Gallery (Auto-sliding Animated Carousel) --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        @if(empty($fotos))
        <div class="p-6 text-center bg-gray-50/80 dark:bg-gray-800/40 border-b border-gray-100 dark:border-gray-800 grid place-items-center">
            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 rounded-xl grid place-items-center mb-2 text-lg">
                📸
            </div>
            <p class="text-xs font-bold text-gray-700 dark:text-gray-300">Belum Ada Foto Kamar</p>
            <p class="text-[10px] text-gray-400 mt-0.5">Unggah foto kamar via tombol Edit di daftar kos.</p>
        </div>
        @else
        {{-- Primary Main Image Slider --}}
        <div class="relative w-full h-56 sm:h-64 bg-gray-900 overflow-hidden group" @mouseenter="stopAutoSlide()" @mouseleave="startAutoSlide()">
            <template x-for="(photoUrl, index) in photos" :key="index">
                <div x-show="activePhotoIndex === index"
                    x-transition:enter="transition ease-out duration-700"
                    x-transition:enter-start="opacity-0 scale-105"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-500"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute inset-0 w-full h-full">
                    <img :src="photoUrl"
                        :alt="'Foto Kamar ' + index"
                        class="w-full h-full object-cover cursor-pointer"
                        @click="openImage(photoUrl, 'Foto Kamar {{ $kamar->kode_kamar }} (' + (index + 1) + '/' + photos.length + ')')">
                </div>
            </template>

            {{-- Prev / Next Arrow Controls (if > 1 photos) --}}
            <template x-if="photos.length > 1">
                <div class="absolute inset-y-0 left-0 right-0 flex items-center justify-between px-2 pointer-events-none z-10">
                    <button type="button" @click="prevPhoto()" class="pointer-events-auto w-7 h-7 bg-black/50 hover:bg-black/80 backdrop-blur-sm text-white rounded-full grid place-items-center text-xs font-bold transition-all active:scale-90 shadow-md" title="Foto Sebelumnya">
                        ‹
                    </button>
                    <button type="button" @click="nextPhoto()" class="pointer-events-auto w-7 h-7 bg-black/50 hover:bg-black/80 backdrop-blur-sm text-white rounded-full grid place-items-center text-xs font-bold transition-all active:scale-90 shadow-md" title="Foto Selanjutnya">
                        ›
                    </button>
                </div>
            </template>

            {{-- Counter Pill Top-Left --}}
            <div class="absolute top-2.5 left-2.5 bg-black/60 backdrop-blur-md text-white text-[10px] font-mono px-2 py-0.5 rounded-md z-10 shadow-sm">
                <span x-text="(activePhotoIndex + 1) + '/' + photos.length + ' Foto'"></span>
            </div>

            {{-- Zoom Hint Bottom-Right --}}
            <div class="absolute bottom-2.5 right-2.5 bg-black/70 backdrop-blur-md text-white text-[10px] font-bold px-2.5 py-1 rounded-lg grid grid-flow-col auto-cols-max items-center gap-1 shadow-sm cursor-pointer z-10"
                @click="openImage(photos[activePhotoIndex], 'Foto Kamar {{ $kamar->kode_kamar }}')">
                <span>🔍 Ketuk untuk Perbesar</span>
            </div>

            {{-- Animated Slide Indicators (Dots) --}}
            <template x-if="photos.length > 1">
                <div class="absolute bottom-2.5 left-1/2 -translate-x-1/2 flex items-center gap-1.5 z-10 bg-black/40 backdrop-blur-md px-2 py-1 rounded-full">
                    <template x-for="(photo, idx) in photos" :key="idx">
                        <button type="button"
                            @click="selectPhoto(idx)"
                            class="h-1.5 rounded-full transition-all duration-300"
                            :class="activePhotoIndex === idx ? 'w-4 bg-emerald-400' : 'w-1.5 bg-white/60 hover:bg-white'"></button>
                    </template>
                </div>
            </template>
        </div>

        {{-- Horizontal Scrollable Thumbnails (Admin/SuperAdmin can delete) --}}
        @if(count($fotos) > 1)
        <div class="p-2 bg-gray-50 dark:bg-gray-800/50 grid grid-flow-col auto-cols-max items-center gap-2 overflow-x-auto no-scrollbar border-t border-gray-100 dark:border-gray-800">
            @foreach($fotoUrls as $index => $thumbUrl)
            <div class="relative w-16 h-12 rounded-lg overflow-hidden border-2 cursor-pointer transition-all duration-200"
                :class="activePhotoIndex === {{ $index }} ? 'border-emerald-500 scale-95 shadow-md' : 'border-transparent opacity-60 hover:opacity-100'"
                @click="selectPhoto({{ $index }})">
                <img src="{{ $thumbUrl }}" alt="Thumb {{ $index + 1 }}" class="w-full h-full object-cover">

                @if(Auth::user() && Auth::user()->hasAnyRole(['admin', 'superadmin']))
                <form action="{{ route($p . 'kamar.foto.delete', $kamar->kode_kamar ?? $kamar->id) }}" method="POST" onsubmit="return confirm('Hapus foto ini?')" class="absolute top-0.5 right-0.5 z-20">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="index" value="{{ $index }}">
                    <button type="submit" class="w-4 h-4 bg-red-600 hover:bg-red-700 text-white rounded-full grid place-items-center text-[8px] font-bold shadow-xs transition-transform active:scale-90" title="Hapus Foto (Khusus Admin)">
                        ✕
                    </button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
        @endif
        @endif
    </div>

    {{-- Card 1: Header Ringkasan Kamar & Kos --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="grid grid-cols-[1fr_auto] items-start gap-2">
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Informasi Unit</p>
                <h2 class="text-base font-bold text-gray-900 dark:text-white leading-snug">Kamar {{ $kamar->kode_kamar }}</h2>
                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-bold mt-0.5">{{ $kamar->kos->nama ?? '-' }}</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] text-gray-400 uppercase font-bold">Kapasitas</p>
                <p class="text-xs font-bold text-gray-800 dark:text-gray-200 font-mono mt-0.5">{{ $kamar->kapasitas }} Orang</p>
            </div>
        </div>

        @if($kamar->kos && $kamar->kos->mitra)
        <div class="p-2.5 bg-amber-50/70 dark:bg-amber-950/30 rounded-xl border border-amber-200/60 dark:border-amber-900/40 grid grid-cols-[1fr_auto] items-center gap-2 text-xs">
            <div class="min-w-0">
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 block">Mitra Pemilik:</span>
                <span class="font-bold text-gray-900 dark:text-white truncate block">{{ $kamar->kos->mitra->nama }}</span>
            </div>
            @if($kamar->kos->mitra->no_hp)
            @php
            $cleanHp = preg_replace('/[^0-9]/', '', $kamar->kos->mitra->no_hp);
            $waHp = str_starts_with($cleanHp, '0') ? '62' . substr($cleanHp, 1) : $cleanHp;
            @endphp
            <div class="grid grid-cols-2 items-center gap-1">
                <a href="https://wa.me/{{ $waHp }}" target="_blank"
                    class="grid grid-flow-col auto-cols-max items-center gap-1 px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 dark:hover:bg-emerald-800/50 border border-emerald-200 dark:border-emerald-800 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 rounded-md transition-all active:scale-95"
                    title="Kirim WhatsApp ke Mitra">
                    <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400 fill-current" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                        <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                    </svg>
                    <span>WhatsApp</span>
                </a>
                <a href="tel:{{ $kamar->kos->mitra->no_hp }}"
                    class="grid grid-flow-col auto-cols-max items-center gap-1 px-1.5 py-0.5 bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 dark:hover:bg-blue-800/50 border border-blue-200 dark:border-blue-800 text-[10px] font-bold text-blue-700 dark:text-blue-300 rounded-md transition-all active:scale-95"
                    title="Telepon Langsung Mitra">
                    <span>📞 Telp</span>
                </a>
            </div>
            @endif
        </div>
        @endif

        <div class="pt-1 text-[11px] text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-800">
            <span class="font-bold text-gray-700 dark:text-gray-300">Alamat:</span> {{ $kamar->kos->alamat ?? 'Alamat tidak diisi' }}
        </div>
    </div>

    {{-- Card 2: Perabotan & Fasilitas Kamar --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-2">
        <div class="grid grid-cols-[auto_1fr] items-center gap-1.5 border-b border-gray-100 dark:border-gray-800 pb-2">
            <span class="text-amber-500 text-sm">📦</span>
            <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Detail Perabotan &amp; Fasilitas</h3>
        </div>

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
        <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800 text-xs">
            @if(empty($kamar->detail) || strtolower(trim($kamar->detail)) === 'kosong')
            <div class="text-center py-1">
                <span class="px-2.5 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-400 text-[11px] italic font-semibold rounded-lg inline-block">
                    Kosong (Tanpa Perabotan)
                </span>
            </div>
            @else
            <div class="flex flex-wrap items-center gap-1.5">
                @foreach($detailsList as $item)
                @php
                $icon = $getFacilityIcon($item);
                @endphp
                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 border border-amber-200/70 dark:border-amber-800/50 rounded-lg text-xs font-bold">
                    <span>{{ $icon }}</span>
                    <span>{{ $item }}</span>
                </span>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Card 3: Rincian Tarif Biaya Sewa --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-2.5">
        <div class="grid grid-cols-[auto_1fr] items-center gap-1.5 border-b border-gray-100 dark:border-gray-800 pb-2">
            <span class="text-emerald-500 text-sm">💰</span>
            <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Tarif Biaya Sewa</h3>
        </div>

        <div class="grid grid-cols-1 gap-2 text-xs">
            <div class="p-3 bg-emerald-50/70 dark:bg-emerald-950/40 rounded-xl border border-emerald-200/60 dark:border-emerald-900/40 grid grid-cols-[1fr_auto] items-center">
                <span class="font-bold text-emerald-800 dark:text-emerald-300">Bulanan</span>
                <span class="font-mono font-bold text-emerald-700 dark:text-emerald-300 text-sm">
                    Rp {{ number_format($kamar->harga_per_bulan, 0, ',', '.') }}
                </span>
            </div>

            <div class="p-3 bg-purple-50/70 dark:bg-purple-950/40 rounded-xl border border-purple-200/60 dark:border-purple-900/40 grid grid-cols-[1fr_auto] items-center">
                <span class="font-bold text-purple-800 dark:text-purple-300">Mingguan</span>
                <span class="font-mono font-bold text-purple-700 dark:text-purple-300">
                    {{ $kamar->harga_per_minggu ? 'Rp ' . number_format($kamar->harga_per_minggu, 0, ',', '.') : '-' }}
                </span>
            </div>

            <div class="p-3 bg-blue-50/70 dark:bg-blue-950/40 rounded-xl border border-blue-200/60 dark:border-blue-900/40 grid grid-cols-[1fr_auto] items-center">
                <span class="font-bold text-blue-800 dark:text-blue-300">Harian</span>
                <span class="font-mono font-bold text-blue-700 dark:text-blue-300">
                    {{ $kamar->harga_per_hari ? 'Rp ' . number_format($kamar->harga_per_hari, 0, ',', '.') : '-' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Card 4: Penghuni Aktif saat ini --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-2.5">
        <div class="grid grid-cols-[1fr_auto] items-center border-b border-gray-100 dark:border-gray-800 pb-2">
            <div class="grid grid-cols-[auto_1fr] items-center gap-1.5">
                <span class="text-purple-500 text-sm">👥</span>
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Penghuni Terdaftar</h3>
            </div>
            <span class="text-[11px] font-mono font-bold text-gray-400">
                {{ $activePenghunis->count() }}/{{ $kamar->kapasitas }} Orang
            </span>
        </div>

        @if($activePenghunis->isEmpty())
        <div class="p-4 text-center bg-gray-50 dark:bg-gray-800/40 rounded-xl border border-dashed border-gray-200 dark:border-gray-800">
            <p class="text-xs text-gray-400 italic">Belum ada penghuni aktif.</p>
        </div>
        @else
        <div class="space-y-2">
            @foreach($activePenghunis as $pk)
            @php
            $penghuniUser = $pk->penghuni;
            $tglMasuk = $pk->tanggal_masuk ? \Carbon\Carbon::parse($pk->tanggal_masuk)->format('d M Y') : '-';
            $tglKeluar = $pk->tanggal_keluar ? \Carbon\Carbon::parse($pk->tanggal_keluar)->format('d M Y') : '-';
            $targetKeluar = $pk->tanggal_keluar ? \Carbon\Carbon::parse($pk->tanggal_keluar)->setTime(14, 0, 0) : null;
            $isExpiredPenghuni = $targetKeluar && $targetKeluar->isPast();
            $overdueDays = $isExpiredPenghuni ? max(1, (int) $targetKeluar->diffInDays(now())) : 0;
            $paymentStatus = $pk->getStatusPembayaranInfo();
            @endphp
            <div class="p-3 rounded-xl border {{ $isExpiredPenghuni ? 'bg-red-50/70 border-red-200 dark:bg-red-950/30 dark:border-red-900/50' : 'bg-emerald-50/40 border-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-900/50' }} grid grid-cols-[1fr_auto] items-center gap-2 text-xs">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="font-bold text-gray-900 dark:text-white text-xs truncate">{{ $penghuniUser->nama ?? 'Penghuni' }}</span>
                        <span class="px-1.5 py-0.5 text-[9px] font-bold rounded uppercase {{ $isExpiredPenghuni ? 'bg-red-100 text-red-700 dark:bg-red-900/60 dark:text-red-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300' }}">
                            {{ $isExpiredPenghuni ? "Terlewat {$overdueDays} Hari" : 'Aktif' }}
                        </span>
                        <span class="px-1.5 py-0.5 text-[9px] font-bold rounded uppercase {{ $paymentStatus['badge_class'] }}">
                            {{ $paymentStatus['label'] }}
                        </span>
                    </div>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 font-mono mt-0.5">
                        Sewa {{ ucfirst($pk->durasi) }} · s/d {{ $tglKeluar }} (Batas Checkout 14.00 WIB)
                    </p>
                </div>

                @if($penghuniUser && $penghuniUser->no_hp)
                @php
                $cleanHpP = preg_replace('/[^0-9]/', '', $penghuniUser->no_hp);
                $waHpP = str_starts_with($cleanHpP, '0') ? '62' . substr($cleanHpP, 1) : $cleanHpP;
                @endphp
                <a href="https://wa.me/{{ $waHpP }}" target="_blank" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-[10px] grid grid-flow-col auto-cols-max items-center gap-1 active:scale-95 transition-transform">
                    <svg class="w-3 h-3 text-white fill-current" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                        <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                    </svg>
                    <span>WhatsApp</span>
                </a>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Card 5: WhatsApp Group --}}
    @if($kamar->link_grup_wa || $kamar->wa_group_id)
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-2.5">
        <div class="grid grid-cols-[auto_1fr] items-center gap-1.5 border-b border-gray-100 dark:border-gray-800 pb-2">
            <span class="text-emerald-500 text-sm">💬</span>
            <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Grup WhatsApp Kamar</h3>
        </div>

        <div class="space-y-2 text-xs">
            @if($kamar->link_grup_wa)
            <a href="{{ $kamar->link_grup_wa }}" target="_blank" class="w-full py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl grid place-items-center active:scale-95 transition-transform text-xs shadow-xs">
                <span class="grid grid-flow-col auto-cols-max items-center gap-1.5">
                    <svg class="w-4 h-4 text-white fill-current" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                        <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                    </svg>
                    <span>Join Link Grup WhatsApp Kamar</span>
                </span>
            </a>
            @endif

            @if($kamar->wa_group_id)
            <div class="p-2.5 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700">
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block">ID Grup Fonnte:</span>
                <span class="font-mono text-[11px] font-bold text-gray-800 dark:text-gray-200 select-all block break-all">{{ $kamar->wa_group_id }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Lightbox Modal --}}
    <x-modal show="showImageModal" title="Detail Foto Kamar">
        <div class="space-y-3 text-center">
            <p class="text-xs font-bold text-gray-700 dark:text-gray-300" x-text="modalImageTitle"></p>
            <div class="relative w-full max-h-[75vh] overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800 bg-black grid place-items-center">
                <img :src="modalImageSrc" :alt="modalImageTitle" class="max-w-full max-h-[75vh] object-contain rounded-xl">
            </div>
            <div class="pt-2 grid justify-items-end">
                <x-btn type="button" variant="secondary" size="sm" @click="showImageModal = false">Tutup</x-btn>
            </div>
        </div>
    </x-modal>
</div>
@endsection