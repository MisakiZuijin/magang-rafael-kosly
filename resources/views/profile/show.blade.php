@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<h1 class="text-xl font-bold mb-5 dark:text-white">Profil Saya</h1>

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