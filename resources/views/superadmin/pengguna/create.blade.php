@extends('layouts.app')

@section('title', 'Tambah Pengguna Baru - Super Admin')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Tambah Pengguna Baru</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Buat akun pengguna baru (Admin, Mitra, atau Penghuni)</p>
        </div>
        <x-btn href="{{ route('superadmin.pengguna.index') }}" variant="secondary" size="sm" class="!min-h-[36px] !py-1 text-xs">
            &larr; Kembali
        </x-btn>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm">
        <form action="{{ route('superadmin.pengguna.store') }}" method="POST" class="space-y-3.5">
            @csrf

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Pilih Role / Peran</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="relative flex items-center justify-center p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer focus-within:ring-2 focus-within:ring-blue-500 hover:bg-gray-50 dark:hover:bg-gray-800">
                        <input type="radio" name="role" value="admin" class="sr-only peer" {{ old('role') === 'admin' ? 'checked' : '' }}>
                        <div class="text-center peer-checked:text-blue-600 dark:peer-checked:text-blue-400">
                            <p class="text-xs font-bold">Admin</p>
                            <p class="text-[10px] text-gray-400">Pengelola</p>
                        </div>
                    </label>

                    <label class="relative flex items-center justify-center p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer focus-within:ring-2 focus-within:ring-emerald-500 hover:bg-gray-50 dark:hover:bg-gray-800">
                        <input type="radio" name="role" value="mitra" class="sr-only peer" {{ old('role', 'mitra') === 'mitra' ? 'checked' : '' }}>
                        <div class="text-center peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400">
                            <p class="text-xs font-bold">Mitra Kos</p>
                            <p class="text-[10px] text-gray-400">Pemilik Kos</p>
                        </div>
                    </label>

                    <label class="relative flex items-center justify-center p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer focus-within:ring-2 focus-within:ring-purple-500 hover:bg-gray-50 dark:hover:bg-gray-800">
                        <input type="radio" name="role" value="penghuni" class="sr-only peer" {{ old('role') === 'penghuni' ? 'checked' : '' }}>
                        <div class="text-center peer-checked:text-purple-600 dark:peer-checked:text-purple-400">
                            <p class="text-xs font-bold">Penghuni Kos</p>
                            <p class="text-[10px] text-gray-400">Anak Kos</p>
                        </div>
                    </label>
                </div>
                @error('role') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Lengkap</label>
                <input type="text" name="nama" value="{{ old('nama') }}" required placeholder="Masukkan nama pengguna" 
                       class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">
                @error('nama') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="contoh@domain.com" 
                       class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">
                @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">No. HP / WhatsApp</label>
                <input type="text" name="no_hp" value="{{ old('no_hp') }}" placeholder="08xxxxxxxxxx" 
                       class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">
                @error('no_hp') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Password</label>
                <input type="password" name="password" required placeholder="Minimal 6 karakter" 
                       class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">
                @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="pt-2">
                <x-btn type="submit" variant="primary" size="md" class="w-full">
                    Simpan & Buat Akun
                </x-btn>
            </div>
        </form>
    </div>
</div>
@endsection
