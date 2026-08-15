@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold">Pengguna</h1>
    <x-btn href="{{ route('admin.pengguna.create') }}" size="sm">+</x-btn>
</div>

{{-- Mitra --}}
<h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Mitra Kos</h2>
<div class="space-y-3 mb-6">
    @forelse($mitras as $m)
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm">
        <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center text-amber-600 font-bold text-sm">
                    {{ substr($m->nama, 0, 1) }}
                </div>
                <div>
                    <p class="text-sm font-bold">{{ $m->nama }}</p>
                    <p class="text-xs text-gray-500">{{ $m->email }}</p>
                </div>
            </div>
            <x-badge type="{{ $m->is_active ? 'success' : 'danger' }}">{{ $m->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
        </div>
        <div class="flex gap-2">
            <x-btn href="{{ route('admin.pengguna.edit', $m->id) }}" size="sm" variant="secondary" class="flex-1">Edit</x-btn>
            <form action="{{ route('admin.pengguna.toggle', $m->id) }}" method="POST" class="flex-1">
                @csrf
                <x-btn type="submit" size="sm" variant="{{ $m->is_active ? 'danger' : 'success' }}" class="w-full">
                    {{ $m->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </x-btn>
            </form>
        </div>
    </div>
    @empty
    <x-empty-state message="Belum ada mitra." />
    @endforelse
</div>

{{-- Penghuni --}}
<h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Penghuni</h2>
<div class="space-y-3">
    @forelse($penghunis as $p)
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm">
        <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center text-emerald-600 font-bold text-sm">
                    {{ substr($p->nama, 0, 1) }}
                </div>
                <div>
                    <p class="text-sm font-bold">{{ $p->nama }}</p>
                    <p class="text-xs text-gray-500">{{ $p->email }}</p>
                </div>
            </div>
            <x-badge type="{{ $p->is_active ? 'success' : 'danger' }}">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
        </div>
        <div class="flex gap-2">
            <x-btn href="{{ route('admin.pengguna.edit', $p->id) }}" size="sm" variant="secondary" class="flex-1">Edit</x-btn>
            <form action="{{ route('admin.pengguna.toggle', $p->id) }}" method="POST" class="flex-1">
                @csrf
                <x-btn type="submit" size="sm" variant="{{ $p->is_active ? 'danger' : 'success' }}" class="w-full">
                    {{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </x-btn>
            </form>
        </div>
    </div>
    @empty
    <x-empty-state message="Belum ada penghuni." />
    @endforelse
</div>
@endsection