@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1 class="text-xl font-bold mb-5">Dashboard</h1>

{{-- Stats --}}
<div class="grid grid-cols-2 gap-3 mb-5">
    <x-card class="text-center py-5">
        <p class="text-3xl font-bold text-emerald-600">{{ $data['total_kamar'] }}</p>
        <p class="text-xs text-gray-500 font-medium mt-1">Total Kamar</p>
    </x-card>
    <x-card class="text-center py-5">
        <p class="text-3xl font-bold text-blue-600">{{ $data['kamar_terisi'] }}</p>
        <p class="text-xs text-gray-500 font-medium mt-1">Terisi</p>
    </x-card>
    <x-card class="text-center py-5">
        <p class="text-3xl font-bold text-gray-500">{{ $data['kamar_kosong'] }}</p>
        <p class="text-xs text-gray-500 font-medium mt-1">Kosong</p>
    </x-card>
    <x-card class="text-center py-5">
        <p class="text-3xl font-bold text-red-500">{{ $data['expired_sewa']->count() }}</p>
        <p class="text-xs text-gray-500 font-medium mt-1">Jatuh Tempo</p>
    </x-card>
</div>

{{-- Penghuni List --}}
<h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Penghuni Aktif</h2>
<x-card class="mb-5">
    @if($data['penghuni_aktif']->isEmpty())
    <x-empty-state message="Tidak ada penghuni aktif." />
    @else
    <div class="space-y-3">
        @foreach($data['penghuni_aktif']->take(5) as $pk)
        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center text-sm font-bold text-gray-600 dark:text-gray-300">
                    {{ substr($pk->penghuni->nama, 0, 1) }}
                </div>
                <div>
                    <p class="text-sm font-bold">{{ $pk->penghuni->nama }}</p>
                    <p class="text-xs text-gray-500">{{ $pk->kamar->kode_kamar }} · {{ $pk->kamar->kos->nama }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs font-bold {{ $pk->tanggal_keluar && $pk->tanggal_keluar < now() ? 'text-red-500' : 'text-emerald-600' }}">
                    {{ $pk->tanggal_keluar ? $pk->tanggal_keluar->diffForHumans() : '-' }}
                </p>
            </div>
        </div>
        @endforeach
    </div>
    @if($data['penghuni_aktif']->count() > 5)
    <p class="text-center text-xs text-emerald-600 font-semibold mt-4">+{{ $data['penghuni_aktif']->count() - 5 }} lainnya</p>
    @endif
    @endif
</x-card>

{{-- Pending Payments --}}
<h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Menunggu Verifikasi</h2>
<x-card>
    @if($data['pending_payments']->isEmpty())
    <p class="text-sm text-gray-500 text-center py-6">Tidak ada pembayaran pending.</p>
    @else
    <div class="space-y-3">
        @foreach($data['pending_payments']->take(3) as $p)
        <div class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-100 dark:border-amber-800/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-200 dark:bg-amber-800 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-700 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold">{{ $pk->penghuniKamar->penghuni->nama ?? '-' }}</p>
                    <p class="text-xs text-gray-500">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</p>
                </div>
            </div>
            <x-btn href="{{ route('admin.pembayaran.index') }}" size="sm" variant="outline">Cek</x-btn>
        </div>
        @endforeach
    </div>
    @endif
</x-card>
@endsection