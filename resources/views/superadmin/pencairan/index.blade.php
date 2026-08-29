@extends('layouts.app')

@section('title', 'Pencairan Biaya Per Kos - Super Admin')

@section('content')
@php
$namaBulan = [
1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
@endphp

<div class="space-y-6" x-data="{ 
    filterStatus: 'all',
    searchQuery: '',
    modalProses: false,
    prosesData: { kos_id: '', nama_kos: '', nama_mitra: '', bank_info: '', nominal: 0, nominal_format: '' },
    modalDetail: false,
    detailData: { nama_kos: '', nama_mitra: '', nominal_format: '', tanggal_cair: '', bukti_url: '', catatan: '' },
    openProsesModal(kosId, namaKos, namaMitra, bankInfo, nominal) {
        this.prosesData = {
            kos_id: kosId,
            nama_kos: namaKos,
            nama_mitra: namaMitra,
            bank_info: bankInfo,
            nominal: nominal,
            nominal_format: 'Rp ' + new Intl.NumberFormat('id-ID').format(nominal)
        };
        this.modalProses = true;
    },
    openDetailModal(namaKos, namaMitra, nominal, tglCair, buktiUrl, catatan) {
        this.detailData = {
            nama_kos: namaKos,
            nama_mitra: namaMitra,
            nominal_format: 'Rp ' + new Intl.NumberFormat('id-ID').format(nominal),
            tanggal_cair: tglCair,
            bukti_url: buktiUrl,
            catatan: catatan
        };
        this.modalDetail = true;
    },
    matchSearch(namaKos, namaMitra, status) {
        const matchesStatus = this.filterStatus === 'all' || this.filterStatus === status;
        const q = this.searchQuery.toLowerCase().trim();
        const matchesQuery = !q || namaKos.toLowerCase().includes(q) || namaMitra.toLowerCase().includes(q);
        return matchesStatus && matchesQuery;
    }
}">

    {{-- Page Header --}}
    <x-page-header title="Pencairan Biaya Per Kos" subtitle="Khusus Super Admin - Pemrosesan Transfer Pendapatan Kos ke Mitra" backUrl="{{ route('dashboard') }}">

    </x-page-header>

    {{-- Controls: Filter Periode & Search & Status Tabs --}}
    <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-4">
        {{-- Section 1: Select Periode Bulan & Tahun --}}
        <form action="{{ route('superadmin.pencairan.index') }}" method="GET" class="grid grid-cols-1 gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Periode Tagihan Pencairan</h3>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Pilih bulan dan tahun periode pembayaran</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <select name="bulan" class="col-span-1 py-2 px-3.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    @foreach($namaBulan as $num => $name)
                    <option value="{{ $num }}" {{ $bulan == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>

                <select name="tahun" class="col-span-1 py-2 px-3.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold font-mono text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                </select>

                <x-btn type="submit" variant="primary" size="sm" class="col-span-2 !py-2 font-bold shadow-xs">
                    🔍 Filter Periode
                </x-btn>
            </div>
        </form>

        {{-- Section 2: Search Input & Quick Status Filter Tabs --}}
        <div class="grid grid-cols-1 gap-4">
            {{-- Search Bar Input --}}
            <div class="relative min-w-[260px] flex-1 max-w-md">
                <input type="text"
                    x-model="searchQuery"
                    placeholder="🔍 Cari nama kos atau nama pemilik mitra..."
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 shadow-xs">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            {{-- Status Tabs Filter Buttons --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                <button type="button" @click="filterStatus = 'all'" :class="filterStatus === 'all' ? 'bg-gray-600 text-white dark:bg-white dark:text-gray-900 shadow-xs' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'" class="px-3.5 py-2 rounded-xl transition-all">
                    Semua ({{ $pencairanData->count() }})
                </button>
                <button type="button" @click="filterStatus = 'pending'" :class="filterStatus === 'pending' ? 'bg-amber-500 text-white shadow-xs' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-800/50'" class="px-3.5 py-2 rounded-xl transition-all">
                    Belum Cair ({{ $pencairanData->where('status', 'pending')->count() }})
                </button>
                <button type="button" @click="filterStatus = 'dicairkan'" :class="filterStatus === 'dicairkan' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-800/50'" class="px-3.5 py-2 rounded-xl transition-all">
                    Sudah Cair ({{ $pencairanData->where('status', 'dicairkan')->count() }})
                </button>
            </div>
        </div>
    </div>

    {{-- Cards Stats Overview (4 Cards: Includes Ditunda Ke Bulan Depan) --}}
    <div class="grid grid-cols-1 gap-4">
        <x-stat-card label="Total Lolos Cutoff" value="Rp {{ number_format($totalPendapatanSemua, 0, ',', '.') }}" unit="Periode {{ $namaBulan[$bulan] }} {{ $tahun }}" color="emerald">
            <x-slot name="icon">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card label="Sudah Dicairkan" value="Rp {{ number_format($totalSudahDicairkan, 0, ',', '.') }}" unit="Telah Ditransfer Ke Pemilik" color="blue">
            <x-slot name="icon">
                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card label="Belum Dicairkan" value="Rp {{ number_format($totalBelumDicairkan, 0, ',', '.') }}" unit="Menunggu Pemrosesan" color="amber">
            <x-slot name="icon">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card label="Ditunda / Roll Over" value="Rp {{ number_format($totalPendapatanDitundaSemua, 0, ',', '.') }}" unit="Simpanan Bulan Depannya" color="purple">
            <x-slot name="icon">
                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </x-slot>
        </x-stat-card>
    </div>

    @php
    $nextMonthDate = \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->addMonth();
    $bulanDepanNum = $nextMonthDate->month;
    $tahunDepanNum = $nextMonthDate->year;
    $namaBulanDepan = $namaBulan[$bulanDepanNum] ?? '';
    @endphp

    {{-- Box Informasi Rule Cutoff & Periode Sewa --}}
    <div class="bg-emerald-50/80 dark:bg-emerald-950/30 p-4 rounded-2xl border border-emerald-200/80 dark:border-emerald-900/50 flex items-start gap-3.5">
        <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0 mt-0.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="text-xs text-emerald-950 dark:text-emerald-200 leading-relaxed min-w-0">
            <p class="font-bold text-sm text-emerald-900 dark:text-emerald-300 mb-0.5">
                📌 Kebijakan Alokasi Periode Pencairan:
            </p>
            <p>
                Pencairan dana ke Mitra dihitung berdasarkan <strong>Periode Mulai Sewa (Tanggal Masuk)</strong>. Jika penghuni membayar lebih awal di bulan ini untuk sewa bulan depan (misal: <strong>{{ $namaBulanDepan }} {{ $tahunDepanNum }}</strong>), dana secara otomatis dialokasikan ke pencairan periode <strong>{{ $namaBulanDepan }} {{ $tahunDepanNum }}</strong> dan tampil sebagai simpanan/ditunda di periode ini.
            </p>
        </div>
    </div>

    {{-- Daftar Kos (Spacious Item Cards) --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between px-1">
            <h2 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                Daftar Kos (Periode {{ $namaBulan[$bulan] }} {{ $tahun }})
            </h2>
            <span class="text-xs text-gray-500 font-normal" x-show="searchQuery" x-text="'Hasil pencarian: &quot;' + searchQuery + '&quot;'"></span>
        </div>

        @if($pencairanData->isEmpty())
        <x-empty-state message="Belum ada kos terdaftar dalam sistem." />
        @else
        <div class="grid grid-cols-1 gap-4">
            @foreach($pencairanData as $item)
            @php
            $kos = $item['kos'];
            $mitra = $item['mitra'];
            $status = $item['status'];
            $record = $item['record'];
            $bankInfoStr = ($kos->bank ?? 'Bank Belum Diisi') . ' - ' . ($kos->no_rekening ?? '-') . ' (a.n ' . ($kos->nama_pemilik_rekening ?? '-') . ')';
            $tglCairStr = $record && $record->tanggal_cair ? $record->tanggal_cair->format('d M Y H:i') : '-';
            $buktiUrlStr = $record && $record->bukti_transfer ? asset('storage/' . $record->bukti_transfer) : '';
            $catatanStr = $record && $record->catatan ? $record->catatan : '-';
            $nominalRp = 'Rp ' . number_format($item['total_nominal'], 0, ',', '.');
            $nominalDitundaRp = 'Rp ' . number_format($item['total_nominal_ditunda'], 0, ',', '.');
            @endphp

            <div x-show="matchSearch('{{ addslashes($kos->nama) }}', '{{ addslashes($mitra->nama ?? '') }}', '{{ $status }}')"
                class="bg-white dark:bg-gray-900 rounded-2xl border p-5 sm:p-6 shadow-sm hover:shadow-md transition-all space-y-4
                        {{ $status === 'dicairkan' ? 'border-emerald-200 dark:border-emerald-900/50 bg-gradient-to-br from-white via-emerald-50/15 to-white dark:from-gray-900 dark:via-emerald-950/10 dark:to-gray-900' : 'border-gray-200/80 dark:border-gray-800' }}">

                {{-- Header Kos & Info Owner --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-4 col-span-2 min-w-0 space-y-1">
                        <div class="grid grid-cols-1 sm:grid-cols-6 col-span-4 gap-2 flex-wrap">
                            <h3 class="col-span-4 font-bold text-lg text-gray-900 dark:text-white leading-snug">
                                🏢 {{ $kos->nama }}
                            </h3>

                            {{-- Status Badge --}}
                            <div class="col-span-2">
                                @if($status === 'dicairkan')
                                <span class="px-3.5 py-1.5 rounded-full text-xs bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 flex items-center gap-1.5 shadow-2xs">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Sudah Cair</span>
                                </span>
                                @else
                                <span class="px-3.5 py-1.5 rounded-full text-xs bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-300 dark:border-amber-800 flex items-center gap-1.5 shadow-2xs">
                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Belum Cair</span>
                                </span>
                                @endif
                            </div>
                        </div>

                        <span class="col-span-1 px-2.5 py-0.5 text-center text-xs font-bold uppercase rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                            {{ $kos->kamar->count() }} Kamar
                        </span>

                        @if($kos->tipe)
                        <span class="col-span-1 px-2.5 py-0.5 text-xs font-bold uppercase rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            {{ ucfirst($kos->tipe) }}
                        </span>
                        @endif

                        <p class="col-span-4 text-xs text-gray-600 dark:text-gray-400 flex items-center gap-2 flex-wrap pt-0.5">
                            <span>Mitra Pemilik: <strong class="text-gray-900 dark:text-white font-bold">{{ $mitra ? $mitra->nama : 'Mitra Tidak Ditemukan' }}</strong></span>
                            @if($mitra && $mitra->no_hp)
                            @php
                            $cleanPhone = preg_replace('/[^0-9]/', '', $mitra->no_hp);
                            if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '62' . substr($cleanPhone, 1);
                            }
                            @endphp
                            <a href="https://wa.me/{{ $cleanPhone }}?text=Halo%20{{ urlencode($mitra->nama) }},%20mengenai%20pencairan%20pendapatan%20Kos%20{{ urlencode($kos->nama) }}..."
                                target="_blank"
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-300 hover:underline bg-emerald-50 dark:bg-emerald-950/80 px-2.5 py-0.5 rounded-lg border border-emerald-200/60 dark:border-emerald-800/60 transition-all active:scale-95 shadow-2xs"
                                title="Hubungi Mitra via WhatsApp ({{ $mitra->no_hp }})">
                                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 fill-current" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                                </svg>
                                <span>WhatsApp</span>
                            </a>
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Rekening Transfer Mitra Box --}}
                <div class="p-4 rounded-xl bg-gray-50/90 dark:bg-gray-800/60 border border-gray-200/60 dark:border-gray-700/60 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400 tracking-wider">💳 Info Rekening Transfer Mitra:</span>
                        @if($kos->bank)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-mono">
                            {{ $kos->bank }}
                        </span>
                        @endif
                    </div>
                    @if($kos->bank && $kos->no_rekening)
                    <div class="flex items-center gap-2 pt-0.5 flex-wrap">
                        <p class="font-mono font-black text-gray-900 dark:text-white text-sm">
                            {{ $kos->no_rekening }}
                        </p>
                        <button type="button"
                            x-data="{ copied: false }"
                            @click="navigator.clipboard.writeText('{{ $kos->no_rekening }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-100 hover:bg-emerald-200 dark:hover:bg-emerald-800/50 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 transition-all border border-emerald-200 dark:border-emerald-800 active:scale-95"
                            title="Salin Nomor Rekening">
                            <svg x-show="!copied" class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <svg x-show="copied" class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <span x-text="copied ? 'Tersalin!' : 'Salin'">Salin</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                        Atas Nama: <strong class="text-gray-900 dark:text-white font-bold">{{ $kos->nama_pemilik_rekening ?? '-' }}</strong>
                    </p>
                    @else
                    <p class="text-amber-600 dark:text-amber-400 italic text-xs pt-1">⚠️ Rekening belum diisi oleh mitra/admin</p>
                    @endif
                </div>

                {{-- Detail Pendapatan Grid (Lolos Cutoff vs Ditunda/Roll Over Ke Bulan Depan) --}}
                <div class="grid grid-cols-1 md:grid-cols-1 gap-4 pt-1 pb-1">
                    {{-- Jumlah Harus Dicairkan Bulan Ini (Lolos Cutoff) --}}
                    <div class="p-4 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-900/50 space-y-1.5 text-xs">
                        <span class="text-[10px] uppercase font-bold text-emerald-800 dark:text-emerald-400 tracking-wider block">💰 DICAIRKAN PERIODE {{ strtoupper($namaBulan[$bulan]) }} {{ $tahun }}:</span>
                        <p class="font-mono font-black text-emerald-700 dark:text-emerald-300 text-lg leading-tight">
                            {{ $nominalRp }}
                        </p>
                        <p class="text-xs text-emerald-800 dark:text-emerald-400">
                            {{ $item['total_transaksi'] }} Transaksi Periode Sewa {{ $namaBulan[$bulan] }} {{ $tahun }}
                        </p>
                    </div>

                    {{-- Jumlah Ditunda / Roll Over Ke Bulan Depan --}}
                    <div class="p-4 rounded-xl bg-purple-50/80 dark:bg-purple-950/40 border border-purple-200/80 dark:border-purple-900/50 space-y-1.5 text-xs">
                        <span class="text-[10px] uppercase font-bold text-purple-800 dark:text-purple-400 tracking-wider block">⏳ DITUNDA KE PERIODE {{ strtoupper($namaBulanDepan) }} {{ $tahunDepanNum }}:</span>
                        <p class="font-mono font-black text-purple-700 dark:text-purple-300 text-lg leading-tight">
                            {{ $nominalDitundaRp }}
                        </p>
                        <p class="text-xs text-purple-800 dark:text-purple-400">
                            {{ $item['total_transaksi_ditunda'] }} Transaksi Periode Sewa Masa Depan ({{ $namaBulanDepan }} {{ $tahunDepanNum }}+)
                        </p>
                    </div>
                </div>

                {{-- Action Bar --}}
                <div class="pt-2 grid grid-cols-1 gap-3 border-t border-gray-100 dark:border-gray-800 flex-wrap">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        @if($status === 'dicairkan')
                        Status: <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">Sudah Ditransfer pada {{ $tglCairStr }}</span>
                        @else
                        Status: <span class="font-bold text-amber-600 dark:text-amber-400">Siap Diproses</span>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-2">
                        @if($status === 'dicairkan')
                        <x-btn type="button" variant="secondary" size="sm"
                            data-nama-kos="{{ $kos->nama }}"
                            data-nama-mitra="{{ $mitra->nama ?? '-' }}"
                            data-nominal="{{ $item['total_nominal'] }}"
                            data-tgl-cair="{{ $tglCairStr }}"
                            data-bukti-url="{{ $buktiUrlStr }}"
                            data-catatan="{{ $catatanStr }}"
                            @click="openDetailModal($el.dataset.namaKos, $el.dataset.namaMitra, parseInt($el.dataset.nominal), $el.dataset.tglCair, $el.dataset.buktiUrl, $el.dataset.catatan)"
                            class="!py-2 !px-3.5 text-xs font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>Lihat Bukti Transfer</span>
                        </x-btn>
                        @else
                        <x-btn type="button" variant="primary" size="sm"
                            data-kos-id="{{ $kos->id }}"
                            data-nama-kos="{{ $kos->nama }}"
                            data-nama-mitra="{{ $mitra->nama ?? '-' }}"
                            data-bank-info="{{ $bankInfoStr }}"
                            data-nominal="{{ $item['total_nominal'] }}"
                            @click="openProsesModal($el.dataset.kosId, $el.dataset.namaKos, $el.dataset.namaMitra, $el.dataset.bankInfo, parseInt($el.dataset.nominal))"
                            class="!py-2 !px-4 text-xs font-bold flex items-center gap-1.5 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Proses Pencairan ({{ $nominalRp }})</span>
                        </x-btn>
                        @endif
                    </div>
                </div>

            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Modal Form Proses Pencairan --}}
    <x-modal show="modalProses" title="Konfirmasi & Proses Pencairan Pendapatan">
        <form action="{{ route('superadmin.pencairan.proses') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="kos_id" :value="prosesData.kos_id">
            <input type="hidden" name="bulan" value="{{ $bulan }}">
            <input type="hidden" name="tahun" value="{{ $tahun }}">

            <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 rounded-2xl border border-emerald-200 dark:border-emerald-900/50 space-y-2">
                <p class="text-sm font-bold text-emerald-900 dark:text-emerald-200" x-text="prosesData.nama_kos"></p>
                <div class="grid grid-cols-1 gap-2 text-xs">
                    <span class="text-emerald-700 dark:text-emerald-400">Mitra Pemilik :</span>
                    <strong class="text-gray-900 dark:text-white font-bold" x-text="prosesData.nama_mitra"></strong>
                </div>
                <div class="grid grid-cols-1 gap-2 text-xs">
                    <span class="text-emerald-700 dark:text-emerald-400">Rekening Transfer :</span>
                    <strong class="font-mono text-gray-900 dark:text-white" x-text="prosesData.bank_info"></strong>
                </div>
                <div class="pt-2 flex justify-between items-center border-t border-emerald-200/60 dark:border-emerald-900/40">
                    <span class="text-xs font-bold text-emerald-900 dark:text-emerald-200 uppercase">Jumlah Dicairkan:</span>
                    <span class="text-lg font-black font-mono text-emerald-700 dark:text-emerald-300" x-text="prosesData.nominal_format"></span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                    📷 Unggah Resi / Foto Bukti Transfer Bank (Opsional)
                </label>
                <input type="file" name="bukti_transfer" accept="image/*" class="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                    📝 Catatan Pemrosesan (Opsional)
                </label>
                <textarea name="catatan" rows="3" placeholder="Masukkan nomor referensi transaksi atau catatan internal..." class="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white"></textarea>
            </div>

            <div class="pt-2 flex justify-end gap-2 border-t border-gray-100 dark:border-gray-800">
                <x-btn type="button" variant="secondary" size="sm" @click="modalProses = false">Batal</x-btn>
                <x-btn type="submit" variant="primary" size="sm" class="!py-2.5 !px-4 font-bold shadow-sm">
                    ✓ Konfirmasi Pencairan Selesai
                </x-btn>
            </div>
        </form>
    </x-modal>

    {{-- Modal Detail Bukti Transfer Pencairan --}}
    <x-modal show="modalDetail" title="Detail Bukti Transfer Pencairan">
        <div class="space-y-4">
            <div class="p-4 bg-gray-50 dark:bg-gray-800/80 rounded-2xl border border-gray-200 dark:border-gray-700 space-y-1.5 text-xs">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-gray-900 dark:text-white text-sm" x-text="detailData.nama_kos"></span>
                    <span class="font-mono font-black text-emerald-600 dark:text-emerald-400 text-sm" x-text="detailData.nominal_format"></span>
                </div>
                <div class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                    <span>Mitra Pemilik:</span>
                    <strong class="text-gray-900 dark:text-white" x-text="detailData.nama_mitra"></strong>
                </div>
                <div class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                    <span>Waktu Pencairan:</span>
                    <span class="font-mono text-gray-900 dark:text-white" x-text="detailData.tanggal_cair"></span>
                </div>
                <div class="pt-1.5 border-t border-gray-200/80 dark:border-gray-700/80" x-show="detailData.catatan !== '-'">
                    <span class="text-gray-500">Catatan:</span>
                    <p class="italic text-gray-800 dark:text-gray-200 mt-0.5" x-text="detailData.catatan"></p>
                </div>
            </div>

            <template x-if="detailData.bukti_url">
                <div class="text-center space-y-2">
                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300">📷 Pratinjau Resi / Bukti Transfer:</p>
                    <div class="relative w-full max-h-[60vh] overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800 bg-black flex items-center justify-center">
                        <img :src="detailData.bukti_url" alt="Bukti Transfer Pencairan" class="max-w-full max-h-[60vh] object-contain rounded-lg">
                    </div>
                </div>
            </template>
            <template x-if="!detailData.bukti_url">
                <p class="text-xs text-gray-400 italic text-center py-4">Tidak ada foto bukti transfer yang diunggah saat pemrosesan.</p>
            </template>

            <div class="pt-2 flex justify-end">
                <x-btn type="button" variant="secondary" size="sm" @click="modalDetail = false">Tutup</x-btn>
            </div>
        </div>
    </x-modal>

</div>
@endsection