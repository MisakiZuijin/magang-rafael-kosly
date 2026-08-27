@extends('layouts.app')

@section('title', 'Dashboard Mitra Kos')

@section('content')
<div class="space-y-4" x-data="{ filterStatus: 'semua', selectedKosId: 'semua' }">
    {{-- Header / Welcome --}}
    <x-page-header preTitle="Selamat datang," title="{{ Auth::user()->nama }}">
        <x-slot name="action">
            <div class="bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 text-xs font-bold px-3 py-1.5 rounded-xl flex items-center gap-1.5 border border-emerald-200 dark:border-emerald-800 flex-shrink-0 shadow-xs">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>Mitra Kos</span>
            </div>
        </x-slot>
    </x-page-header>

    {{-- Stats Cards Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-2 gap-3">
        <x-stat-card label="Total Kos" value="{{ $data['total_kos'] }}" unit="Unit" color="emerald">
            <x-slot name="icon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card label="Total Kamar" value="{{ $data['total_kamar'] }}" unit="Kamar" color="blue">
            <x-slot name="icon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card label="Kamar Terisi" value="{{ $data['kamar_terisi'] }}" color="emerald">
            <x-slot name="icon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card label="Kamar Kosong" value="{{ $data['kamar_kosong'] }}" color="amber">
            <x-slot name="icon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-stat-card>
    </div>

    {{-- Occupancy Progress Bar --}}
    @php
    $occupancyRate = $data['total_kamar'] > 0 ? round(($data['kamar_terisi'] / $data['total_kamar']) * 100) : 0;
    @endphp
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-2" x-data="{ rate: {{ $occupancyRate }} }">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-2 min-w-0">
                <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0 animate-pulse"></div>
                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate">Tingkat Okupansi Seluruh Kamar Kos</span>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-black font-mono bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex-shrink-0 ml-2" x-text="rate + '%'">
                {{ $occupancyRate }}%
            </span>
        </div>
        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-3 overflow-hidden p-0.5 border border-gray-100 dark:border-gray-800">
            <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-2 rounded-full transition-all duration-500" :style="{ width: rate + '%' }"></div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="flex items-center justify-between gap-2 pt-1">
        <h2 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider truncate min-w-0 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span>Daftar Kos &amp; Status Kamar</span>
        </h2>
        <x-btn href="{{ route('mitra.kamar') }}" size="sm" variant="ghost" class="!min-h-[30px] !py-1 text-xs text-emerald-600 font-bold flex-shrink-0">
            Kelola Detail Kamar &rarr;
        </x-btn>
    </div>

    <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
        <button @click="filterStatus = 'semua'"
            :class="filterStatus === 'semua' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800 font-semibold'"
            class="px-4 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all">
            Semua Kamar ({{ $data['total_kamar'] }})
        </button>
        <button @click="filterStatus = 'terisi'"
            :class="filterStatus === 'terisi' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800 font-semibold'"
            class="px-4 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all">
            Terisi ({{ $data['kamar_terisi'] }})
        </button>
        <button @click="filterStatus = 'kosong'"
            :class="filterStatus === 'kosong' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800 font-semibold'"
            class="px-4 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all">
            Kosong ({{ $data['kamar_kosong'] }})
        </button>
    </div>

    {{-- List Kos & Kamar --}}
    @if($data['kos_list']->isEmpty())
    <x-empty-state message="Anda belum mendaftarkan kos. Hubungi Admin untuk pendaftaran kos baru." />
    @else
    <div class="space-y-4">
        @foreach($data['kos_list'] as $kos)
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">

            {{-- Foto Kos Banner jika ada --}}
            @if($kos->foto)
            <div class="relative w-full h-32 sm:h-40 overflow-hidden bg-gray-900 border-b border-gray-100 dark:border-gray-800">
                <img src="{{ str_starts_with($kos->foto, 'http') ? $kos->foto : asset('storage/' . $kos->foto) }}"
                    alt="{{ $kos->nama }}"
                    class="w-full h-full object-cover object-center opacity-90">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                <div class="absolute bottom-2.5 left-3 right-3 flex justify-between items-center text-white">
                    <span class="px-2.5 py-1 bg-black/60 backdrop-blur-md rounded-lg text-[10px] font-bold">
                        🏢 Kos Mitra
                    </span>
                    <span class="px-2.5 py-1 bg-emerald-600/90 backdrop-blur-md rounded-lg text-[10px] font-bold">
                        {{ $kos->kamar->count() }} Kamar
                    </span>
                </div>
            </div>
            @endif

            {{-- Header Kos --}}
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 space-y-2.5">
                <div class="flex justify-between items-start gap-2">
                    <div class="min-w-0 flex-1">
                        <h3 class="font-bold text-base text-gray-900 dark:text-white leading-snug truncate">{{ $kos->nama }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-0.5 min-w-0">
                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="truncate">{{ $kos->alamat ?? 'Alamat tidak diisi' }}</span>
                        </p>
                    </div>
                    @if(!$kos->foto)
                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800 flex-shrink-0">
                        {{ $kos->kamar->count() }} Kamar
                    </span>
                    @endif
                </div>

                {{-- Ringkasan Kos --}}
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></span>
                            <span>Terisi: <strong class="text-gray-800 dark:text-gray-200 font-mono">{{ $kos->kamar->where('status', 'terisi')->count() }}</strong></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"></span>
                            <span>Kosong: <strong class="text-gray-800 dark:text-gray-200 font-mono">{{ $kos->kamar->where('status', 'kosong')->count() }}</strong></span>
                        </div>
                    </div>
                    @if($kos->bank && $kos->no_rekening)
                    <div class="text-[10px] font-mono text-gray-400 truncate max-w-full">
                        💳 {{ $kos->bank }} - {{ $kos->no_rekening }} (a.n {{ $kos->nama_pemilik_rekening ?? '-' }})
                    </div>
                    @endif
                </div>
            </div>

            {{-- Grid Kamar Kosong & Terisi --}}
            <div class="p-3">
                @if($kos->kamar->isEmpty())
                <p class="text-xs text-gray-400 text-center py-4">Belum ada kamar terdaftar di kos ini.</p>
                @else
                <div class="grid grid-cols-1 gap-2.5">
                    @foreach($kos->kamar as $kamar)
                    @php
                    $activePenghuniList = $kamar->penghuniKamar->where('status', 'aktif');
                    $isTerisi = $kamar->status === 'terisi' && $activePenghuniList->isNotEmpty();
                    @endphp

                    <div x-show="filterStatus === 'semua' || filterStatus === '{{ $kamar->status }}'"
                        x-transition
                        class="p-3 rounded-xl border transition-all min-w-0
                               {{ $isTerisi 
                                   ? 'bg-emerald-50/40 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-900/50' 
                                   : 'bg-gray-50/70 dark:bg-gray-800/40 border-gray-200 dark:border-gray-800' }}">

                        <div class="mb-3">
                            <div class="grid grid-cols-5 min-w-0 gap-4">
                                <div class="col-span-5 flex items-center gap-2 flex-wrap">
                                    <span class="font-black text-xs text-gray-900 dark:text-white font-mono bg-white dark:bg-gray-800 px-2 py-0.5 rounded-lg border border-gray-200 dark:border-gray-700">
                                        🚪 Kamar {{ $kamar->kode_kamar }}
                                    </span>
                                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-full flex-shrink-0 {{ $isTerisi ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' }}">
                                        {{ $isTerisi ? 'Terisi (' . $activePenghuniList->count() . '/' . $kamar->kapasitas . ')' : 'Kosong' }}
                                    </span>
                                </div>
                                <span class="col-span-2 px-1.5 py-0.5 text-center text-[10px] uppercase font-bold rounded-md {{ $kamar->tipe === 'berbagi' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' }}">
                                    {{ $kamar->tipe === 'berbagi' ? '👥 Berbagi' : '👤 Standar' }}
                                </span>
                                <div class="grid grid-cols-1 col-span-5 gap-2">
                                    <p class="text-xs text-mono text-black dark:text-white">Jenis Biaya</p>
                                    <div class="col-span-5 grid grid-cols-3 items-center gap-2 text-[10px]">
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
                            </div>
                        </div>

                        {{-- Detail Penghuni jika Terisi --}}
                        @if($isTerisi)
                        <div class="mt-2.5 pt-2 border-t border-emerald-100 dark:border-emerald-900/40 space-y-1.5">
                            <p class="text-[10px] uppercase font-bold text-emerald-700 dark:text-emerald-400 tracking-wider">Penghuni Aktif:</p>
                            @foreach($activePenghuniList as $activePenghuni)
                            @php $penghuniUser = $activePenghuni->penghuni; @endphp
                            @if($penghuniUser)
                            <div class="flex items-center justify-between gap-2 min-w-0 bg-white/70 dark:bg-gray-800/60 p-2 rounded-lg border border-emerald-100 dark:border-emerald-900/30">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <img src="{{ $penghuniUser->foto_profile ? asset('storage/' . $penghuniUser->foto_profile) : 'https://ui-avatars.com/api/?name=' . urlencode($penghuniUser->nama) . '&background=10b981&color=fff' }}"
                                        class="w-7 h-7 rounded-full object-cover ring-1 ring-emerald-500/30 flex-shrink-0"
                                        alt="{{ $penghuniUser->nama }}">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate">{{ $penghuniUser->nama }}</p>
                                        <p class="text-[10px] text-emerald-700 dark:text-emerald-400 font-mono truncate">
                                            {{ ucfirst($activePenghuni->durasi) }} · s/d {{ $activePenghuni->tanggal_keluar ? $activePenghuni->tanggal_keluar->format('d M Y') : '-' }}
                                        </p>
                                    </div>
                                </div>

                                @if($penghuniUser->no_hp)
                                @php
                                $phone = preg_replace('/[^0-9]/', '', $penghuniUser->no_hp);
                                if(str_starts_with($phone, '0')) {
                                $phone = '62' . substr($phone, 1);
                                }
                                $waMessage = rawurlencode("Halo Kak {$penghuniUser->nama}, pengingat dari Pemilik Kos {$kos->nama} mengenai Kamar {$kamar->kode_kamar}.");
                                @endphp
                                <a href="https://wa.me/{{ $phone }}?text={{ $waMessage }}"
                                    target="_blank"
                                    class="px-2 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold flex items-center gap-1 flex-shrink-0 transition-transform active:scale-95 shadow-xs"
                                    title="Kirim Pesan WhatsApp">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                                    </svg>
                                    <span>WhatsApp</span>
                                </a>
                                @endif
                            </div>
                            @endif
                            @endforeach
                        </div>
                        @else
                        <div class="mt-2.5 pt-2 border-t border-gray-200/60 dark:border-gray-800">
                            <p class="text-[11px] text-amber-700 dark:text-amber-400 font-semibold flex items-center gap-1 min-w-0 truncate">
                                🏠 Siap dihuni (Kapasitas {{ $kamar->kapasitas }} orang)
                            </p>
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
</div>
@endsection