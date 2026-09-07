@extends('layouts.app')

@section('title', 'Hasil Filter Laporan - Mitra Pro')

@section('content')
<div class="space-y-4">
    <x-page-header title="Hasil Filter Laporan" subtitle="Periode: {{ \Carbon\Carbon::parse($start)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($end)->format('d M Y') }}" backUrl="{{ route('mitra.laporan.index') }}" />

    {{-- Download button with parameters --}}
    <div class="grid grid-cols-1">
        <x-btn href="{{ route('mitra.laporan.export', ['start' => $start, 'end' => $end]) }}" variant="secondary" size="sm" class="!min-h-[36px] !py-1 text-xs flex items-center justify-center gap-1.5 border border-emerald-500/40 text-emerald-700 dark:text-emerald-300 bg-emerald-50/50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 transition-all font-bold">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Download Laporan Periode Ini (.xls)</span>
        </x-btn>
    </div>

    {{-- Ringkasan Pendapatan --}}
    <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-4 text-white shadow-md">
        <span class="text-xs text-emerald-100 uppercase tracking-wider font-bold">Total Pendapatan Terverifikasi</span>
        <p class="text-2xl font-black font-mono mt-1">
            Rp {{ number_format($pembayarans->sum('jumlah'), 0, ',', '.') }}
        </p>
        <p class="text-xs text-emerald-100 mt-0.5">Total {{ $pembayarans->count() }} transaksi dalam rentang periode ini.</p>
    </div>

    {{-- Daftar Transaksi --}}
    <div class="space-y-3">
        <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Rincian Transaksi Pembayaran</h3>
        @forelse($pembayarans as $p)
        @php
        $penghuniNama = $p->penghuniKamar->penghuni->nama ?? 'Penghuni';
        $kosKamar = ($p->penghuniKamar->kamar->kode_kamar ?? '-') . ' · ' . ($p->penghuniKamar->kamar->kos->nama ?? '-');
        $nominal = number_format($p->jumlah, 0, ',', '.');
        $tglBayar = $p->tanggal_bayar ? $p->tanggal_bayar->format('d M Y') : ($p->created_at ? $p->created_at->format('d M Y') : '-');
        @endphp
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center justify-between gap-2">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $penghuniNama }}</p>
                <p class="text-[11px] text-gray-400 font-mono truncate">{{ $kosKamar }}</p>
                <p class="text-[10px] text-gray-400 mt-0.5">Tgl: {{ $tglBayar }}</p>
            </div>
            <div class="text-right flex-shrink-0">
                <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 text-xs block">Rp {{ $nominal }}</span>
                <span class="inline-block mt-0.5 px-2 py-0.5 text-[9px] font-bold rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Lunas</span>
            </div>
        </div>
        @empty
        <x-empty-state message="Tidak ada data pembayaran yang ditemukan pada rentang tanggal ini." />
        @endforelse
    </div>
</div>
@endsection
