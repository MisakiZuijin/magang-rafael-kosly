@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="mb-5">
    <x-page-header title="Profil Saya" subtitle="Pengaturan informasi akun Anda" backUrl="{{ route('dashboard') }}" />
</div>

<x-card class="mb-4">
    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf

        {{-- Foto Profil --}}
        <div class="flex flex-col items-center">
            <div class="relative">
                @php
                $defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->nama) . '&background=10b981&color=fff&size=128';
                @endphp
                <img src="{{ Auth::user()->foto_profile ? asset('storage/' . Auth::user()->foto_profile) : $defaultAvatar }}"
                    class="w-24 h-24 rounded-full object-cover ring-4 ring-gray-100 dark:ring-gray-800"
                    onerror="this.src='{{ $defaultAvatar }}'">
                <label for="foto_profile" class="absolute bottom-0 right-0 w-8 h-8 bg-emerald-500 hover:bg-emerald-600 rounded-full flex items-center justify-center cursor-pointer shadow-lg active:scale-95 transition-transform">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </label>
                <input type="file" id="foto_profile" name="foto_profile" accept="image/*" class="hidden" onchange="this.form.submit()">
            </div>
            <p class="text-xs text-gray-400 mt-2">Ketuk ikon kamera untuk ganti foto</p>
        </div>

        {{-- Nama --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Nama Lengkap</label>
            <input type="text" name="nama" value="{{ Auth::user()->nama }}" required
                class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
        </div>

        {{-- Email (Readonly) --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
            <input type="email" value="{{ Auth::user()->email }}" disabled
                class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800/50 text-sm text-gray-500 dark:text-gray-400 cursor-not-allowed">
        </div>

        {{-- No HP --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Nomor HP</label>
            <input type="tel" name="no_hp" value="{{ Auth::user()->no_hp }}" placeholder="08xxxxxxxxxx"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
        </div>

        {{-- Ubah Password --}}
        <div class="pt-3 border-t border-gray-100 dark:border-gray-800 space-y-4">
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ubah Password (Opsional)</p>

            <div x-data="{ show: false }">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Password Baru</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="password" placeholder="Kosongkan jika tidak ingin diubah (min. 6 karakter)"
                        class="w-full px-4 py-3 pr-11 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                    <button type="button" @click="show = !show" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none flex items-center justify-center w-5 h-5">
                        <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12c.729-2.3 2.184-4.24 4.095-5.59A10.84 10.84 0 0112 5c1.884 0 3.696.486 5.27 1.341 1.91 1.35 3.365 3.29 4.095 5.59-.73 2.3-2.184 4.24-4.095 5.59A10.84 10.84 0 0112 19c-1.884 0-3.696-.486-5.27-1.341C4.82 16.31 3.365 14.37 2.036 12z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div x-data="{ show: false }">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Konfirmasi Password Baru</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="password_confirmation" placeholder="Ketik ulang password baru"
                        class="w-full px-4 py-3 pr-11 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                    <button type="button" @click="show = !show" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none flex items-center justify-center w-5 h-5">
                        <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12c.729-2.3 2.184-4.24 4.095-5.59A10.84 10.84 0 0112 5c1.884 0 3.696.486 5.27 1.341 1.91 1.35 3.365 3.29 4.095 5.59-.73 2.3-2.184 4.24-4.095 5.59A10.84 10.84 0 0112 19c-1.884 0-3.696-.486-5.27-1.341C4.82 16.31 3.365 14.37 2.036 12z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Role Info --}}
        <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Role Akun</p>
            <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wide
                    {{ Auth::user()->role === 'super_admin' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : '' }}
                    {{ Auth::user()->role === 'admin' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                    {{ Auth::user()->role === 'mitra' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}
                    {{ Auth::user()->role === 'penghuni' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}">
                {{ str_replace('_', ' ', Auth::user()->role) }}
            </span>
        </div>

        <x-btn type="submit" class="w-full">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Simpan Perubahan
        </x-btn>
    </form>
</x-card>

{{-- Info Akun --}}
<x-card title="Informasi Akun" class="mb-4 dark:text-white">
    <div class="space-y-3 text-sm">
        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800">
            <span class="text-gray-500 dark:text-gray-400">Bergabung Sejak</span>
            <span class="font-medium text-gray-700 dark:text-gray-200">{{ Auth::user()->created_at->format('d M Y') }}</span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800">
            <span class="text-gray-500 dark:text-gray-400">Status</span>
            <span class="font-medium {{ Auth::user()->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                {{ Auth::user()->is_active ? 'Aktif' : 'Nonaktif' }}
            </span>
        </div>
        <div class="flex justify-between items-center py-2">
            <span class="text-gray-500 dark:text-gray-400">Terakhir Diperbarui</span>
            <span class="font-medium text-gray-700 dark:text-gray-200">{{ Auth::user()->updated_at->diffForHumans() }}</span>
        </div>
    </div>
</x-card>
@endsection