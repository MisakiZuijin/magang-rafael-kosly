@extends('layouts.app')

@section('title', 'WhatsApp Gateway Mandiri - Mitra Pro')

@section('content')
<div class="space-y-4" x-data="{ showToken: false }">
    {{-- Header --}}
    <x-page-header title="WhatsApp Gateway Mandiri" subtitle="Integrasi Fonnte pribadi untuk notifikasi & pengumuman kos Anda" backUrl="{{ route('dashboard') }}">
        @slot('action')
        <button onclick="window.location.reload()" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white bg-gray-100 dark:bg-gray-800 rounded-xl transition-all" title="Refresh Status">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </button>
        @endslot
    </x-page-header>

    {{-- Card Status Device WA Realtime --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full {{ $deviceStatus['connected'] ? 'bg-emerald-500 animate-pulse' : 'bg-red-500' }}"></div>
                <h2 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Status Perangkat WhatsApp Anda</h2>
            </div>
            <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg text-center self-start sm:self-auto {{ $deviceStatus['connected'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                {{ $deviceStatus['status_text'] }}
            </span>
        </div>

        @if($deviceStatus['connected'])
        <div class="grid grid-cols-2 sm:grid-cols-2 gap-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-xs">
            <div class="p-2.5 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800">
                <span class="text-[10px] text-gray-400 font-semibold uppercase block">Nomor Device</span>
                <span class="font-bold font-mono text-gray-900 dark:text-white block mt-0.5">{{ $deviceStatus['device'] }}</span>
            </div>
            <div class="p-2.5 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800">
                <span class="text-[10px] text-gray-400 font-semibold uppercase block">Nama Device</span>
                <span class="font-bold text-gray-900 dark:text-white block mt-0.5 truncate">{{ $deviceStatus['name'] }}</span>
            </div>
            <div class="p-2.5 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800">
                <span class="text-[10px] text-gray-400 font-semibold uppercase block">Sisa Kuota WA</span>
                <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 block mt-0.5">{{ number_format((int)$deviceStatus['quota'], 0, ',', '.') }} Pesan</span>
            </div>
            <div class="p-2.5 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800">
                <span class="text-[10px] text-gray-400 font-semibold uppercase block">Paket & Expired</span>
                <span class="font-bold text-gray-900 dark:text-white block mt-0.5 text-[11px]">{{ ucfirst($deviceStatus['package']) }} ({{ $deviceStatus['expired'] }})</span>
            </div>
        </div>
        @else
        <div class="p-3 bg-amber-50/60 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-900/50 text-xs text-amber-800 dark:text-amber-300 space-y-1">
            <p class="font-bold flex items-center gap-1.5">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Perangkat WhatsApp Belum Terhubung</span>
            </p>
            <p class="text-[11px] text-amber-700 dark:text-amber-400">
                {{ $deviceStatus['message'] ?? 'Pastikan API Token Fonnte pribadi Anda sudah dimasukkan dan nomor WhatsApp Anda telah di-scan di dashboard fonnte.com.' }}
            </p>
        </div>
        @endif
    </div>

    {{-- Form Konfigurasi Token Fonnte Pribadi --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="flex items-center gap-2 border-b border-gray-100 dark:border-gray-800 pb-2">
            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
            <h2 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Konfigurasi Gateway WA Pribadi</h2>
        </div>

        <form action="{{ route('mitra.whatsapp.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    API Token Fonnte Pribadi
                </label>
                <div class="relative">
                    <input :type="showToken ? 'text' : 'password'"
                        name="wa_gateway_token"
                        value="{{ old('wa_gateway_token', $token) }}"
                        placeholder="Contoh: 8xK9pL2mQ0vWnRtY..."
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white pr-16 focus:ring-emerald-500">
                    <button type="button" @click="showToken = !showToken" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-white text-xs">
                        <span x-text="showToken ? 'Sembunyikan' : 'Lihat'"></span>
                    </button>
                </div>
                <p class="text-[10px] text-gray-400 mt-1 italic">* Token API Fonnte didapatkan dari dashboard akun Fonnte Anda di <a href="https://fonnte.com" target="_blank" class="text-emerald-600 underline">fonnte.com</a>. Jika diisi, seluruh pesan otomatis & pengumuman kos Anda akan dikirim menggunakan nomor WA Anda sendiri.</p>
                @error('wa_gateway_token') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Endpoint API Fonnte
                </label>
                <input type="url"
                    name="wa_gateway_endpoint"
                    value="{{ old('wa_gateway_endpoint', $endpoint) }}"
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white focus:ring-emerald-500">
                @error('wa_gateway_endpoint') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <x-btn type="submit" size="sm" class="w-full flex items-center justify-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
                <span>Simpan Konfigurasi Gateway</span>
            </x-btn>
        </form>
    </div>

    {{-- Form Tes Kirim Pesan Uji Coba --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="flex items-center gap-2 border-b border-gray-100 dark:border-gray-800 pb-2">
            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            <h2 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Tes Kirim Pesan Uji Coba</h2>
        </div>

        <form action="{{ route('mitra.whatsapp.test') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Nomor Tujuan / ID Grup WA Target <span class="text-red-500">*</span>
                </label>
                <input type="text"
                    name="target"
                    required
                    placeholder="Contoh: 081234567890 atau 12036304xxxx@g.us"
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white focus:ring-emerald-500">
                <p class="text-[10px] text-gray-400 mt-0.5">Bisa berupa nomor WhatsApp pribadi atau ID Grup WhatsApp.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Isi Pesan Uji Coba <span class="text-red-500">*</span>
                </label>
                <textarea name="pesan"
                    rows="2"
                    required
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">Halo, ini adalah pesan uji coba dari WhatsApp Gateway Mitra Pro.</textarea>
            </div>

            <x-btn type="submit" variant="secondary" size="sm" class="w-full flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 fill-current flex-shrink-0" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                    <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                </svg>
                <span>Kirim Pesan Tes Sekarang</span>
            </x-btn>
        </form>
    </div>
</div>
@endsection
