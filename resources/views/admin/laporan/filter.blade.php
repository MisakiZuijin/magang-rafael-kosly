@extends('layouts.app')

@php
$isSuperAdmin = request()->is('superadmin*');
$backRoute = $isSuperAdmin ? route('superadmin.laporan.index') : route('admin.laporan.index');
$exportRoute = $isSuperAdmin ? route('superadmin.laporan.export') : route('admin.laporan.export');
$exportUrl = $exportRoute . '?start=' . request('start') . '&end=' . request('end');
@endphp

@section('title', 'Hasil Filter Laporan')

@section('content')
<div class="space-y-4">
    <div class="grid grid-cols-1 gap-2">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Hasil Filter Laporan</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Periode: {{ request('start') }} s/d {{ request('end') }}
            </p>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <x-btn href="{{ $backRoute }}" variant="secondary" size="sm" class="!min-h-[36px] !py-1 text-xs">
                &larr; Kembali
            </x-btn>
            <x-btn href="{{ $exportUrl }}" variant="secondary" size="sm" class="!min-h-[36px] !py-1 text-xs flex items-center gap-1.5 border border-emerald-500/40 text-emerald-700 dark:text-emerald-300 bg-emerald-50/50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </x-btn>
        </div>
    </div>

    {{-- Section 1: Pembayaran Terverifikasi --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="flex justify-between items-center pb-2 border-b border-gray-100 dark:border-gray-800">
            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Laporan Pembayaran Terverifikasi</span>
            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">
                Total: {{ $pembayarans->count() }} Transaksi
            </span>
        </div>

        @if($pembayarans->isEmpty())
        <x-empty-state message="Tidak ditemukan transaksi pembayaran pada rentang tanggal tersebut." />
        @else
        @php
        $totalNominal = $pembayarans->sum('jumlah');
        @endphp
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 rounded-xl border border-emerald-100 dark:border-emerald-900/50 flex justify-between items-center mb-3">
            <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">Total Nominal Pembayaran</span>
            <span class="text-base font-bold font-mono text-emerald-700 dark:text-emerald-300">
                Rp {{ number_format($totalNominal, 0, ',', '.') }}
            </span>
        </div>

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
                    <p class="text-[10px] text-gray-400 font-mono">{{ $pb->tanggal_bayar ? $pb->tanggal_bayar->format('d M Y') : '-' }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Section 2: Log Aktivitas Periode Terpilih --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="flex justify-between items-center pb-2 border-b border-gray-100 dark:border-gray-800">
            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Log Aktivitas Sistem (Periode Terpilih)</span>
            <span class="text-xs font-mono font-bold text-blue-600 dark:text-blue-400">
                Total: {{ count($logs ?? []) }} Log
            </span>
        </div>

        <x-log-activity-list :logs="$logs" />
    </div>
</div>
@endsection