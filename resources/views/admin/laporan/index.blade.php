@extends('layouts.app')

@php
$isSuperAdmin = request()->is('superadmin*');
$filterRoute = $isSuperAdmin ? route('superadmin.laporan.filter') : route('admin.laporan.filter');
$exportRoute = $isSuperAdmin ? route('superadmin.laporan.export') : route('admin.laporan.export');
@endphp

@section('title', 'Laporan & Aktivitas')

@section('content')
<div class="space-y-4" x-data="{ tab: 'grafik' }">
    {{-- Header --}}
    <x-page-header title="Laporan & Aktivitas" subtitle="Grafik kamar, aktivitas bayar, log per kos, & log aktivitas sistem" backUrl="{{ route('dashboard') }}">
        @slot('action')
        <x-btn href="{{ $exportRoute }}" variant="secondary" size="sm" class="!min-h-[36px] !py-1 text-xs flex items-center gap-1.5 border border-emerald-500/40 text-emerald-700 dark:text-emerald-300 bg-emerald-50/50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Export CSV</span>
        </x-btn>
        @endslot
    </x-page-header>

    {{-- Filter Date Form --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-2.5">
        <h2 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Filter Periode Laporan</h2>
        <form action="{{ $filterRoute }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <div>
                <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Dari Tanggal</label>
                <input type="date" name="start" value="{{ date('Y-m-01') }}" required class="w-full px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Sampai Tanggal</label>
                <input type="date" name="end" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white">
            </div>
            <div class="flex items-end">
                <x-btn type="submit" variant="primary" size="sm" class="w-full !min-h-[34px] !py-1 text-xs">
                    Filter Laporan
                </x-btn>
            </div>
        </form>
    </div>

    {{-- Sub Tabs --}}
    <div class="grid grid-cols-2 sm:grid-cols-2 gap-1.5 p-1 bg-gray-100 dark:bg-gray-800/80 rounded-xl">
        <button @click="tab = 'grafik'"
            :class="tab === 'grafik' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400 font-medium hover:bg-gray-200/60 dark:hover:bg-gray-700/60 dark:hover:text-gray-200'"
            class="py-2 text-xs rounded-lg transition-all text-center">
            Grafik Kamar
        </button>
        <button @click="tab = 'pembayaran'"
            :class="tab === 'pembayaran' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400 font-medium hover:bg-gray-200/60 dark:hover:bg-gray-700/60 dark:hover:text-gray-200'"
            class="py-2 text-xs rounded-lg transition-all text-center">
            Aktivitas Bayar
        </button>
        <button @click="tab = 'perkos'"
            :class="tab === 'perkos' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400 font-medium hover:bg-gray-200/60 dark:hover:bg-gray-700/60 dark:hover:text-gray-200'"
            class="py-2 text-xs rounded-lg transition-all text-center">
            Log Per Kos
        </button>
        <button @click="tab = 'log_aktivitas'"
            :class="tab === 'log_aktivitas' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400 font-medium hover:bg-gray-200/60 dark:hover:bg-gray-700/60 dark:hover:text-gray-200'"
            class="py-2 text-xs rounded-lg transition-all text-center">
            Log Aktivitas Sistem
        </button>
    </div>

    {{-- Tab 1: Grafik Kamar --}}
    <div x-show="tab === 'grafik'" class="space-y-3" x-transition>
        @php
        $total = $totalKamar > 0 ? $totalKamar : 1;
        $pctTerisi = round(($kamarTerisi / $total) * 100);
        $pctKosong = round(($kamarKosong / $total) * 100);
        @endphp
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-4" x-data="{ pctTerisi: {{ $pctTerisi }}, pctKosong: {{ $pctKosong }} }">
            <h3 class="font-bold text-sm text-gray-900 dark:text-white">Persentase &amp; Okupansi Kamar</h3>

            <div class="space-y-2">
                <div class="flex justify-between text-xs font-semibold">
                    <span class="text-emerald-600 dark:text-emerald-400">Kamar Terisi ({{ $kamarTerisi }})</span>
                    <span class="text-gray-500 font-mono">{{ $pctTerisi }}%</span>
                </div>
                <div class="w-full h-3 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                    <div class="bg-emerald-500 h-full transition-all duration-500" :style="{ width: pctTerisi + '%' }"></div>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between text-xs font-semibold">
                    <span class="text-amber-600 dark:text-amber-400">Kamar Kosong ({{ $kamarKosong }})</span>
                    <span class="text-gray-500 font-mono">{{ $pctKosong }}%</span>
                </div>
                <div class="w-full h-3 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                    <div class="bg-amber-400 h-full transition-all duration-500" :style="{ width: pctKosong + '%' }"></div>
                </div>
            </div>

            <div class="pt-2 border-t border-gray-100 dark:border-gray-800 text-center">
                <p class="text-xs text-gray-500">Total Keseluruhan Kapasitas: <strong class="text-gray-900 dark:text-white font-mono">{{ $totalKamar }} Kamar</strong></p>
            </div>
        </div>
    </div>

    {{-- Tab 2: Aktivitas Pembayaran --}}
    <div x-show="tab === 'pembayaran'" class="space-y-3" x-transition x-cloak>
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <h3 class="font-bold text-sm text-gray-900 dark:text-white">Riwayat Pembayaran Terverifikasi</h3>

            @if($pembayarans->isEmpty())
            <p class="text-xs text-gray-400 text-center py-4">Belum ada riwayat transaksi pembayaran.</p>
            @else
            <div class="space-y-2.5 max-h-[320px] overflow-y-auto no-scrollbar pr-0.5">
                @foreach($pembayarans as $pb)
                <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs">
                    <div>
                        <p class="font-bold text-gray-900 dark:text-white">{{ $pb->penghuniKamar->penghuni->nama ?? 'Anak Kos' }}</p>
                        <p class="text-[10px] text-gray-500 font-mono">
                            {{ $pb->penghuniKamar->kamar->kode_kamar ?? '-' }} · {{ $pb->penghuniKamar->kamar->kos->nama ?? '-' }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">Rp {{ number_format($pb->jumlah, 0, ',', '.') }}</p>
                        <p class="text-[10px] text-gray-400 font-mono">{{ $pb->tanggal_verifikasi ? $pb->tanggal_verifikasi->format('d M Y') : '-' }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Tab 3: Log Penggunaan Per Kos --}}
    <div x-show="tab === 'perkos'" class="space-y-3" x-transition x-cloak>
        @if($kosList->isEmpty())
        <x-empty-state message="Belum ada data kos terdaftar." />
        @else
        @foreach($kosList as $kos)
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white">{{ $kos->nama }}</h3>
                <span class="text-xs text-gray-500">Pemilik: <strong>{{ $kos->mitra->nama ?? '-' }}</strong></span>
            </div>

            <div class="grid grid-cols-2 gap-2 text-center">
                <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/30 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                    <p class="text-[10px] uppercase text-gray-500 font-semibold">Kamar Terisi</p>
                    <p class="text-base font-bold text-emerald-600 dark:text-emerald-400">
                        {{ $kos->kamar ? $kos->kamar->where('status', 'terisi')->count() : 0 }}
                    </p>
                </div>
                <div class="p-2.5 bg-amber-50 dark:bg-amber-950/30 rounded-xl border border-amber-100 dark:border-amber-900/50">
                    <p class="text-[10px] uppercase text-gray-500 font-semibold">Kamar Kosong</p>
                    <p class="text-base font-bold text-amber-600 dark:text-amber-400">
                        {{ $kos->kamar ? $kos->kamar->where('status', 'kosong')->count() : 0 }}
                    </p>
                </div>
            </div>
        </div>
        @endforeach
        @endif
    </div>

    {{-- Tab 4: Log Aktivitas Sistem --}}
    <div x-show="tab === 'log_aktivitas'" class="space-y-3" x-transition x-cloak>
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <div class="grid grid-cols-1 gap-3 pb-2 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white">Log Aktivitas Pengguna &amp; Sistem</h3>
                    <p class="text-[10px] text-gray-400">Catatan riwayat semua tindakan pengguna, konfirmasi pembayaran, pendaftaran, login, dll.</p>
                </div>
                <span class="px-2.5 py-1 text-[10px] text-center w-[100px] font-bold font-mono rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    {{ count($logs ?? []) }} Log Terbaru
                </span>
            </div>
            <x-log-activity-list :logs="$logs" />
        </div>
    </div>
</div>
@endsection