@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Selamat datang,</p>
        <h1 class="text-xl font-bold dark:text-white">{{ explode(' ', Auth::user()->nama)[0] }}</h1>
    </div>
</div>

@if(session('show_aturan_popup'))
<x-modal show="true" title="Aturan Kos">
    <p class="mb-2">Harap baca dan patuhi aturan kos yang berlaku di tempat tinggal Anda.</p>
    @slot('footer')
    <x-btn variant="secondary" @click="open = false">Nanti</x-btn>
    <x-btn @click="open = false; fetch('{{ route('penghuni.aturan.dismiss') }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'}, body:JSON.stringify({kos_id:{{ session('kos_id_popup') ?? 'null' }}})})">
        Mengerti
    </x-btn>
    @endslot
</x-modal>
@endif

@if($data['kos'])
{{-- Info Card --}}
<x-card class="mb-3 border-l-4 border-l-emerald-500">
    <div class="flex justify-between items-start mb-4">
        <div>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Kos Anda</p>
            <h2 class="font-bold text-lg leading-tight dark:text-white">{{ $data['kos']->nama }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $data['kamar']->kode_kamar }}</p>
        </div>
        <x-badge type="success">{{ $data['durasi'] }}</x-badge>
    </div>

    <x-stat-grid :items="[
                ['label' => 'Biaya', 'value' => 'Rp ' . number_format($data['total_biaya'], 0, ',', '.')],
                ['label' => 'Penghuni', 'value' => $data['jumlah_penghuni'] . ' org'],
                ['label' => 'Kapasitas', 'value' => $data['kamar']->kapasitas . ' org'],
                ['label' => 'Sisa Waktu', 'value' => ''],
            ]" />

    {{-- Countdown: Initial value dari PHP, Alpine.js hanya update. Tidak ada layout shift --}}
    @if($data['tanggal_keluar'])
    @php
    $diff = now()->diff($data['tanggal_keluar']);
    $isExpired = now()->gt($data['tanggal_keluar']);
    $initialText = $isExpired
    ? 'Sudah habis'
    : ($diff->d > 0
    ? $diff->d . ' hari ' . $diff->h . ' jam ' . $diff->i . ' menit'
    : ($diff->h > 0
    ? $diff->h . ' jam ' . $diff->i . ' menit'
    : $diff->i . ' menit'));
    @endphp

    <div x-data="{ 
                    target: new Date('{{ $data['tanggal_keluar']->format('Y-m-d H:i:s') }}').getTime(),
                    formatted: '{{ $initialText }}',
                    timer: null,
                    start() {
                        this.update();
                        this.timer = setInterval(() => this.update(), 60000);
                    },
                    update() {
                        const distance = this.target - new Date().getTime();
                        if (distance < 0) { 
                            this.formatted = 'Sudah habis'; 
                            clearInterval(this.timer); 
                            return; 
                        }
                        const d = Math.floor(distance / (1000*60*60*24));
                        const h = Math.floor((distance % (1000*60*60*24)) / (1000*60*60));
                        const m = Math.floor((distance % (1000*60*60)) / (1000*60));
                        this.formatted = d > 0 ? `${d} hari ${h} jam ${m} menit` : (h > 0 ? `${h} jam ${m} menit` : `${m} menit`);
                    }
                }" x-init="start()" class="mt-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-800/50 min-h-[64px] flex flex-col justify-center">
        <p class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">Sisa Waktu</p>
        <p class="text-xl font-bold text-emerald-700 dark:text-emerald-300 font-mono" x-text="formatted">{{ $initialText }}</p>
    </div>
    @else
    <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl min-h-[64px] flex flex-col justify-center">
        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Sisa Waktu</p>
        <p class="text-lg font-bold text-gray-600 dark:text-gray-400">-</p>
    </div>
    @endif
</x-card>

{{-- Rekening Card --}}
<x-card title="Pembayaran" class="mb-3 dark:text-white">
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold dark:text-white">{{ $data['kos']->bank }}</p>
                    <p class="text-xs text-gray-500 font-mono">{{ $data['kos']->no_rekening }}</p>
                </div>
            </div>
            <p class="text-xs text-gray-500 pl-10">{{ $data['kos']->nama_pemilik_rekening }}</p>
        </div>
        <x-btn href="{{ route('penghuni.pembayaran') }}" size="sm">Bayar</x-btn>
    </div>
</x-card>

{{-- Quick Actions --}}
<div class="grid grid-cols-2 gap-3">
    <a href="{{ route('penghuni.aturan') }}" class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm active:scale-[0.98] transition-transform">
        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>
        <p class="text-sm font-bold dark:text-white">Aturan</p>
        <p class="text-xs text-gray-500 mt-0.5">Lihat aturan kos</p>
    </a>
    <a href="{{ route('penghuni.pembayaran') }}" class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm active:scale-[0.98] transition-transform">
        <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        </div>
        <p class="text-sm font-bold dark:text-white">Riwayat</p>
        <p class="text-xs text-gray-500 mt-0.5">Cek pembayaran</p>
    </a>
</div>
@else
<x-empty-state message="Anda belum terdaftar di kamar manapun." />
@endif
@endsection