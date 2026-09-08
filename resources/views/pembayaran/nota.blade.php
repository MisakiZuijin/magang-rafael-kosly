@extends('layouts.app')

@php
$userRole = Auth::user()->role ?? '';
if ($userRole === 'penghuni') {
$backUrl = route('penghuni.pembayaran');
} elseif ($userRole === 'super_admin' || $userRole === 'superadmin') {
$backUrl = route('superadmin.pembayaran.index');
} elseif ($userRole === 'admin') {
$backUrl = route('admin.pembayaran.index');
} elseif ($userRole === 'mitra') {
$backUrl = route('mitra.dashboard');
} else {
$backUrl = route('dashboard');
}

$invoiceNumber = $pembayaran->kode_invoice ?? ('INV-' . date('Ymd', strtotime($pembayaran->created_at)) . '-' . sprintf('%04d', $pembayaran->id));
@endphp

@section('title', 'Nota Pembayaran #' . $invoiceNumber)

@section('content')
<div class="space-y-4 print:space-y-0">
    {{-- Header Standard dengan Tombol Back Komponen & Action Cetak --}}
    <div class="print:hidden">
        <x-page-header title="Bukti Nota Pembayaran" subtitle="#{{ $invoiceNumber }}" backUrl="{{ $backUrl }}">
            @slot('action')
            <button onclick="window.print()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-1.5 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak PDF</span>
            </button>
            @endslot
        </x-page-header>
    </div>

    {{-- HALAMAN 1: Lembar Rincian Nota Resmi (Mobile-First di Web, Desktop A4 di Print) --}}
    <div class="print-page-1 bg-white dark:bg-gray-900 text-gray-900 dark:text-white p-4 sm:p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm space-y-4 relative overflow-hidden print:overflow-visible print:shadow-none print:border-none print:rounded-none print:space-y-4 print:bg-white print:text-black">
        {{-- Stamp Watermark --}}
        <div class="absolute right-3 top-3 sm:right-6 sm:top-6 opacity-10 sm:opacity-15 pointer-events-none transform rotate-12 border-2 sm:border-4 border-emerald-600 p-1.5 sm:p-3 rounded-xl text-center select-none print:opacity-20">
            <div class="text-xs sm:text-xl font-black uppercase text-emerald-600 tracking-widest">LUNAS</div>
            <div class="text-[7px] sm:text-[10px] font-mono text-emerald-700 font-bold">TERVERIFIKASI</div>
        </div>

        {{-- Header Nota --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-3 sm:pb-4 border-b border-gray-100 dark:border-gray-800 print:border-gray-300 gap-2 sm:gap-4">
            <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                    <img src="{{ $appLogo }}" alt="{{ $appName }} Logo" class="w-7 h-7 object-contain">
                    <span class="font-['Cassandra'] font-bold text-lg tracking-tight text-emerald-600 dark:text-emerald-400">{{ $appName }}</span>
                </div>
                <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 print:text-gray-600">Aplikasi Pengelolaan & Pendaftaran Kos Digital</p>
            </div>

            <div class="sm:text-right space-y-0.5 pt-1 sm:pt-0">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-gray-400 print:text-gray-600">BUKTI NOTA PEMBAYARAN</div>
                <div class="text-sm sm:text-base font-mono font-bold text-emerald-600 dark:text-emerald-400">#{{ $invoiceNumber }}</div>
                <div class="text-[10px] text-gray-400 font-mono print:text-gray-600">Tgl Verifikasi: {{ $pembayaran->tanggal_verifikasi ? $pembayaran->tanggal_verifikasi->format('d/m/Y H:i') : date('d/m/Y H:i') }}</div>
            </div>
        </div>

        {{-- Grid Details (Penerima & Kos: Atas-Bawah di Layar HP, Kiri-Kanan saat Cetak PDF) --}}
        <div class="grid grid-cols-1 print:grid-cols-2 gap-3.5 print:gap-4 p-3.5 sm:p-4 bg-gray-50/80 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800 print:bg-gray-50 print:border-gray-200 text-xs">
            {{-- Data Penghuni (Atas di Web, Kiri di PDF) --}}
            <div class="space-y-1 pb-3 print:pb-0 border-b print:border-b-0 print:border-r border-gray-200/60 dark:border-gray-700/60 print:border-gray-300 print:pr-4">
                <span class="text-[9px] font-bold text-gray-400 print:text-gray-600 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-400 print:text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Ditujukan Kepada (Penghuni)</span>
                </span>
                <p class="font-bold text-sm text-gray-900 dark:text-white print:text-black">{{ $pembayaran->penghuniKamar->penghuni->nama ?? '-' }}</p>
                <div class="space-y-0.5 text-xs text-gray-500 dark:text-gray-400 print:text-gray-700 font-mono">
                    <p class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400 print:text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <span>{{ $pembayaran->penghuniKamar->penghuni->no_hp ?? '-' }}</span>
                    </p>
                    <p class="truncate font-sans text-[11px] flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400 print:text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>{{ $pembayaran->penghuniKamar->penghuni->email ?? '-' }}</span>
                    </p>
                </div>
            </div>

            {{-- Data Lokasi Hunian (Bawah di Web, Kanan di PDF) --}}
            <div class="space-y-1 print:pl-2">
                <span class="text-[9px] font-bold text-gray-400 print:text-gray-600 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-400 print:text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span>{{ $userRole === 'mitra' ? 'Lokasi Kos' : 'Lokasi Hunian' }}</span>
                </span>
                @if($userRole === 'mitra')
                <p class="font-bold text-sm text-gray-900 dark:text-white print:text-black">{{ $pembayaran->penghuniKamar->kamar->kos->nama ?? '-' }} <span class="text-emerald-600 dark:text-emerald-400 font-mono text-xs">(Kamar {{ $pembayaran->penghuniKamar->kamar->kode_kamar ?? '-' }})</span></p>
                @else
                <p class="font-bold text-sm text-gray-900 dark:text-white print:text-black">Kamar {{ $pembayaran->penghuniKamar->kamar->kode_kamar ?? '-' }}</p>
                @endif
                <div class="space-y-0.5 text-xs text-gray-500 dark:text-gray-400 print:text-gray-700">
                    <p class="text-[11px] leading-relaxed flex items-start gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400 print:text-gray-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>{{ $pembayaran->penghuniKamar->kamar->kos->alamat ?? 'Alamat Kos' }}</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Tabel Items Rincian Pembayaran --}}
        <div class="overflow-x-auto -mx-1 sm:mx-0">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800 print:border-gray-300 text-gray-400 print:text-gray-600 uppercase tracking-wider text-[9px] sm:text-[10px]">
                        <th class="py-2 px-2 text-left">Item Pembayaran</th>
                        <th class="py-2 px-2 text-center">Durasi</th>
                        <th class="py-2 px-2 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 print:divide-gray-200 font-medium text-[11px]">
                    <tr>
                        <td class="py-2.5 px-2 text-left">
                            <div class="font-bold text-gray-900 dark:text-white print:text-black text-xs">Sewa Kamar {{ $pembayaran->penghuniKamar->kamar->kode_kamar ?? '-' }}</div>
                            <div class="text-[10px] text-gray-500 print:text-gray-600 mt-0.5 font-mono">
                                {{ $pembayaran->periode_mulai ? date('d/m/Y', strtotime($pembayaran->periode_mulai)) : '-' }} s/d {{ $pembayaran->periode_selesai ? date('d/m/Y', strtotime($pembayaran->periode_selesai)) : '-' }}
                            </div>
                        </td>
                        <td class="py-2.5 px-2 text-center capitalize font-semibold text-[10px]">
                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded-md print:bg-transparent">{{ $pembayaran->tipe_perpanjangan ?? 'Bulanan' }}</span>
                        </td>
                        <td class="py-2.5 px-2 text-right font-mono font-bold text-gray-900 dark:text-white print:text-black text-xs sm:text-sm">
                            Rp {{ number_format($pembayaran->jumlah, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Total & Signature --}}
        <div class="pt-3 border-t border-gray-100 dark:border-gray-800 print:border-gray-300 space-y-3">
            {{-- Kotak Total Diterima --}}
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-xl flex items-center justify-between gap-2 print:bg-emerald-50 print:border-emerald-300">
                <div>
                    <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 block">Total Diterima</span>
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                        <span>✓</span> LUNAS TERVERIFIKASI
                    </span>
                </div>
                <div class="text-lg sm:text-2xl font-mono font-black text-emerald-700 dark:text-emerald-300">
                    Rp {{ number_format($pembayaran->jumlah, 0, ',', '.') }}
                </div>
            </div>

            {{-- Metadata Verifikasi --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 print:text-gray-700 gap-1 px-1">
                <div>
                    Waktu Verifikasi: <strong class="text-gray-800 dark:text-gray-200 print:text-black font-mono">{{ $pembayaran->tanggal_verifikasi ? $pembayaran->tanggal_verifikasi->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-' }}</strong>
                </div>
                @if(in_array($userRole, ['admin', 'super_admin', 'superadmin']))
                <div>
                    Petugas: <strong class="text-gray-800 dark:text-gray-200 print:text-black">{{ $pembayaran->diverifikasiOleh->nama ?? 'Admin ' . $appName }}</strong>
                </div>
                @endif
            </div>
        </div>

        {{-- Footer Note Halaman 1 --}}
        <div class="pt-3 border-t border-gray-100 dark:border-gray-800/60 print:border-gray-300 text-center text-[9px] text-gray-400 print:text-gray-500 italic">
            Nota ini diterbitkan secara otomatis oleh sistem {{ $appName }} App dan berlaku sebagai bukti pembayaran yang sah.
        </div>
    </div>

    {{-- HALAMAN 2: Khusus Lampiran Bukti Transfer Penuh --}}
    @php
    $effectiveBukti = $pembayaran->effective_bukti_transfer_url ?? $pembayaran->bukti_transfer_url;
    $isDiwakilkan = $pembayaran->catatan_verifikasi && str_contains($pembayaran->catatan_verifikasi, 'Lunas (Dibayar');
    $uploaderName = $isDiwakilkan ? trim(preg_replace('/^Lunas \(Dibayar (?:Full|Tarif 2 Orang|Tarif 3 Orang|Tarif 1 Kamar) oleh (.+)\)$/', '$1', $pembayaran->catatan_verifikasi)) : null;
    @endphp

    @if($effectiveBukti)
    @php
    $buktiImg = str_starts_with($effectiveBukti, 'http')
    ? $effectiveBukti
    : asset('storage/' . $effectiveBukti);
    @endphp
    <div class="print-page-break mt-4 sm:mt-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white p-4 sm:p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm space-y-3 print:shadow-none print:border-none print:p-0 print:rounded-none print:space-y-4 print:bg-white print:text-black">
        {{-- Header Lampiran --}}
        <div class="flex items-center justify-between pb-2.5 border-b border-gray-100 dark:border-gray-800 print:border-gray-300">
            <div class="space-y-0.5">
                <div class="flex items-center gap-1.5">
                    <span class="font-['Cassandra'] font-bold text-base text-emerald-600 dark:text-emerald-400">{{ $appName }}</span>
                    <span class="text-[11px] font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">· Bukti Transfer</span>
                </div>
                <p class="text-[10px] text-gray-500 font-mono">
                    Invoice #{{ $invoiceNumber }}
                    @if($isDiwakilkan && $uploaderName)
                    <span class="text-blue-600 dark:text-blue-400 font-semibold">· Dibayar oleh {{ $uploaderName }}</span>
                    @endif
                </p>
            </div>
            <div class="text-right">
                <a href="{{ $buktiImg }}" target="_blank" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline print:hidden flex items-center gap-1">
                    <span>Buka Foto</span>
                </a>
            </div>
        </div>

        @if($isDiwakilkan && $uploaderName)
        <div class="p-2.5 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/50 rounded-xl flex items-center gap-2 text-[11px] text-blue-800 dark:text-blue-300 print:bg-blue-50 print:border-blue-200">
            <svg class="w-4 h-4 text-blue-700 dark:text-blue-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span>Pembayaran kamar diwakilkan oleh <strong>{{ $uploaderName }}</strong> (Bukti transfer sekamar).</span>
        </div>
        @endif

        {{-- Frame Gambar Bukti Transfer --}}
        <div class="p-2.5 sm:p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700/80 space-y-2.5 print:bg-white print:border-none print:p-0">
            <div class="w-full flex items-center justify-center p-1.5 rounded-lg border border-gray-200 dark:border-gray-700 print:border-gray-300 bg-white dark:bg-gray-900 shadow-xs print:shadow-none min-h-[180px] max-h-[360px] print:max-h-[850px] print:min-h-[750px]">
                <img src="{{ $buktiImg }}" alt="Bukti Transfer {{ $pembayaran->penghuniKamar->penghuni->nama ?? 'Penghuni' }}" class="w-full max-h-[340px] print:max-h-[820px] object-contain rounded-md cursor-pointer print-img-full" onclick="window.open('{{ $buktiImg }}', '_blank')" title="Klik untuk membuka ukuran penuh">
            </div>

            <div class="grid grid-cols-1 gap-1.5 pt-1.5 text-[11px] text-gray-600 dark:text-gray-300 border-t border-gray-200/60 dark:border-gray-700/60 print:border-gray-200">
                <div class="flex items-center gap-1 font-bold text-emerald-700 dark:text-emerald-400">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Bukti Transfer Sah Terverifikasi</span>
                </div>
                <div class="text-[10px] text-gray-500 font-mono">
                    Tanggal Bayar: <strong class="text-gray-800 dark:text-gray-200 print:text-black">{{ $pembayaran->tanggal_bayar ? \Carbon\Carbon::parse($pembayaran->tanggal_bayar)->format('d M Y') : date('d M Y', strtotime($pembayaran->created_at)) }}</strong>
                </div>
            </div>

            @if($pembayaran->catatan_verifikasi)
            @php
            $cleanCatatan = trim(str_ireplace([' oleh Admin', ' oleh admin'], '', $pembayaran->catatan_verifikasi));
            @endphp
            <div class="p-2 bg-white/80 dark:bg-gray-900/60 rounded-lg border border-gray-200/60 dark:border-gray-700/40 print:border-gray-200 text-[10px] text-gray-600 dark:text-gray-300">
                <span class="font-bold text-gray-700 dark:text-gray-200 print:text-black">Catatan:</span>
                <span class="italic ml-1">"{{ $cleanCatatan }}"</span>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

<style>
    @media print {
        @page {
            size: A4 portrait;
            margin: 0;
        }

        html,
        body {
            background: #ffffff !important;
            color: #000000 !important;
            height: auto !important;
            min-height: auto !important;
            overflow: visible !important;
            font-size: 11pt !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Override wrapper aplikasi mobile agar saat cetak PDF menjadi format desktop A4 penuh */
        #app-container {
            max-width: 100% !important;
            width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            overflow: visible !important;
            display: block !important;
            position: static !important;
            box-shadow: none !important;
            background: transparent !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        main {
            margin: 0 !important;
            padding: 0 !important;
            height: auto !important;
            overflow: visible !important;
            display: block !important;
            position: static !important;
        }

        nav,
        header,
        sidebar,
        #navbar,
        #sidebar,
        .print\:hidden {
            display: none !important;
        }

        .shadow-xl,
        .shadow-sm,
        .shadow-2xl,
        .shadow-xs {
            box-shadow: none !important;
        }

        .border {
            border-color: #e5e7eb !important;
        }

        /* Halaman 1 Spacing saat Cetak PDF */
        .print-page-1 {
            padding: 12mm 15mm !important;
            margin: 0 !important;
        }

        /* Pemisah & Jarak Halaman 2 untuk Cetak PDF */
        .print-page-break {
            page-break-before: always !important;
            break-before: page !important;
            clear: both !important;
            display: block !important;
            margin: 0 !important;
            padding: 15mm 15mm 12mm 15mm !important;
        }

        .print-img-full {
            max-height: 750px !important;
            width: 100% !important;
            object-fit: contain !important;
        }
    }
</style>
@endsection