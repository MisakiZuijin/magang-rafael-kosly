@extends('layouts.app')

@section('title', 'Kelola Pengguna - Super Admin')

@section('content')
<div class="space-y-4" x-data="{ 
    activeTab: 'mitra', 
    search: '',
    matchSearch(text) {
        if (!this.search) return true;
        return text.toLowerCase().includes(this.search.toLowerCase());
    }
}">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Pengguna System</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kelola akun Mitra Kos dan Anak Kos</p>
        </div>
    </div>

    <x-btn href="{{ route('superadmin.pengguna.create') }}" size="sm" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-sm active:scale-95 transition-all text-xs flex items-center justify-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tambah User</span>
    </x-btn>

    {{-- Tabs & Search --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-3 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="grid grid-cols-2 gap-1.5 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
            <button @click="activeTab = 'mitra'"
                :class="activeTab === 'mitra' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400 font-medium'"
                class="py-2 text-xs rounded-lg transition-all text-center">
                Mitra Kos ({{ $mitras->count() }})
            </button>
            <button @click="activeTab = 'penghuni'"
                :class="activeTab === 'penghuni' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400 font-medium'"
                class="py-2 text-xs rounded-lg transition-all text-center">
                Anak Kos ({{ $penghunis->count() }})
            </button>
        </div>

        <div class="relative">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" x-model="search" placeholder="Cari nama atau email pengguna..."
                class="w-full pl-9 pr-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-emerald-500 focus:outline-none">
        </div>
    </div>

    {{-- Tab Mitra Kos --}}
    <div x-show="activeTab === 'mitra'" class="space-y-3" x-transition>
        @forelse($mitras as $m)
        <div x-show="matchSearch(@js($m->nama . ' ' . $m->email . ' ' . ($m->no_hp ?? '')))"
            class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-full flex items-center justify-center text-amber-700 dark:text-amber-300 font-bold text-sm">
                        {{ substr($m->nama, 0, 1) }}
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $m->nama }}</p>
                        <p class="text-xs text-gray-500 font-mono">{{ $m->email }}</p>
                        @if($m->no_hp)
                        <p class="text-[11px] text-gray-400 mt-0.5">HP: {{ $m->no_hp }}</p>
                        @endif
                    </div>
                </div>
                <x-badge type="{{ $m->is_active ? 'success' : 'danger' }}">
                    {{ $m->is_active ? 'Aktif' : 'Nonaktif' }}
                </x-badge>
            </div>

            <div class="flex gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                <x-btn href="{{ route('superadmin.pengguna.edit', $m->id) }}" size="sm" variant="secondary" class="flex-1 !min-h-[36px] !py-1 text-xs dark:hover:bg-gray-700">
                    Edit
                </x-btn>
                <form action="{{ route('superadmin.pengguna.toggle', $m->id) }}" method="POST" class="flex-1">
                    @csrf
                    <x-btn type="submit" size="sm" variant="{{ $m->is_active ? 'danger' : 'primary' }}" class="w-full !min-h-[36px] !py-1 text-xs">
                        {{ $m->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </x-btn>
                </form>
            </div>
        </div>
        @empty
        <x-empty-state message="Belum ada akun Mitra Kos terdaftar." />
        @endforelse
    </div>

    {{-- Tab Penghuni Kos --}}
    <div x-show="activeTab === 'penghuni'" class="space-y-3" x-transition x-cloak>
        @forelse($penghunis as $p)
        <div x-show="matchSearch(@js($p->nama . ' ' . $p->email . ' ' . ($p->no_hp ?? '')))"
            class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-full flex items-center justify-center text-emerald-700 dark:text-emerald-300 font-bold text-sm">
                        {{ substr($p->nama, 0, 1) }}
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $p->nama }}</p>
                        <p class="text-xs text-gray-500 font-mono">{{ $p->email }}</p>
                        @if($p->no_hp)
                        <p class="text-[11px] text-gray-400 mt-0.5">HP: {{ $p->no_hp }}</p>
                        @endif
                    </div>
                </div>
                <x-badge type="{{ $p->is_active ? 'success' : 'danger' }}">
                    {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                </x-badge>
            </div>

            <div class="flex gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                <x-btn href="{{ route('superadmin.pengguna.edit', $p->id) }}" size="sm" variant="secondary" class="flex-1 !min-h-[36px] !py-1 text-xs dark:hover:bg-gray-700">
                    Edit
                </x-btn>
                <form action="{{ route('superadmin.pengguna.toggle', $p->id) }}" method="POST" class="flex-1">
                    @csrf
                    <x-btn type="submit" size="sm" variant="{{ $p->is_active ? 'danger' : 'primary' }}" class="w-full !min-h-[36px] !py-1 text-xs">
                        {{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </x-btn>
                </form>
            </div>
        </div>
        @empty
        <x-empty-state message="Belum ada akun Anak Kos terdaftar." />
        @endforelse
    </div>
</div>
@endsection