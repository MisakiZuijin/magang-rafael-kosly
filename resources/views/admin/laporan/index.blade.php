@extends('layouts.app')

@php
$isSuperAdmin = request()->is('superadmin*');
$filterRoute = $isSuperAdmin ? route('superadmin.laporan.filter') : route('admin.laporan.filter');
$exportRoute = $isSuperAdmin ? route('superadmin.laporan.export') : route('admin.laporan.export');
@endphp

@section('title', 'Laporan & Aktivitas')

@section('content')
<div class="space-y-4" x-data="{ tab: 'grafik', searchKos: '' }">
    {{-- Header --}}
    <x-page-header title="Laporan & Aktivitas" subtitle="Grafik kamar, aktivitas bayar, log per kos, & log aktivitas sistem" backUrl="{{ route('dashboard') }}" />

    {{-- Tombol Download Excel di bawah header --}}
    <div class="grid grid-cols-1">
        <x-btn href="{{ $exportRoute }}" variant="secondary" size="sm" class="!min-h-[36px] !py-1 text-xs flex items-center justify-center gap-1.5 border border-emerald-500/40 text-emerald-700 dark:text-emerald-300 bg-emerald-50/50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 transition-all font-bold">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Download Laporan Excel (.xls)</span>
        </x-btn>
    </div>

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
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 p-1 bg-gray-100 dark:bg-gray-800/80 rounded-xl">
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
            Rekap Per Kos
        </button>
        <button @click="tab = 'log_aktivitas'"
            :class="tab === 'log_aktivitas' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400 font-medium hover:bg-gray-200/60 dark:hover:bg-gray-700/60 dark:hover:text-gray-200'"
            class="py-2 text-xs rounded-lg transition-all text-center">
            Log Aktivitas
        </button>
    </div>

    {{-- Tab 1: Grafik Kamar & Pendapatan --}}
    <div x-show="tab === 'grafik'" class="space-y-3" x-transition>
        {{-- Card 1: Okupansi Kamar --}}
        @php
        $total = $totalKamar > 0 ? $totalKamar : 1;
        $pctTerisi = round(($kamarTerisi / $total) * 100);
        $pctKosong = round(($kamarKosong / $total) * 100);
        @endphp
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-4" x-data="{ pctTerisi: {{ $pctTerisi }}, pctKosong: {{ $pctKosong }} }">
            <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>Persentase & Okupansi Kamar</span>
            </h3>

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

        {{-- Card 2: Grafik Pendapatan Per Bulan --}}
        @php
        $bulanList = [];
        $currentMonth = \Carbon\Carbon::now()->startOfMonth();
        for ($i = 5; $i >= 0; $i--) {
        $dt = $currentMonth->copy()->subMonths($i);
        $keyYm = $dt->format('Y-m');
        $labelBulan = $dt->locale('id')->isoFormat('MMMM Y');
        $shortBulan = $dt->locale('id')->isoFormat('MMM');

        $nominalBulan = $pembayarans->filter(function($p) use ($keyYm) {
        $tgl = $p->tanggal_verifikasi ?? ($p->tanggal_bayar ?? $p->created_at);
        return $tgl && \Carbon\Carbon::parse($tgl)->format('Y-m') === $keyYm;
        })->sum('jumlah');

        $countTrx = $pembayarans->filter(function($p) use ($keyYm) {
        $tgl = $p->tanggal_verifikasi ?? ($p->tanggal_bayar ?? $p->created_at);
        return $tgl && \Carbon\Carbon::parse($tgl)->format('Y-m') === $keyYm;
        })->count();

        $bulanList[] = [
        'key' => $keyYm,
        'label' => $labelBulan,
        'short' => $shortBulan,
        'nominal' => $nominalBulan,
        'count' => $countTrx,
        'isCurrent' => $i === 0,
        ];
        }
        $maxNominal = max(collect($bulanList)->max('nominal') ?: 1, 1);
        $total6Bulan = collect($bulanList)->sum('nominal');
        $avgBulan = round($total6Bulan / 6);
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-1 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        <span>Grafik Pendapatan Per Bulan</span>
                    </h3>
                    <p class="text-[10px] text-gray-400">Tren pendapatan sewa terverifikasi selama 6 bulan terakhir</p>
                </div>
                <span class="text-[10px] font-bold font-mono px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    6 Bulan Terakhir
                </span>
            </div>

            {{-- Ringkasan 6 Bulan --}}
            <div class="grid grid-cols-2 gap-2">
                <div class="p-3 bg-emerald-50/70 dark:bg-emerald-950/30 rounded-xl border border-emerald-100 dark:border-emerald-900/40">
                    <span class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider block">Total 6 Bulan</span>
                    <span class="text-sm sm:text-base font-bold font-mono text-emerald-700 dark:text-emerald-300 block mt-0.5">
                        Rp {{ number_format($total6Bulan, 0, ',', '.') }}
                    </span>
                </div>
                <div class="p-3 bg-purple-50/70 dark:bg-purple-950/30 rounded-xl border border-purple-100 dark:border-purple-900/40">
                    <span class="text-[10px] font-semibold text-purple-700 dark:text-purple-400 uppercase tracking-wider block">Rata-Rata / Bulan</span>
                    <span class="text-sm sm:text-base font-bold font-mono text-purple-700 dark:text-purple-300 block mt-0.5">
                        Rp {{ number_format($avgBulan, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- Visual Bar Chart Vertikal --}}
            <div class="pt-2">
                <div class="h-44 flex items-end justify-between gap-2 px-1 pb-2 border-b border-gray-100 dark:border-gray-800">
                    @foreach($bulanList as $bln)
                    @php
                    $pctHeight = $bln['nominal'] > 0 ? max(round(($bln['nominal'] / $maxNominal) * 100), 8) : 4;
                    @endphp
                    <div class="flex-1 flex flex-col items-center h-full justify-end group relative">
                        {{-- Nominal Label on Top --}}
                        <span class="text-[9px] font-mono font-bold text-gray-500 dark:text-gray-400 mb-1 scale-90 sm:scale-100 truncate max-w-full">
                            @if($bln['nominal'] >= 1000000)
                            {{ round($bln['nominal'] / 1000000, 1) }}jt
                            @elseif($bln['nominal'] >= 1000)
                            {{ round($bln['nominal'] / 1000) }}rb
                            @elseif($bln['nominal'] > 0)
                            {{ $bln['nominal'] }}
                            @else
                            0
                            @endif
                        </span>

                        {{-- Bar Column --}}
                        <div class="w-full max-w-[42px] bg-gray-100 dark:bg-gray-800 rounded-t-xl overflow-hidden flex items-end h-32 p-0.5">
                            <div class="w-full rounded-t-lg transition-all duration-700 {{ $bln['isCurrent'] ? 'bg-gradient-to-t from-emerald-600 to-teal-400 shadow-xs' : ($bln['nominal'] > 0 ? 'bg-gradient-to-t from-emerald-500/80 to-emerald-400/80 group-hover:from-emerald-600 group-hover:to-teal-400' : 'bg-gray-200 dark:bg-gray-700') }}"
                                :style="{ height: '{{ $pctHeight }}%' }">
                            </div>
                        </div>

                        {{-- Bulan Label --}}
                        <span class="text-[10px] font-semibold mt-2 {{ $bln['isCurrent'] ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $bln['short'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Breakdown List Rincian Tiap Bulan --}}
            <div class="space-y-2.5 pt-2 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Rincian Riwayat Bulanan</span>
                    <span class="text-[10px] text-gray-400">Terbaru &rarr; Terlama</span>
                </div>
                <div class="grid grid-cols-1 gap-2.5">
                    @foreach(array_reverse($bulanList) as $b)
                    @php
                    $isZero = $b['nominal'] <= 0;
                        @endphp
                        <div class="p-3.5 sm:p-4 rounded-2xl border transition-all flex items-center justify-between gap-3 text-xs {{ $b['isCurrent'] ? 'bg-gradient-to-r from-emerald-50/90 to-teal-50/50 dark:from-emerald-950/40 dark:to-teal-950/20 border-emerald-200 dark:border-emerald-800/60 shadow-xs' : ($isZero ? 'bg-gray-50/50 dark:bg-gray-800/20 border-gray-100 dark:border-gray-800/80 opacity-75' : 'bg-white dark:bg-gray-900 border-gray-200/80 dark:border-gray-800 shadow-2xs hover:border-emerald-200 dark:hover:border-emerald-800/50') }}">

                        {{-- Kiri: Ikon Kalender & Keterangan Bulan --}}
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 {{ $b['isCurrent'] ? 'bg-emerald-600 text-white shadow-xs' : ($isZero ? 'bg-gray-100 dark:bg-gray-800 text-gray-400' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/40') }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="font-bold text-sm text-gray-900 dark:text-white leading-none">{{ $b['label'] }}</h4>
                                    @if($b['isCurrent'])
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500 text-white leading-none tracking-wide">
                                        Bulan Ini
                                    </span>
                                    @endif
                                </div>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-1.5 font-medium">
                                    <span class="w-2 h-2 rounded-full {{ $b['isCurrent'] ? 'bg-emerald-500 animate-pulse' : ($b['nominal'] > 0 ? 'bg-teal-500' : 'bg-gray-300 dark:bg-gray-600') }}"></span>
                                    <span>{{ $b['count'] }} Transaksi Terverifikasi</span>
                                </p>
                            </div>
                        </div>

                        {{-- Kanan: Nominal Pendapatan --}}
                        <div class="text-right flex-shrink-0 pl-2">
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider block">Pendapatan</span>
                            <span class="font-mono font-black text-sm sm:text-base {{ $b['nominal'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }} block mt-0.5">
                                Rp {{ number_format($b['nominal'], 0, ',', '.') }}
                            </span>
                        </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Tab 2: Aktivitas Pembayaran --}}
<div x-show="tab === 'pembayaran'" class="space-y-3" x-transition x-cloak>
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="flex items-center justify-between pb-1 border-b border-gray-100 dark:border-gray-800">
            <h3 class="font-bold text-sm text-gray-900 dark:text-white">Riwayat Pembayaran Terverifikasi</h3>
            <span class="text-[10px] font-bold font-mono px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                {{ $pembayarans->count() }} Transaksi
            </span>
        </div>

        @if($pembayarans->isEmpty())
        <x-empty-state message="Belum ada riwayat transaksi pembayaran." />
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

{{-- Tab 3: Rekap Per Kos --}}
<div x-show="tab === 'perkos'" class="space-y-4" x-transition x-cloak>
    {{-- Search Bar --}}
    @if(!$kosList->isEmpty())
    <div class="space-y-3">
        <div class="relative">
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text"
                x-model="searchKos"
                placeholder="Cari gedung kos, mitra, atau alamat..."
                class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all shadow-sm">
        </div>
    </div>
    @endif

    {{-- Kos Cards List --}}
    @forelse($kosList as $kos)
    @php
    $totalKamarKos = $kos->kamar ? $kos->kamar->count() : 0;
    $terisiKos = $kos->kamar ? $kos->kamar->where('status', 'terisi')->count() : 0;
    $kosongKos = $kos->kamar ? $kos->kamar->where('status', 'kosong')->count() : 0;
    $pctOkupansi = $totalKamarKos > 0 ? round(($terisiKos / $totalKamarKos) * 100) : 0;
    $kosPembayarans = $pembayarans->filter(fn($p) => ($p->penghuniKamar->kamar->kos_id ?? 0) === $kos->id);
    $pendapatanKos = $kosPembayarans->sum('jumlah');
    $trxCount = $kosPembayarans->count();
    $searchKey = addslashes(trim(preg_replace('/\s+/', ' ', strtolower($kos->nama . ' ' . ($kos->alamat ?? '') . ' ' . ($kos->mitra->nama ?? '')))));
    @endphp
    <div x-show="!searchKos || '{{ $searchKey }}'.includes(searchKos.toLowerCase().trim())"
        x-data="{ expanded: false }"
        x-transition
        class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-4 sm:p-5 shadow-sm space-y-4 hover:border-emerald-200 dark:hover:border-emerald-800/50 transition-all">

        {{-- Header Kos --}}
        <div class="flex items-start gap-3 min-w-0">
            <div class="w-11 h-11 rounded-2xl bg-emerald-100 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-400 flex items-center justify-center flex-shrink-0 shadow-2xs mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <h4 class="font-bold text-sm sm:text-base text-gray-900 dark:text-white truncate">{{ $kos->nama }}</h4>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold font-mono {{ $pctOkupansi >= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : ($pctOkupansi > 0 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400') }}">
                        {{ $pctOkupansi }}% Terisi
                    </span>
                </div>
                <p class="text-xs text-gray-400 truncate mt-0.5 flex items-center gap-1">
                    <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="truncate">{{ $kos->alamat ?: 'Alamat belum diatur' }}</span>
                </p>
                @if($kos->mitra)
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Pemilik: <strong class="text-gray-700 dark:text-gray-200">{{ $kos->mitra->nama }}</strong></span>
                </p>
                @endif
            </div>
        </div>

        {{-- Pendapatan Box --}}
        <div class="bg-gradient-to-r from-emerald-50/90 to-teal-50/50 dark:from-emerald-950/40 dark:to-teal-950/20 p-3 sm:p-3.5 rounded-2xl border border-emerald-100 dark:border-emerald-900/40 flex items-center justify-between gap-3">
            <div class="space-y-0.5 min-w-0">
                <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-[10px] font-bold uppercase tracking-wider">Pendapatan Masuk</span>
                </div>
                <p class="font-mono font-black text-base sm:text-lg text-emerald-600 dark:text-emerald-400 truncate">
                    Rp {{ number_format($pendapatanKos, 0, ',', '.') }}
                </p>
            </div>

            <div class="text-right flex-shrink-0">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white dark:bg-gray-900 text-[11px] font-mono font-bold text-emerald-700 dark:text-emerald-300 border border-emerald-200/70 dark:border-emerald-800/70 shadow-2xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>{{ $trxCount }} Transaksi Sukses</span>
                </span>
            </div>
        </div>

        {{-- Okupansi Progress Bar --}}
        <div class="space-y-1.5 pt-1">
            <div class="flex justify-between items-center text-xs">
                <span class="text-gray-500 dark:text-gray-400 font-medium">Tingkat Okupansi:</span>
                <span class="font-mono font-bold text-gray-800 dark:text-gray-200">{{ $terisiKos }} / {{ $totalKamarKos }} Kamar ({{ $pctOkupansi }}%)</span>
            </div>
            <div class="w-full h-2.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden flex">
                @if($totalKamarKos > 0)
                <div class="bg-emerald-500 h-full transition-all duration-500" :style="{ width: '{{ $pctOkupansi }}%' }"></div>
                <div class="bg-amber-400 h-full transition-all duration-500" :style="{ width: '{{ 100 - $pctOkupansi }}%' }"></div>
                @else
                <div class="bg-gray-300 dark:bg-gray-700 h-full w-full"></div>
                @endif
            </div>
        </div>

        {{-- Metrik 3 Kolom --}}
        <div class="grid grid-cols-3 gap-2 text-center text-xs">
            <div class="p-2.5 bg-gray-50/80 dark:bg-gray-800/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                <span class="text-[10px] uppercase font-bold text-gray-400 block">Total Kamar</span>
                <span class="font-mono font-bold text-sm sm:text-base text-gray-900 dark:text-white block mt-0.5">{{ $totalKamarKos }}</span>
            </div>
            <div class="p-2.5 bg-emerald-50/70 dark:bg-emerald-950/30 rounded-2xl border border-emerald-100 dark:border-emerald-900/40">
                <span class="text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400 block">Kamar Terisi</span>
                <span class="font-mono font-bold text-sm sm:text-base text-emerald-600 dark:text-emerald-400 block mt-0.5">{{ $terisiKos }}</span>
            </div>
            <div class="p-2.5 bg-amber-50/70 dark:bg-amber-950/30 rounded-2xl border border-amber-100 dark:border-amber-900/40">
                <span class="text-[10px] uppercase font-bold text-amber-600 dark:text-amber-400 block">Kamar Kosong</span>
                <span class="font-mono font-bold text-sm sm:text-base text-amber-600 dark:text-amber-400 block mt-0.5">{{ $kosongKos }}</span>
            </div>
        </div>

        {{-- Expandable Rincian Kamar --}}
        @if($totalKamarKos > 0)
        <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
            <button type="button" @click="expanded = !expanded" class="w-full flex items-center justify-between py-1 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors font-medium">
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <span>Daftar Rincian Kamar ({{ $totalKamarKos }})</span>
                </span>
                <svg class="w-4 h-4 transform transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="expanded" x-transition class="pt-3 space-y-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($kos->kamar as $kmr)
                    @php
                    $isTerisi = $kmr->status === 'terisi';
                    $aktifPenghuni = $kmr->penghuniKamar ? $kmr->penghuniKamar->where('status_sewa', 'aktif')->first() : null;
                    $penghuniLabel = $aktifPenghuni && $aktifPenghuni->penghuni ? $aktifPenghuni->penghuni->nama : null;
                    @endphp
                    <div class="p-2.5 rounded-xl border {{ $isTerisi ? 'bg-emerald-50/40 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-900/30' : 'bg-gray-50/60 dark:bg-gray-800/30 border-gray-100 dark:border-gray-800' }} flex items-center justify-between text-xs">
                        <div class="min-w-0 pr-2">
                            <span class="font-bold text-gray-900 dark:text-white font-mono">{{ $kmr->kode_kamar }}</span>
                            @if($penghuniLabel)
                            <p class="text-[11px] text-gray-600 dark:text-gray-300 truncate mt-0.5">👤 {{ $penghuniLabel }}</p>
                            @else
                            <p class="text-[10px] text-gray-400 italic mt-0.5">Kamar kosong</p>
                            @endif
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="px-2 py-0.5 text-[9px] font-bold rounded-md font-mono {{ $isTerisi ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' }}">
                                {{ $isTerisi ? 'Terisi' : 'Kosong' }}
                            </span>
                            <span class="block text-[10px] font-mono text-gray-400 mt-0.5">Rp {{ number_format($kmr->harga ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
    @empty
    <x-empty-state message="Belum ada data kos terdaftar." />
    @endforelse
</div>

{{-- Tab 4: Log Aktivitas Sistem --}}
<div x-show="tab === 'log_aktivitas'" class="space-y-3" x-transition x-cloak>
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="grid grid-cols-1 gap-3 pb-2 border-b border-gray-100 dark:border-gray-800">
            <div>
                <h3 class="font-bold text-sm text-gray-900 dark:text-white">Log Aktivitas Pengguna & Sistem</h3>
                <p class="text-[10px] text-gray-400">Catatan riwayat semua tindakan pengguna, konfirmasi pembayaran, pendaftaran, login, dll.</p>
            </div>
            <span class="px-2.5 py-1 text-[10px] text-center w-[125px] font-bold font-mono rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                {{ count($logs ?? []) }} Log Terbaru
            </span>
        </div>
        <x-log-activity-list :logs="$logs" />
    </div>
</div>
</div>
@endsection