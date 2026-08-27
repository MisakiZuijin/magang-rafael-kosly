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
    <x-page-header title="WhatsApp Gateway (Fonnte)" subtitle="Integrasi API Fonnte, status koneksi nomor WA, & tes pengiriman ({{ $roleLabel }})" backUrl="{{ route('dashboard') }}">
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
        <div class="grid grid-cols-1 gap-3">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full {{ $deviceInfo['connected'] ? 'bg-emerald-500 animate-pulse' : 'bg-red-500' }}"></div>
                <h2 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Status Koneksi Device WhatsApp</h2>
            </div>
            <span class="px-2.5 text-center w-[150px] py-1 text-[10px] font-bold rounded-lg {{ $deviceInfo['connected'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                {{ $deviceInfo['status_text'] }}
            </span>
        </div>

        @if($deviceInfo['connected'])
        <div class="grid grid-cols-2 sm:grid-cols-2 gap-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-xs">
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
                        <span x-text="showToken ? 'Sembunyikan' : 'Lihat'"></span>
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
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-blue-500">Halo! Ini adalah pesan uji coba dari aplikasi Kosly menggunakan WhatsApp Gateway Fonnte.</textarea>
            </div>

            <div class="pt-1 flex justify-end">
                <x-btn type="submit" variant="primary" size="sm" class="grid grid-cols-1 sm:grid-cols-2 gap-2 !min-h-[38px] text-xs font-bold px-5 bg-blue-600 hover:bg-blue-700">
                    <span class="[&>svg]:h-5 [&>svg]:w-5">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor"
                            viewBox="0 0 448 512">
                            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
                            <path
                                d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                        </svg>
                    </span>
                    Test Kirim Pesan WA
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