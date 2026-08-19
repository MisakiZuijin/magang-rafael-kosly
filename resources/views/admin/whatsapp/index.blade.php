@extends('layouts.app')

@php
$isSuperAdmin = request()->is('superadmin*');
$roleLabel = $isSuperAdmin ? 'Super Admin' : 'Admin';
$formRoute = $isSuperAdmin ? route('superadmin.whatsapp.store') : route('admin.whatsapp.store');
$testRoute = $isSuperAdmin ? route('superadmin.whatsapp.test') : route('admin.whatsapp.test');
@endphp

@section('title', 'Pengaturan WhatsApp Gateway - ' . $roleLabel)

@section('content')
<div class="space-y-4" x-data="{ showToken: false }">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">WhatsApp Gateway (Fonnte)</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Integrasi API Fonnte, status koneksi nomor WA, &amp; tes pengiriman ({{ $roleLabel }})</p>
        </div>
        <button onclick="window.location.reload()" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white bg-gray-100 dark:bg-gray-800 rounded-xl transition-all" title="Refresh Status">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </button>
    </div>

    {{-- Card Status Device WA Realtime --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="grid grid-cols-1 gap-3">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full {{ $deviceInfo['connected'] ? 'bg-emerald-500 animate-pulse' : 'bg-red-500' }}"></div>
                <h2 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Status Koneksi Device WhatsApp</h2>
            </div>
            <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg {{ $deviceInfo['connected'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                {{ $deviceInfo['status_text'] }}
            </span>
        </div>

        @if($deviceInfo['connected'])
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-xs">
            <div class="p-2.5 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800">
                <span class="text-[10px] text-gray-400 font-semibold uppercase block">Nomor Device</span>
                <span class="font-bold font-mono text-gray-900 dark:text-white block mt-0.5">{{ $deviceInfo['device'] }}</span>
            </div>
            <div class="p-2.5 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800">
                <span class="text-[10px] text-gray-400 font-semibold uppercase block">Nama Device</span>
                <span class="font-bold text-gray-900 dark:text-white block mt-0.5 truncate">{{ $deviceInfo['name'] }}</span>
            </div>
            <div class="p-2.5 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800">
                <span class="text-[10px] text-gray-400 font-semibold uppercase block">Sisa Kuota WA</span>
                <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 block mt-0.5">{{ number_format((int)$deviceInfo['quota'], 0, ',', '.') }} Pesan</span>
            </div>
            <div class="p-2.5 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800">
                <span class="text-[10px] text-gray-400 font-semibold uppercase block">Paket &amp; Expired</span>
                <span class="font-bold text-gray-900 dark:text-white block mt-0.5 text-[11px]">{{ ucfirst($deviceInfo['package']) }} ({{ $deviceInfo['expired'] }})</span>
            </div>
        </div>
        @else
        <div class="p-3 bg-amber-50/60 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-900/50 text-xs text-amber-800 dark:text-amber-300 space-y-1">
            <p class="font-bold">⚠️ Perangkat WhatsApp Belum Terhubung</p>
            <p class="text-[11px] text-amber-700 dark:text-amber-400">
                {{ $deviceInfo['message'] ?? 'Pastikan API Token Fonnte sudah benar dan nomor WhatsApp pengelola telah di-scan di panel fonnte.com.' }}
            </p>
        </div>
        @endif
    </div>

    {{-- Form Configuration API Token Fonnte --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="flex items-center gap-2 border-b border-gray-100 dark:border-gray-800 pb-2">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
            <h2 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Konfigurasi Fonnte API Key</h2>
        </div>

        <form action="{{ $formRoute }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    API Token Fonnte <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input :type="showToken ? 'text' : 'password'"
                        name="fonnte_api_key"
                        value="{{ old('fonnte_api_key', $apiKey) }}"
                        placeholder="Contoh: 8xK9pL2mQ0vWnRtY..."
                        required
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white pr-10 focus:ring-emerald-500">
                    <button type="button" @click="showToken = !showToken" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-white text-xs">
                        <span x-text="showToken ? '🔒 Sembunyikan' : '👁️ Lihat'"></span>
                    </button>
                </div>
                <p class="text-[10px] text-gray-400 mt-1 italic">* Token API unik ini didapatkan dari dashboard akun Fonnte Anda di fonnte.com.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Endpoint API Fonnte
                </label>
                <input type="url"
                    name="fonnte_endpoint"
                    value="{{ old('fonnte_endpoint', $endpoint) }}"
                    required
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white focus:ring-emerald-500">
            </div>

            <div class="pt-1 flex justify-end">
                <x-btn type="submit" variant="primary" size="sm" class="!min-h-[38px] text-xs font-bold px-5">
                    💾 Simpan Pengaturan Fonnte
                </x-btn>
            </div>
        </form>
    </div>

    {{-- Form Tes Kirim Pesan WA --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="flex items-center gap-2 border-b border-gray-100 dark:border-gray-800 pb-2">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            <h2 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Tes Kirim Pesan WhatsApp</h2>
        </div>

        <form action="{{ $testRoute }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Target Penerasi (Nomor HP / ID Grup WA) <span class="text-red-500">*</span>
                </label>
                <input type="text"
                    name="target"
                    placeholder="Contoh Nomor Personal: 081234567890 atau ID Grup: 120363xxxx@g.us"
                    required
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono text-gray-900 dark:text-white focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Judul / Subjek Pesan</label>
                <input type="text"
                    name="judul"
                    value="Uji Coba WhatsApp Gateway"
                    required
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Isi Pesan WhatsApp</label>
                <textarea name="pesan" rows="3" required placeholder="Tuliskan pesan tes pengiriman WhatsApp..."
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-blue-500">Halo! Ini adalah pesan uji coba dari aplikasi Kostly menggunakan WhatsApp Gateway Fonnte.</textarea>
            </div>

            <div class="pt-1 flex justify-end">
                <x-btn type="submit" variant="primary" size="sm" class="!min-h-[38px] text-xs font-bold px-5 bg-blue-600 hover:bg-blue-700">
                    🚀 Kirim Pesan Tes WA
                </x-btn>
            </div>
        </form>
    </div>

    {{-- Panduan Sambung Fonnte --}}
    <div class="bg-gray-50 dark:bg-gray-800/40 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 space-y-2 text-xs">
        <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
            <span>📖</span> Cara Registrasi &amp; Sambungkan WhatsApp di Fonnte:
        </h3>
        <ol class="list-decimal list-inside space-y-1 text-gray-600 dark:text-gray-300 text-[11px] leading-relaxed">
            <li>Daftar akun gratis di <a href="https://fonnte.com" target="_blank" class="text-emerald-600 font-bold hover:underline">fonnte.com</a>.</li>
            <li>Tambah Device baru dan salin <strong>API Token</strong> yang diberikan Fonnte.</li>
            <li>Tempelkan Token API ke kolom <strong>API Token Fonnte</strong> di atas, lalu klik <strong>Simpan</strong>.</li>
            <li>Buka WhatsApp di HP pengelola kos &rarr; pilih <strong>Perangkat Tertaut (*Linked Devices*)</strong> &rarr; Scan Barcode QR Code yang ada di dashboard Fonnte.</li>
            <li>Untuk <strong>Grup WhatsApp Kos</strong>: Masukkan ID Grup WA (format `...@g.us`) di menu <strong>Pendaftaran Kos &amp; Kamar</strong> agar pengumuman kos otomatis masuk ke grup WA anak kos.</li>
        </ol>
    </div>
</div>
@endsection