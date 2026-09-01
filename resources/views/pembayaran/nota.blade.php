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
<div class="max-w-2xl mx-auto space-y-4">
    {{-- Header Standard dengan Tombol Back Komponen & Action Cetak --}}
    <div class="print:hidden">
        <x-page-header title="Bukti Nota Pembayaran" subtitle="#{{ $invoiceNumber }}" backUrl="{{ $backUrl }}">
            @slot('action')
            <button onclick="window.print()" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-1.5 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak PDF</span>
            </button>
            @endslot
        </x-page-header>
    </div>

    {{-- Nota Paper Content --}}
    <div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white p-6 sm:p-8 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-xl space-y-6 relative overflow-hidden print:shadow-none print:border-none print:p-0">
        {{-- Stamp Watermark --}}
        <div class="absolute right-6 top-6 opacity-15 pointer-events-none transform rotate-12 border-4 border-emerald-600 p-3 rounded-2xl text-center select-none">
            <div class="text-xl font-black uppercase text-emerald-600 tracking-widest">LUNAS</div>
            <div class="text-[10px] font-mono text-emerald-700">TERVERIFIKASI</div>
        </div>

        {{-- Header Nota --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-6 border-b border-gray-200 dark:border-gray-800 gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <img src="{{ $appLogo }}" alt="{{ $appName }} Logo" class="w-8 h-8 object-contain">
                    <span class="font-['Cassandra'] font-bold text-xl tracking-tight text-emerald-600 dark:text-emerald-400">{{ $appName }}</span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Aplikasi Pengelolaan & Pendaftaran Kos Digital</p>
            </div>

            <div class="sm:text-right space-y-0.5">
                <div class="text-xs font-bold uppercase tracking-wider text-gray-400">BUKTI NOTA PEMBAYARAN</div>
                <div class="text-base font-mono font-bold text-emerald-600 dark:text-emerald-400">#{{ $invoiceNumber }}</div>
                <div class="text-[11px] text-gray-500 font-mono">Tgl Verifikasi: {{ $pembayaran->tanggal_verifikasi ? $pembayaran->tanggal_verifikasi->format('d/m/Y H:i') : date('d/m/Y H:i') }}</div>
            </div>
        </div>

        {{-- Grid Details (Penerima & Kos) --}}
        <div class="grid grid-cols-1 gap-6 p-4 bg-gray-50 dark:bg-gray-800/60 rounded-2xl border border-gray-100 dark:border-gray-800 text-xs">
            {{-- Data Penghuni --}}
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Ditujukan Kepada (Penghuni)</span>
                <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $pembayaran->penghuniKamar->penghuni->nama ?? '-' }}</p>
                <p class="text-gray-500 dark:text-gray-400 font-mono">📞 {{ $pembayaran->penghuniKamar->penghuni->no_hp ?? '-' }}</p>
                <p class="text-gray-500 dark:text-gray-400">✉️ {{ $pembayaran->penghuniKamar->penghuni->email ?? '-' }}</p>
            </div>

            {{-- Data Kos & Mitra --}}
            <div class="space-y-1">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Lokasi Kos & Pengelola</span>
                <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $pembayaran->penghuniKamar->kamar->kos->nama ?? '-' }} (Kamar {{ $pembayaran->penghuniKamar->kamar->kode_kamar ?? '-' }})</p>
                <p class="text-gray-500 dark:text-gray-400">{{ $pembayaran->penghuniKamar->kamar->kos->alamat ?? 'Alamat Kos' }}</p>
                <p class="text-gray-500 dark:text-gray-400">Mitra: <strong class="text-gray-700 dark:text-gray-300">{{ $pembayaran->penghuniKamar->kamar->kos->mitra->nama ?? '-' }}</strong> (📞 {{ $pembayaran->penghuniKamar->kamar->kos->mitra->no_hp ?? '-' }})</p>
            </div>
        </div>

        {{-- Tabel Items Rincian Pembayaran --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800 text-gray-400 uppercase tracking-wider text-[10px]">
                        <th class="py-2.5 px-3">Keterangan Item Pembayaran</th>
                        <th class="py-2.5 px-3 text-center">Durasi / Tipe</th>
                        <th class="py-2.5 px-3 text-center">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 font-medium text-[11px]">
                    <tr>
                        <td class="py-2 text-left">
                            <div class="font-bold text-gray-900 dark:text-white">Pembayaran Sewa Kamar {{ $pembayaran->penghuniKamar->kamar->kode_kamar ?? '-' }}</div>
                            <div class="text-[11px] text-gray-500 mt-0.5">
                                {{ $pembayaran->penghuniKamar->kamar->kos->nama ?? '-' }} · Periode:
                                {{ $pembayaran->periode_mulai ? date('d/m/Y', strtotime($pembayaran->periode_mulai)) : '-' }}
                                s/d
                                {{ $pembayaran->periode_selesai ? date('d/m/Y', strtotime($pembayaran->periode_selesai)) : '-' }}
                            </div>
                        </td>
                        <td class="py-2 text-center capitalize font-semibold">
                            {{ $pembayaran->tipe_perpanjangan ?? 'Bulanan' }}
                        </td>
                        <td class="py-2 text-left font-mono font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($pembayaran->jumlah, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Total & Signature --}}
        <div class="grid grid-cols-1 sm:grid-cols-6 pt-4 border-t border-gray-200 dark:border-gray-800 gap-4">
            <div class="col-span-6 space-y-1 text-xs text-gray-500 dark:text-gray-400">
                <p>Status Verifikasi: <span class="font-bold text-emerald-600 dark:text-emerald-400">LUNAS / TERVERIFIKASI</span></p>
                <p>Waktu Verifikasi: <strong class="text-gray-800 dark:text-gray-200">{{ $pembayaran->tanggal_verifikasi ? $pembayaran->tanggal_verifikasi->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-' }}</strong></p>
                @if(in_array($userRole, ['admin', 'super_admin', 'superadmin']))
                <p>Petugas Verifikasi: <strong class="text-gray-800 dark:text-gray-200">{{ $pembayaran->diverifikasiOleh->nama ?? 'Admin ' . $appName }}</strong></p>
                @endif
            </div>

            <div class="grid col-span-6 grid-cols-1 sm:grid-cols-2 p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl items-center min-w-[200px] space-y-0.5">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 block">Total Diterima</span>
                <div class="text-xl font-mono font-black text-emerald-700 dark:text-emerald-300">
                    Rp {{ number_format($pembayaran->jumlah, 0, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- Footer Note --}}
        <div class="pt-6 border-t border-gray-100 dark:border-gray-800/60 text-center text-[10px] text-gray-400 italic">
            Nota ini diterbitkan secara otomatis oleh sistem {{ $appName }} App dan berlaku sebagai bukti pembayaran yang sah.
        </div>
    </div>
</div>

<style>
    @media print {
        body {
            background: white !important;
            color: black !important;
        }

        nav,
        header,
        sidebar,
        .print\:hidden {
            display: none !important;
        }

        .shadow-xl,
        .shadow-sm {
            box-shadow: none !important;
        }

        .border {
            border-color: #e5e7eb !important;
        }
    }
</style>
@endsection