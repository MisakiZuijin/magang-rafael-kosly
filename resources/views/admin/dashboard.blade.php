@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Panel Kontrol</p>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Dashboard Admin</h1>
        </div>
        <div class="bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 text-xs font-bold px-3 py-1 rounded-xl flex items-center gap-1.5 border border-blue-200 dark:border-blue-800">
            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>{{ Auth::user()->role === 'super_admin' ? 'Super Admin' : 'Admin' }}</span>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 gap-2.5">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl flex items-center justify-center text-indigo-600 dark:text-indigo-400 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Kos</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $data['total_kos'] ?? 0 }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl flex items-center justify-center text-emerald-600 dark:text-emerald-400 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Kamar</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $data['total_kamar'] }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kamar Terisi</p>
                <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $data['kamar_terisi'] }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-xl flex items-center justify-center text-amber-600 dark:text-amber-400 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kamar Kosong</p>
                <p class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ $data['kamar_kosong'] }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center gap-3 col-span-2">
            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/40 rounded-xl flex items-center justify-center text-red-600 dark:text-red-400 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex-1 flex justify-between items-center">
                <div>
                    <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Masa Sewa Habis Tempo</p>
                    <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ $data['expired_sewa']->count() }} Penghuni</p>
                </div>
                @if($data['expired_sewa']->count() > 0)
                @php
                $expiredKamarIds = $data['expired_sewa']->pluck('kamar_id')->unique()->implode(',');
                $actionRoute = request()->is('superadmin*') ? route('superadmin.pengumuman.create', ['kamar_ids' => $expiredKamarIds]) : route('admin.pengumuman.create', ['kamar_ids' => $expiredKamarIds]);
                @endphp
                <a href="{{ $actionRoute }}" class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900 transition-all animate-pulse flex items-center gap-1 shadow-xs cursor-pointer" title="Kirim Pengumuman ke Kamar Jatuh Tempo">
                    ⚡ Perlu Tindakan &rarr;
                </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Grafik Visualisasi Kamar (Occupancy Chart Bar) --}}
    @php
    $total = $data['total_kamar'] > 0 ? $data['total_kamar'] : 1;
    $pctTerisi = round(($data['kamar_terisi'] / $total) * 100);
    $pctKosong = round(($data['kamar_kosong'] / $total) * 100);
    @endphp
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3" x-data="{ pctTerisi: {{ $pctTerisi }}, pctKosong: {{ $pctKosong }} }">
        <div class="flex justify-between items-center">
            <h2 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Tingkat Okupansi Kamar</h2>
            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $pctTerisi }}% Okupansi</span>
        </div>

        <div class="w-full h-3.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden flex">
            <div class="bg-emerald-500 h-full transition-all duration-500" :style="{ width: pctTerisi + '%' }" title="Terisi: {{ $pctTerisi }}%"></div>
            <div class="bg-amber-400 h-full transition-all duration-500" :style="{ width: pctKosong + '%' }" title="Kosong: {{ $pctKosong }}%"></div>
        </div>

        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1">
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span>Terisi: <strong class="text-gray-900 dark:text-white">{{ $data['kamar_terisi'] }}</strong> ({{ $pctTerisi }}%)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                <span>Kosong: <strong class="text-gray-900 dark:text-white">{{ $data['kamar_kosong'] }}</strong> ({{ $pctKosong }}%)</span>
            </div>
        </div>
    </div>

    {{-- Sisa Waktu & Daftar Penghuni Aktif --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h2 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Masa Sewa Penghuni Kos</h2>
            <x-btn href="{{ request()->is('superadmin*') ? route('superadmin.kos.index') : route('admin.kos.index') }}" size="sm" variant="ghost" class="!min-h-[30px] !py-0.5 text-xs text-emerald-600">
                Kelola Kamar &rarr;
            </x-btn>
        </div>

        <div class="p-3">
            @if($data['penghuni_aktif']->isEmpty())
            <x-empty-state message="Belum ada penghuni aktif terdaftar." />
            @else
            <div class="space-y-2.5">
                @foreach($data['penghuni_aktif']->take(6) as $pk)
                @php
                    $isExpired = $pk->tanggal_keluar && $pk->tanggal_keluar < now();
                    $daysLeft = $pk->tanggal_keluar ? round(now()->diffInDays($pk->tanggal_keluar, false)) : null;
                @endphp
                    <div class="p-3 bg-gray-50 dark:bg-gray-800/40 rounded-xl border border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 bg-emerald-100 dark:bg-emerald-900/40 rounded-full flex items-center justify-center text-xs font-bold text-emerald-700 dark:text-emerald-300 flex-shrink-0">
                                {{ substr($pk->penghuni->nama ?? 'P', 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $pk->penghuni->nama ?? '-' }}</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 font-mono truncate">
                                    {{ $pk->kamar->kode_kamar ?? '-' }} · {{ $pk->kamar->kos->nama ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right flex-shrink-0 flex items-center gap-2">
                            <div>
                                @if($pk->tanggal_keluar)
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg {{ $isExpired ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : ($daysLeft <= 3 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300') }}">
                                    {{ $isExpired ? 'Sudah Expired' : ($daysLeft == 0 ? 'Hari Ini' : $daysLeft . ' Hari Lagi') }}
                                </span>
                                <p class="text-[10px] text-gray-400 font-mono mt-0.5">{{ $pk->tanggal_keluar->format('d M Y') }}</p>
                                @else
                                <span class="text-xs text-gray-400">-</span>
                                @endif
                            </div>

                            @if($isExpired || ($daysLeft !== null && $daysLeft <= 3))
                            @php
                            $targetRoute = request()->is('superadmin*') ? route('superadmin.pengumuman.create', ['kamar_id' => $pk->kamar_id]) : route('admin.pengumuman.create', ['kamar_id' => $pk->kamar_id]);
                            @endphp
                            <a href="{{ $targetRoute }}" title="Kirim Pengumuman Jatuh Tempo Ke Kamar {{ $pk->kamar->kode_kamar ?? '' }}" class="p-1.5 rounded-lg bg-amber-100 hover:bg-amber-200 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 transition-all active:scale-95 flex-shrink-0">
                                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16v-5.5A3.5 3.5 0 0 1 14.5 7H18v9h-3.5a3.5 3.5 0 0 1-3.5-3.5ZM6 8h2v8H6V8Zm-2 2h2v4H4v-4Z"/>
                                </svg>
                            </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Pending Payments Section --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-4 space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Menunggu Verifikasi Pembayaran</h2>
            <x-btn href="{{ request()->is('superadmin*') ? route('superadmin.pembayaran.index') : route('admin.pembayaran.index') }}" size="sm" variant="ghost" class="!min-h-[30px] !py-0.5 text-xs text-emerald-600">
                Lihat Semua &rarr;
            </x-btn>
        </div>

        @if($data['pending_payments']->isEmpty())
        <p class="text-xs text-gray-400 text-center py-4">Tidak ada konfirmasi pembayaran pending saat ini.</p>
        @else
        <div class="space-y-2">
            @foreach($data['pending_payments']->take(3) as $p)
            <div class="flex items-center justify-between p-3 bg-amber-50/60 dark:bg-amber-950/30 rounded-xl border border-amber-100 dark:border-amber-900/40">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-700 dark:text-amber-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $p->penghuniKamar->penghuni->nama ?? 'Penghuni' }}</p>
                        <p class="text-[11px] text-gray-500 font-mono">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection