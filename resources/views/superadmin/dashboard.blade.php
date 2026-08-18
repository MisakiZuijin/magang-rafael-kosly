@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Pusat Kendali Utama System</p>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Dashboard Super Admin</h1>
        </div>
        <div class="bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 text-xs font-bold px-3 py-1 rounded-xl flex items-center gap-1.5 border border-purple-200 dark:border-purple-800">
            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Super Admin</span>
        </div>
    </div>

    {{-- User Statistics Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center gap-3 sm:col-span-2">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total User</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $data['total_users'] ?? 0 }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center gap-3 sm:col-span-2">
            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center text-purple-600 dark:text-purple-400 flex-shrink-0">
                👤
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Admin</p>
                <p class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ $data['total_admin'] ?? 0 }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center gap-3 sm:col-span-2">
            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-xl flex items-center justify-center text-amber-600 dark:text-amber-400 flex-shrink-0">
                🏢
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Mitra Kos</p>
                <p class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ $data['total_mitra'] ?? 0 }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center gap-3 sm:col-span-2">
            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl flex items-center justify-center text-emerald-600 dark:text-emerald-400 flex-shrink-0">
                🏠
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Anak Kos</p>
                <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $data['total_penghuni'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    {{-- Property & Room Statistics Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center gap-3 sm:col-span-2">
            <div class="w-9 h-9 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl flex items-center justify-center text-indigo-600 text-xs font-bold">
                🏡
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Kos</p>
                <p class="text-base font-bold text-gray-900 dark:text-white">{{ $data['total_kos'] ?? 0 }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center gap-3 sm:col-span-2">
            <div class="w-9 h-9 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center text-blue-600 text-xs font-bold">
                🛏️
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Kamar</p>
                <p class="text-base font-bold text-gray-900 dark:text-white">{{ $data['total_kamar'] ?? 0 }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center gap-3 sm:col-span-2">
            <div class="w-9 h-9 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl flex items-center justify-center text-emerald-600 text-xs font-bold">
                ✅
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Terisi</p>
                <p class="text-base font-bold text-emerald-600 dark:text-emerald-400">{{ $data['kamar_terisi'] ?? 0 }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center gap-3 sm:col-span-2">
            <div class="w-9 h-9 bg-amber-100 dark:bg-amber-900/40 rounded-xl flex items-center justify-center text-amber-600 text-xs font-bold">
                ⏳
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Kosong</p>
                <p class="text-base font-bold text-amber-600 dark:text-amber-400">{{ $data['kamar_kosong'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    {{-- Grafik Visualisasi Kamar (Occupancy Chart Bar) --}}
    @php
    $total = ($data['total_kamar'] ?? 0) > 0 ? $data['total_kamar'] : 1;
    $pctTerisi = round((($data['kamar_terisi'] ?? 0) / $total) * 100);
    $pctKosong = round((($data['kamar_kosong'] ?? 0) / $total) * 100);
    @endphp
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="flex justify-between items-center">
            <h2 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Grafik Okupansi Kamar Seluruh Kos</h2>
            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $pctTerisi }}% Okupansi</span>
        </div>

        <div class="w-full h-3.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden flex">
            <div class="bg-emerald-500 h-full transition-all duration-500" :style="{ width: '{{ $pctTerisi }}%' }" title="Terisi: {{ $pctTerisi }}%"></div>
            <div class="bg-amber-400 h-full transition-all duration-500" :style="{ width: '{{ $pctKosong }}%' }" title="Kosong: {{ $pctKosong }}%"></div>
        </div>

        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1">
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span>Terisi: <strong class="text-gray-900 dark:text-white">{{ $data['kamar_terisi'] ?? 0 }}</strong> ({{ $pctTerisi }}%)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                <span>Kosong: <strong class="text-gray-900 dark:text-white">{{ $data['kamar_kosong'] ?? 0 }}</strong> ({{ $pctKosong }}%)</span>
            </div>
        </div>
    </div>

    {{-- Quick Shortcuts --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <h2 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Pintas Cepat Tindakan Super Admin</h2>
        <div class="grid grid-cols-2 sm:grid-cols-1 gap-2">
            <x-btn href="{{ route('superadmin.admin.index') }}" variant="secondary" size="sm" class="!justify-start text-xs">
                👤 Kelola Admin
            </x-btn>
            <x-btn href="{{ route('superadmin.kantor.index') }}" variant="secondary" size="sm" class="!justify-start text-xs">
                🏢 Lokasi Kantor
            </x-btn>
            <x-btn href="{{ route('superadmin.pengguna.create') }}" variant="secondary" size="sm" class="!justify-start text-xs">
                ➕ Tambah User
            </x-btn>
            <x-btn href="{{ route('superadmin.pengumuman.create') }}" variant="secondary" size="sm" class="!justify-start text-xs">
                📢 Broadcast WA
            </x-btn>
            <x-btn href="{{ route('superadmin.aturan.index') }}" variant="secondary" size="sm" class="!justify-start text-xs">
                📜 Aturan Kos
            </x-btn>
        </div>
    </div>

    {{-- Status Sewa & Pembayaran --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- Sisa Waktu Masa Sewa --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden p-3.5 space-y-3">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                <h2 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Masa Sewa Penghuni</h2>
                <a href="{{ route('superadmin.kos.index') }}" class="text-[11px] text-emerald-600 font-bold hover:underline">Kelola &rarr;</a>
            </div>
            @if(empty($data['penghuni_aktif']) || $data['penghuni_aktif']->isEmpty())
            <p class="text-xs text-gray-400 py-3 text-center">Belum ada penghuni aktif.</p>
            @else
            <div class="space-y-2 max-h-48 overflow-y-auto no-scrollbar">
                @foreach($data['penghuni_aktif']->take(5) as $pk)
                @php
                $isExpired = $pk->tanggal_keluar && $pk->tanggal_keluar < now();
                    $daysLeft=$pk->tanggal_keluar ? round(now()->diffInDays($pk->tanggal_keluar, false)) : null;
                    @endphp
                    <div class="p-2.5 bg-gray-50 dark:bg-gray-800/40 rounded-xl border border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $pk->penghuni->nama ?? '-' }}</p>
                            <p class="text-[10px] text-gray-500 truncate">{{ $pk->kamar->kode_kamar ?? '-' }} · {{ $pk->kamar->kos->nama ?? '-' }}</p>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg {{ $isExpired ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                            {{ $isExpired ? 'Expired' : ($daysLeft . ' Hr') }}
                        </span>
                    </div>
                    @endforeach
            </div>
            @endif
        </div>

        {{-- Pending Payments --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden p-3.5 space-y-3">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                <h2 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Pembayaran Pending</h2>
                <a href="{{ route('superadmin.pembayaran.index') }}" class="text-[11px] text-emerald-600 font-bold hover:underline">Verifikasi &rarr;</a>
            </div>
            @if(empty($data['pending_payments']) || $data['pending_payments']->isEmpty())
            <p class="text-xs text-gray-400 py-3 text-center">Tidak ada transaksi pending.</p>
            @else
            <div class="space-y-2 max-h-48 overflow-y-auto no-scrollbar">
                @foreach($data['pending_payments']->take(5) as $p)
                <div class="p-2.5 bg-amber-50/60 dark:bg-amber-950/30 rounded-xl border border-amber-100 dark:border-amber-900/40 flex items-center justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $p->penghuniKamar->penghuni->nama ?? 'Penghuni' }}</p>
                        <p class="text-[10px] text-gray-500 font-mono">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</p>
                    </div>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                        Pending
                    </span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
@endsection