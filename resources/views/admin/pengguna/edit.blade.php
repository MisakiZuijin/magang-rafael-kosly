@extends('layouts.app')

@php
$isSuperAdmin = request()->is('superadmin*');
$indexRoute = $isSuperAdmin ? route('superadmin.pengguna.index') : route('admin.pengguna.index');
$updateRoute = $isSuperAdmin ? route('superadmin.pengguna.update', $user->slug ?? $user->id) : route('admin.pengguna.update', $user->slug ?? $user->id);
@endphp

@section('title', 'Edit Pengguna')

@section('content')
<div class="space-y-4">
    <x-page-header 
        title="Edit Pengguna" 
        subtitle="Perbarui informasi akun {{ $user->nama }}" 
        backUrl="{{ $indexRoute }}" 
    />

    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm">
        <form action="{{ $updateRoute }}" method="POST" class="space-y-3.5">
            @csrf
            @method('PUT')

            <div class="p-3 bg-gray-50 dark:bg-gray-800/40 rounded-xl border border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Role / Peran</span>
                    <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase">{{ str_replace('_', ' ', $user->role) }}</p>
                </div>
                <x-badge type="{{ $user->is_active ? 'success' : 'danger' }}">
                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                </x-badge>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Lengkap</label>
                <input type="text" name="nama" value="{{ old('nama', $user->nama) }}" required 
                       class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">
                @error('nama') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required 
                       class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">
                @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">No. HP / WhatsApp</label>
                <input type="text" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" 
                       class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">
                @error('no_hp') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div x-data="{ show: false }">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Password Baru (Opsional)</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="password" placeholder="Kosongkan jika tidak ingin mengubah password" 
                           class="w-full px-3.5 py-2.5 pr-10 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">
                    <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none flex items-center justify-center w-4 h-4">
                        <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12c.729-2.3 2.184-4.24 4.095-5.59A10.84 10.84 0 0112 5c1.884 0 3.696.486 5.27 1.341 1.91 1.35 3.365 3.29 4.095 5.59-.73 2.3-2.184 4.24-4.095 5.59A10.84 10.84 0 0112 19c-1.884 0-3.696-.486-5.27-1.341C4.82 16.31 3.365 14.37 2.036 12z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div x-data="{ show: false }">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Konfirmasi Password Baru</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="password_confirmation" placeholder="Ketik ulang password baru" 
                           class="w-full px-3.5 py-2.5 pr-10 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">
                    <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none flex items-center justify-center w-4 h-4">
                        <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12c.729-2.3 2.184-4.24 4.095-5.59A10.84 10.84 0 0112 5c1.884 0 3.696.486 5.27 1.341 1.91 1.35 3.365 3.29 4.095 5.59-.73 2.3-2.184 4.24-4.095 5.59A10.84 10.84 0 0112 19c-1.884 0-3.696-.486-5.27-1.341C4.82 16.31 3.365 14.37 2.036 12z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="pt-2">
                <x-btn type="submit" variant="primary" size="md" class="w-full">
                    Simpan Perubahan
                </x-btn>
            </div>
        </form>
    </div>
</div>
@endsection
