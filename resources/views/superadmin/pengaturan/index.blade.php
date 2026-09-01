@extends('layouts.app')

@section('title', 'Pengaturan Website - ' . $appName)

@section('content')
<div class="space-y-4" x-data="{
    webName: '{{ addslashes($appName) }}',
    logoPreview: '{{ $appLogo }}',
    faviconPreview: '{{ $appFavicon }}',
    logoFileName: '',
    faviconFileName: '',
    handleLogoChange(e) {
        const file = e.target.files[0];
        if (file) {
            this.logoFileName = file.name;
            this.logoPreview = URL.createObjectURL(file);
        } else {
            this.logoFileName = '';
        }
    },
    handleFaviconChange(e) {
        const file = e.target.files[0];
        if (file) {
            this.faviconFileName = file.name;
            this.faviconPreview = URL.createObjectURL(file);
        } else {
            this.faviconFileName = '';
        }
    }
}">
    {{-- Header --}}
    <x-page-header title="Pengaturan Website" subtitle="Kelola identitas, logo, dan favicon aplikasi" backUrl="{{ route('superadmin.dashboard') }}" />

    {{-- Kartu Live Preview Identitas (Mobile-First) --}}
    <div class="bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-transparent border border-emerald-500/20 rounded-2xl p-4 shadow-sm">
        <div class="flex items-center gap-3.5">
            <div class="w-14 h-14 rounded-2xl bg-white dark:bg-gray-800 p-2 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-center flex-shrink-0">
                <img :src="logoPreview" alt="Logo Preview" class="max-w-full max-h-full object-contain">
            </div>
            <div class="min-w-0 flex-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-mono block">Preview Tampilan</span>
                <h2 class="text-base font-bold text-gray-900 dark:text-white truncate" x-text="webName || 'Nama Web'"></h2>
                <div class="flex items-center w-max gap-1.5 mt-0.5 px-2 py-1.5 rounded-full bg-white dark:bg-gray-800/50">
                    <img :src="faviconPreview" class="w-3.5 h-3.5 object-contain flex-shrink-0" alt="Favicon">
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 font-mono truncate" x-text="(webName || 'Kosly') + ' - Tab Browser'"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Formulir Pengaturan --}}
    <form action="{{ route('superadmin.pengaturan.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        {{-- Section 1: Nama Website --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <div class="flex items-center gap-2 pb-2.5 border-b border-gray-100 dark:border-gray-800">
                <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Nama Website / Aplikasi</h3>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                    Nama Web <span class="text-red-500">*</span>
                </label>
                <input type="text" name="app_name" x-model="webName" required maxlength="100"
                    class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none"
                    placeholder="Contoh: Kostly">
                <p class="text-[10px] text-gray-400 dark:text-gray-500">Tampil di seluruh judul halaman, navbar, footer, login, dan nota.</p>
                @error('app_name')
                <p class="text-[11px] text-red-500 font-semibold">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Section 2: Logo Website --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <div class="flex items-center gap-2 pb-2.5 border-b border-gray-100 dark:border-gray-800">
                <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Logo Website</h3>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Unggah Logo Website</span>
                    </label>
                    <span class="text-[10px] text-gray-400 font-mono">PNG, JPG, WEBP, SVG</span>
                </div>

                {{-- Dropzone Input Desain Pembayaran --}}
                <label class="group relative flex flex-col items-center justify-center w-full py-4 px-3 border-2 border-dashed border-blue-400/80 dark:border-blue-700/80 bg-blue-50/50 dark:bg-blue-950/30 hover:bg-blue-100/50 dark:hover:bg-blue-900/40 rounded-2xl cursor-pointer transition-all text-center">
                    <input type="file" name="app_logo" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                        @change="handleLogoChange($event)"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                    <div class="space-y-1.5 flex flex-col items-center">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300 rounded-2xl flex items-center justify-center shadow-xs group-hover:scale-110 transition-transform p-2 border border-blue-200 dark:border-blue-800">
                            <img :src="logoPreview" alt="Logo Preview" class="max-w-full max-h-full object-contain">
                        </div>

                        <p class="text-xs font-bold text-gray-800 dark:text-gray-200">
                            Klik di sini untuk memilih berkas logo baru
                        </p>

                        <p class="text-[11px] font-semibold font-mono"
                            :class="logoFileName ? 'text-blue-700 dark:text-blue-300' : 'text-gray-400 dark:text-gray-500'"
                            x-text="logoFileName ? '📷 Logo dipilih: ' + logoFileName : 'Maksimal ukuran 2MB (disarankan transparan)'">
                        </p>
                    </div>
                </label>

                @error('app_logo')
                <p class="text-[11px] text-red-500 font-semibold">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Section 3: Favicon Website --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <div class="flex items-center gap-2 pb-2.5 border-b border-gray-100 dark:border-gray-800">
                <div class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Favicon Website</h3>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                        <span>Unggah Favicon Web</span>
                    </label>
                    <span class="text-[10px] text-gray-400 font-mono">.ICO / .PNG</span>
                </div>

                {{-- Dropzone Input Desain Pembayaran --}}
                <label class="group relative flex flex-col items-center justify-center w-full py-4 px-3 border-2 border-dashed border-amber-400/80 dark:border-amber-700/80 bg-amber-50/50 dark:bg-amber-950/30 hover:bg-amber-100/50 dark:hover:bg-amber-900/40 rounded-2xl cursor-pointer transition-all text-center">
                    <input type="file" name="app_favicon" accept=".ico,image/x-icon,image/png,image/svg+xml"
                        @change="handleFaviconChange($event)"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                    <div class="space-y-1.5 flex flex-col items-center">
                        <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-300 rounded-xl flex items-center justify-center shadow-xs group-hover:scale-110 transition-transform p-1.5 border border-amber-200 dark:border-amber-800">
                            <img :src="faviconPreview" alt="Favicon Preview" class="w-5 h-5 object-contain">
                        </div>

                        <p class="text-xs font-bold text-gray-800 dark:text-gray-200">
                            Klik di sini untuk memilih berkas favicon
                        </p>

                        <p class="text-[11px] font-semibold font-mono"
                            :class="faviconFileName ? 'text-amber-700 dark:text-amber-300' : 'text-gray-400 dark:text-gray-500'"
                            x-text="faviconFileName ? '🔖 Favicon dipilih: ' + faviconFileName : 'Disimpan langsung ke public/favicon.ico'">
                        </p>
                    </div>
                </label>

                @error('app_favicon')
                <p class="text-[11px] text-red-500 font-semibold">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Submit Button (Mobile-First Full Width) --}}
        <div class="pt-2">
            <x-btn type="submit" variant="primary" size="md" class="w-full !min-h-[44px] text-xs font-bold shadow-xs">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Simpan Perubahan
            </x-btn>
        </div>
    </form>
</div>
@endsection