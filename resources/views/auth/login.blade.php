@extends('layouts.auth')

@section('title', 'Login - Kosly')

@section('content')
<div class="w-full max-w-sm">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
        <div class="text-center mb-6">
            <div class="w-12 h-12 bg-emerald-500 rounded-xl flex items-center justify-center text-white font-bold text-xl mx-auto mb-3">K</div>
            <h1 class="text-xl font-bold">Kosly</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manajemen Kos</p>
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
            <div>
                <label class="block text-sm font-medium mb-1.5">Password</label>
                <input type="password" name="password" required
                    class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition text-sm">
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