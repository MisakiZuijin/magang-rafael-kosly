@extends('layouts.app')

@section('title', 'Dashboard Mitra Kos')

@section('content')
<div class="space-y-4" x-data="{ filterStatus: 'semua', selectedKosId: 'semua' }">
    {{-- Header / Welcome --}}
    <x-page-header preTitle="Selamat datang," title="{{ Auth::user()->nama }}">
        <x-slot name="action">
            @if(Auth::user()->is_pro)
            <div class="bg-gradient-to-r from-amber-500 to-orange-500 text-white text-xs font-black px-3 py-1.5 rounded-xl flex items-center gap-1.5 flex-shrink-0 shadow-sm tracking-wider">
                <svg class="w-3.5 h-3.5 text-amber-100 fill-current" viewBox="0 0 24 24">
                    <path d="M13.849 4.22c-.684-1.626-3.014-1.626-3.698 0L8.398 8.387l-4.552.361c-1.775.14-2.495 2.331-1.142 3.477l3.468 2.937-1.06 4.392c-.413 1.713 1.472 3.067 2.992 2.149L12 19.35l3.897 2.354c1.52.918 3.405-.436 2.992-2.15l-1.06-4.39 3.468-2.938c1.353-1.146.633-3.336-1.142-3.477l-4.552-.36-1.754-4.17Z" />
                </svg>
                <span>MITRA PRO</span>
            </div>
            @else
            <div class="bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 text-xs font-bold px-3 py-1.5 rounded-xl flex items-center gap-1.5 border border-emerald-200 dark:border-emerald-800 flex-shrink-0 shadow-xs">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>Mitra Kos</span>
            </div>
            @endif
        </x-slot>
    </x-page-header>

    @if(Auth::user()->is_pro)
    {{-- Card Pendapatan Bulan Ini Khusus Mitra Pro --}}
    <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-4 text-white shadow-md relative overflow-hidden">
        <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
        <div class="flex items-center justify-between gap-3 relative z-10">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-400 text-gray-950 text-[10px] font-black rounded-md uppercase tracking-wider flex items-center gap-1">
                        <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24">
                            <path d="M13.849 4.22c-.684-1.626-3.014-1.626-3.698 0L8.398 8.387l-4.552.361c-1.775.14-2.495 2.331-1.142 3.477l3.468 2.937-1.06 4.392c-.413 1.713 1.472 3.067 2.992 2.149L12 19.35l3.897 2.354c1.52.918 3.405-.436 2.992-2.15l-1.06-4.39 3.468-2.938c1.353-1.146.633-3.336-1.142-3.477l-4.552-.36-1.754-4.17Z" />
                        </svg>
                        <span>PRO</span>
                    </span>
                    <span class="text-xs font-medium text-emerald-100">Pendapatan Bulan Ini ({{ now()->locale('id')->isoFormat('MMMM Y') }})</span>
                </div>
                <p class="text-2xl font-black tracking-tight font-mono">
                    Rp {{ number_format($data['pendapatan_bulan_ini'] ?? 0, 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-emerald-100/90 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <span>Total Pendapatan Kumulatif: <strong class="font-mono text-white">Rp {{ number_format($data['pendapatan_total'] ?? 0, 0, ',', '.') }}</strong></span>
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-white/15 flex items-center justify-center flex-shrink-0 shadow-inner">
                <svg class="w-6 h-6 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Menu Pintas Mitra Pro (4 Akses Cepat Utama) --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
        <a href="{{ route('mitra.kos.index') }}" class="p-3 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col items-center justify-center text-center gap-1.5 active:scale-95 hover:border-emerald-300 dark:hover:border-emerald-700 transition-all">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <span class="text-[11px] font-bold text-gray-800 dark:text-gray-200 leading-tight">Kelola Kos</span>
        </a>
        <a href="{{ route('mitra.penghuni.index') }}" class="p-3 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col items-center justify-center text-center gap-1.5 active:scale-95 hover:border-purple-300 dark:hover:border-purple-700 transition-all">
            <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <span class="text-[11px] font-bold text-gray-800 dark:text-gray-200 leading-tight">Penghuni</span>
        </a>
        <a href="{{ route('mitra.pembayaran.index') }}" class="p-3 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col items-center justify-center text-center gap-1.5 active:scale-95 hover:border-blue-300 dark:hover:border-blue-700 transition-all">
            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <span class="text-[11px] font-bold text-gray-800 dark:text-gray-200 leading-tight">Verifikasi Bayar</span>
        </a>
        <a href="{{ route('mitra.pengumuman.create') }}" class="p-3 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col items-center justify-center text-center gap-1.5 active:scale-95 hover:border-amber-300 dark:hover:border-amber-700 transition-all">
            <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
            </div>
            <span class="text-[11px] font-bold text-gray-800 dark:text-gray-200 leading-tight">Pengumuman</span>
        </a>
    </div>
    @endif

    {{-- Stats Cards Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-2 gap-3">
        <x-stat-card label="Total Kos" value="{{ $data['total_kos'] }}" unit="Unit" color="emerald">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card label="Total Kamar" value="{{ $data['total_kamar'] }}" unit="Kamar" color="blue">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card label="Kamar Terisi" value="{{ $data['kamar_terisi'] }}" color="emerald">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card label="Kamar Kosong" value="{{ $data['kamar_kosong'] }}" color="amber">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            <span>Daftar Kos & Status Kamar</span>
        </h2>
        @if(Auth::user()->is_pro)
        <x-btn href="{{ route('mitra.kos.index') }}" size="sm" variant="ghost" class="!min-h-[30px] !py-1 text-xs text-emerald-600 dark:text-emerald-400 font-bold flex-shrink-0 hover:bg-emerald-50 dark:hover:bg-emerald-950/40">
            Kelola Kos & Kamar &rarr;
        </x-btn>
        @else
        <x-btn href="{{ route('mitra.kamar') }}" size="sm" variant="ghost" class="!min-h-[30px] !py-1 text-xs text-emerald-600 dark:text-emerald-400 font-bold flex-shrink-0 hover:bg-emerald-50 dark:hover:bg-emerald-950/40">
            Detail Kamar &rarr;
        </x-btn>
        @endif
    </div>

    <div class="flex gap-2 overflow-x-auto pb-1 m-2 no-scrollbar">
        <button @click="filterStatus = 'semua'"
            :class="filterStatus === 'semua' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800 font-semibold'"
            class="px-4 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>
            <span>Semua ({{ $data['total_kamar'] }})</span>
        </button>
        <button @click="filterStatus = 'terisi'"
            :class="filterStatus === 'terisi' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800 font-semibold'"
            class="px-4 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Terisi ({{ $data['kamar_terisi'] }})</span>
        </button>
        <button @click="filterStatus = 'kosong'"
            :class="filterStatus === 'kosong' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800 font-semibold'"
            class="px-4 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Kosong ({{ $data['kamar_kosong'] }})</span>
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
                    <span class="px-2.5 py-1 bg-black/60 backdrop-blur-md rounded-lg text-[10px] font-bold flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>Kos Mitra</span>
                    </span>
                    <span class="px-2.5 py-1 bg-emerald-600/90 backdrop-blur-md rounded-lg text-[10px] font-bold flex items-center gap-1">
                        <span>{{ $kos->kamar->count() }} Kamar</span>
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
                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800 flex items-center gap-1 flex-shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 20V6a2 2 0 00-2-2H8a2 2 0 00-2 2v14m12 0H6m12 0h2M6 20H4m10-7h.01" />
                        </svg>
                        <span>{{ $kos->kamar->count() }} Kamar</span>
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
                    <div class="text-[10px] font-mono text-gray-500 dark:text-gray-400 truncate max-w-full flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>{{ $kos->bank }} - {{ $kos->no_rekening }} (a.n {{ $kos->nama_pemilik_rekening ?? '-' }})</span>
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

                        <div class="mb-3 space-y-2">
                            {{-- Baris 1: Kode Kamar & Keterisian di Kiri, Jenis/Tipe Kamar di Kanan --}}
                            <div class="flex items-center justify-between gap-2 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap min-w-0">
                                    <span class="font-black text-xs text-gray-900 dark:text-white font-mono bg-white dark:bg-gray-800 px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center gap-1 shadow-2xs">
                                        <span>Kamar {{ $kamar->kode_kamar }}</span>
                                    </span>
                                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-full flex-shrink-0 {{ $isTerisi ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800' }}">
                                        {{ $isTerisi ? 'Terisi (' . $activePenghuniList->count() . '/' . $kamar->kapasitas . ')' : 'Kosong' }}
                                    </span>
                                </div>

                                <span class="px-2.5 py-1 text-[10px] uppercase font-bold rounded-lg flex items-center justify-center gap-1 flex-shrink-0 {{ $kamar->tipe === 'berbagi' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800' }}">
                                    @if($kamar->tipe === 'berbagi')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span>Berbagi</span>
                                    @else
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>Standar</span>
                                    @endif
                                </span>
                            </div>

                            {{-- Baris 2: Jenis Biaya Sewa --}}
                            <div class="space-y-1 pt-1">
                                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Jenis Biaya</p>
                                <div class="grid grid-cols-3 items-center gap-2 text-[10px]">
                                    <span class="text-center text-emerald-700 dark:text-emerald-300 font-bold bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/40 px-1.5 py-1 rounded-lg truncate">
                                        Rp {{ number_format($kamar->harga_per_bulan, 0, ',', '.') }}/bln
                                    </span>
                                    @if($kamar->harga_per_minggu)
                                    <span class="text-center text-purple-600 dark:text-purple-400 font-bold bg-purple-50 dark:bg-purple-950/40 border border-purple-100 dark:border-purple-900/40 px-1.5 py-1 rounded-lg truncate">
                                        Rp {{ number_format($kamar->harga_per_minggu, 0, ',', '.') }}/minggu
                                    </span>
                                    @endif
                                    @if($kamar->harga_per_hari)
                                    <span class="text-center text-blue-600 dark:text-blue-400 font-bold bg-blue-50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/40 px-1.5 py-1 rounded-lg truncate">
                                        Rp {{ number_format($kamar->harga_per_hari, 0, ',', '.') }}/hari
                                    </span>
                                    @endif
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
                            <p class="text-[11px] text-amber-700 dark:text-amber-400 font-semibold flex items-center gap-1.5 min-w-0 truncate">
                                <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                <span>Siap dihuni (Kapasitas {{ $kamar->kapasitas }} orang)</span>
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