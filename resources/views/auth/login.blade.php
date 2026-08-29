@extends('layouts.auth')

@section('title', 'Login - Kosly')

@section('content')
<div class="w-full max-w-sm">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
        <div class="grid grid-cols-1 text-center mb-6">
            <img src="{{ asset('images/logo.png') }}" alt="Kosly Logo" class="sm:col-span-2 w-25 h-25 object-contain mx-auto mb-3 drop-shadow-md">
            <!-- <h1 class="col-span-4 text-xl font-bold text-emerald-600 uppercase">Kosly</h1> -->
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Selamat Datang, Silahkan Login!</p>
        </div>

        @if($errors->any())
        <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1.5">Email</label>
                <input type="email" name="email" required autofocus
                    class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition text-sm">
            </div>
            <div x-data="{ show: false }">
                <label class="block text-sm font-medium mb-1.5">Password</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="password" required
                        class="w-full px-3 py-2.5 pr-10 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition text-sm">
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
            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-gray-600 dark:text-gray-400">Ingat saya</span>
                </label>
            </div>
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 rounded-lg transition text-sm">
                Masuk
            </button>
        </form>
    </div>
</div>
@endsection