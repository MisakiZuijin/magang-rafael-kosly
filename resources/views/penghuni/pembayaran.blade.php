@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
<h1 class="text-xl font-bold mb-5 dark:text-white">Pembayaran</h1>

@if($rekening)
{{-- Info Rekening --}}
<x-card class="mb-4 bg-gradient-to-br from-emerald-500 to-emerald-600 border-0 text-white">
    <p class="text-[11px] font-bold text-emerald-100 uppercase tracking-wider mb-3">Rekening Pembayaran</p>
    <div class="flex items-center justify-between mb-3">
        <div>
            <p class="text-lg font-bold">{{ $rekening->bank }}</p>
            <p class="text-sm font-mono text-emerald-100 tracking-widest">{{ $rekening->no_rekening }}</p>
        </div>
        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
        </div>
    </div>
    <p class="text-xs text-emerald-100">A/n {{ $rekening->nama_pemilik_rekening }}</p>
</x-card>
@endif

{{-- Yang Harus Dibayar (Pending / Belum Upload) --}}
<h2 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Menunggu Pembayaran</h2>

@php
$menunggu = $pembayarans->where('status', 'pending');
@endphp

@if($menunggu->isEmpty())
<x-card class="mb-5">
    <div class="text-center py-6">
        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Tidak ada tagihan saat ini</p>
        <p class="text-xs text-gray-400 mt-1">Semua pembayaran sudah lunas</p>
    </div>
</x-card>
@else
<div class="space-y-3 mb-5">
    @foreach($menunggu as $p)
    <x-card class="border-l-4 border-l-amber-400">
        <div class="flex justify-between items-start mb-3">
            <div>
                <p class="text-sm font-bold dark:text-white">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Periode {{ $p->periode_mulai->format('d M Y') }} - {{ $p->periode_selesai->format('d M Y') }}</p>
            </div>
            <x-badge type="warning">Menunggu</x-badge>
        </div>

        @if(!$p->bukti_transfer_url)
        <form action="{{ route('penghuni.pembayaran.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <input type="hidden" name="pembayaran_id" value="{{ $p->id }}">
            <label class="block w-full">
                <span class="sr-only">Pilih bukti transfer</span>
                <input type="file" name="bukti_transfer" accept="image/*" required
                    class="block w-full text-xs text-gray-500 dark:text-gray-400 
                   file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 
                   file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 
                   dark:file:bg-emerald-900/30 dark:file:text-emerald-300
                   hover:file:bg-emerald-100 cursor-pointer">
            </label>
            <x-btn type="submit" size="sm" class="w-full">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Upload Bukti Transfer
            </x-btn>
        </form>
        @else
        <div class="space-y-3">
            {{-- Status --}}
            <div class="flex items-center gap-2 p-2.5 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs text-amber-700 dark:text-amber-300 font-medium">Bukti sudah diupload, menunggu verifikasi admin</p>
            </div>

            {{-- LINK LIHAT BUKTI --}}
            <a href="{{ asset('storage/' . $p->bukti_transfer_url) }}" target="_blank"
                class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700 active:bg-gray-100 dark:active:bg-gray-700 transition-colors">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Lihat Bukti Transfer</span>
            </a>
        </div>
        @endif
    </x-card>
    @endforeach
</div>
@endif

{{-- Riwayat Pembayaran --}}
<h2 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Riwayat Pembayaran</h2>

@php
$riwayat = $pembayarans->whereIn('status', ['terverifikasi', 'ditolak']);
@endphp

@if($riwayat->isEmpty())
<x-card>
    <x-empty-state message="Belum ada riwayat pembayaran." />
</x-card>
@else
<div class="space-y-3">
    @foreach($riwayat as $p)
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0
                        {{ $p->status === 'terverifikasi' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600' : 'bg-red-100 dark:bg-red-900/30 text-red-600' }}">
                @if($p->status === 'terverifikasi')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                @endif
            </div>
            <div>
                <p class="text-sm font-bold dark:text-white">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $p->periode_mulai->format('d M Y') }} - {{ $p->periode_selesai->format('d M Y') }}</p>
                @if($p->status === 'ditolak' && $p->catatan_verifikasi)
                <p class="text-xs text-red-500 mt-1">Catatan: {{ $p->catatan_verifikasi }}</p>
                @endif
            </div>
        </div>
        <x-badge type="{{ $p->status === 'terverifikasi' ? 'success' : 'danger' }}">
            {{ $p->status === 'terverifikasi' ? 'Lunas' : 'Ditolak' }}
        </x-badge>
    </div>
    @endforeach
</div>
@endif
@endsection