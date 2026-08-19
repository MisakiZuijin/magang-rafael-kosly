@extends('layouts.app')

@section('title', 'Hasil Filter Laporan - Super Admin')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Hasil Filter Laporan</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Periode: {{ request('start') }} s/d {{ request('end') }}
            </p>
        </div>
        <x-btn href="{{ route('superadmin.laporan.index') }}" variant="secondary" size="sm" class="!min-h-[36px] !py-1 text-xs">
            &larr; Kembali
        </x-btn>
    </div>

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
</div>
@endsection