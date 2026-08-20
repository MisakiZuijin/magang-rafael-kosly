@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
<div class="space-y-4" x-data="{ 
    activeTab: 'mitra', 
    statusFilter: 'semua',
    search: '',
    showToggleModal: false,
    toggleUser: null,
    toggleUrl: '',
    matchSearch(text, isActive) {
        const matchesSearch = !this.search || text.toLowerCase().includes(this.search.toLowerCase());
        const matchesStatus = this.statusFilter === 'semua' || 
                              (this.statusFilter === 'aktif' && isActive) || 
                              (this.statusFilter === 'nonaktif' && !isActive);
        return matchesSearch && matchesStatus;
    },
    confirmToggle(id, nama, email, isActive, url) {
        this.toggleUser = { id: id, nama: nama, email: email, is_active: Boolean(isActive) };
        this.toggleUrl = url;
        this.showToggleModal = true;
    }
}">
    {{-- Header --}}
    <x-page-header title="Pengguna System" subtitle="Kelola akun Mitra Kos dan Anak Kos" />

    <x-btn href="{{ request()->is('superadmin*') ? route('superadmin.pengguna.create') : route('admin.pengguna.create') }}" size="sm" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-sm active:scale-95 transition-all text-xs flex items-center justify-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tambah User</span>
    </x-btn>

    {{-- Tabs, Status Filter & Search --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-3 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        {{-- Primary Role Tabs --}}
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

        {{-- Filter Status Pills (Semua / Aktif / Nonaktif) --}}
        <div class="grid grid-cols-3 gap-1.5 p-1 bg-gray-50 dark:bg-gray-800/60 rounded-xl text-xs font-semibold border border-gray-100 dark:border-gray-800">
            <button type="button" @click="statusFilter = 'semua'"
                :class="statusFilter === 'semua' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400'"
                class="py-1.5 rounded-lg transition-all text-center">
                Semua
            </button>
            <button type="button" @click="statusFilter = 'aktif'"
                :class="statusFilter === 'aktif' ? 'bg-emerald-500 text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400'"
                class="py-1.5 rounded-lg transition-all text-center">
                Aktif ({{ $mitras->where('is_active', true)->count() + $penghunis->where('is_active', true)->count() }})
            </button>
            <button type="button" @click="statusFilter = 'nonaktif'"
                :class="statusFilter === 'nonaktif' ? 'bg-red-500 text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400'"
                class="py-1.5 rounded-lg transition-all text-center">
                Nonaktif ({{ $mitras->where('is_active', false)->count() + $penghunis->where('is_active', false)->count() }})
            </button>
        </div>

        {{-- Search Input --}}
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
        <div x-show="matchSearch('{{ addslashes(strtolower($m->nama . ' ' . $m->email . ' ' . ($m->no_hp ?? ''))) }}', {{ $m->is_active ? 'true' : 'false' }})"
            class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-full flex items-center justify-center text-amber-700 dark:text-amber-300 font-bold text-sm flex-shrink-0">
                        {{ substr($m->nama, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $m->nama }}</p>
                        <p class="text-xs text-gray-500 font-mono truncate">{{ $m->email }}</p>
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
                <x-btn href="{{ request()->is('superadmin*') ? route('superadmin.pengguna.edit', $m->id) : route('admin.pengguna.edit', $m->id) }}" size="sm" variant="secondary" class="flex-1 !min-h-[36px] !py-1 text-xs">
                    Edit
                </x-btn>
                <button type="button"
                        @click="confirmToggle({{ $m->id }}, '{{ addslashes($m->nama) }}', '{{ addslashes($m->email) }}', {{ $m->is_active ? 'true' : 'false' }}, '{{ request()->is('superadmin*') ? route('superadmin.pengguna.toggle', $m->id) : route('admin.pengguna.toggle', $m->id) }}')"
                        class="flex-1 min-h-[36px] py-1 px-3 text-xs font-bold rounded-xl text-white transition-all active:scale-95 {{ $m->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                    {{ $m->is_active ? '🚫 Nonaktifkan' : '✅ Aktifkan' }}
                </button>
            </div>
        </div>
        @empty
        <x-empty-state message="Belum ada akun Mitra Kos terdaftar." />
        @endforelse
    </div>

    {{-- Tab Penghuni Kos --}}
    <div x-show="activeTab === 'penghuni'" class="space-y-3" x-transition x-cloak>
        @forelse($penghunis as $p)
        <div x-show="matchSearch('{{ addslashes(strtolower($p->nama . ' ' . $p->email . ' ' . ($p->no_hp ?? ''))) }}', {{ $p->is_active ? 'true' : 'false' }})"
            class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-full flex items-center justify-center text-emerald-700 dark:text-emerald-300 font-bold text-sm flex-shrink-0">
                        {{ substr($p->nama, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $p->nama }}</p>
                        <p class="text-xs text-gray-500 font-mono truncate">{{ $p->email }}</p>
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
                <x-btn href="{{ request()->is('superadmin*') ? route('superadmin.pengguna.edit', $p->id) : route('admin.pengguna.edit', $p->id) }}" size="sm" variant="secondary" class="flex-1 !min-h-[36px] !py-1 text-xs">
                    Edit
                </x-btn>
                <button type="button"
                        @click="confirmToggle({{ $p->id }}, '{{ addslashes($p->nama) }}', '{{ addslashes($p->email) }}', {{ $p->is_active ? 'true' : 'false' }}, '{{ request()->is('superadmin*') ? route('superadmin.pengguna.toggle', $p->id) : route('admin.pengguna.toggle', $p->id) }}')"
                        class="flex-1 min-h-[36px] py-1 px-3 text-xs font-bold rounded-xl text-white transition-all active:scale-95 {{ $p->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                    {{ $p->is_active ? '🚫 Nonaktifkan' : '✅ Aktifkan' }}
                </button>
            </div>
        </div>
        @empty
        <x-empty-state message="Belum ada akun Anak Kos terdaftar." />
        @endforelse
    </div>

    {{-- Modal Konfirmasi Status Pengguna --}}
    <x-modal show="showToggleModal" title="Konfirmasi Status Akun Pengguna">
        <div class="space-y-4">
            <div class="p-3.5 rounded-2xl border text-xs space-y-1.5"
                 :class="(toggleUser && toggleUser.is_active) ? 'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-800/50 text-red-700 dark:text-red-300' : 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-300'">
                <p class="font-bold text-sm" x-text="(toggleUser && toggleUser.is_active) ? '⚠️ Menonaktifkan Akun Pengguna' : '✅ Mengaktifkan Akun Pengguna'"></p>
                <p>
                    Apakah Anda yakin ingin <span class="font-bold" x-text="(toggleUser && toggleUser.is_active) ? 'menonaktifkan' : 'mengaktifkan'"></span> akun <strong x-text="toggleUser ? toggleUser.nama : ''"></strong> (<span x-text="toggleUser ? toggleUser.email : ''"></span>)?
                </p>
                <p class="text-[11px] leading-relaxed opacity-90" x-show="toggleUser && toggleUser.is_active">
                    Saat dinonaktifkan, akun ini tidak dapat login ke aplikasi. Anda dapat mengaktifkannya kembali kapan saja melalui filter status 'Nonaktif'.
                </p>
            </div>

            <form :action="toggleUrl" method="POST" class="pt-2 flex justify-end gap-2">
                @csrf
                <x-btn type="button" variant="secondary" size="sm" @click="showToggleModal = false">Batal</x-btn>
                <button type="submit"
                        :class="(toggleUser && toggleUser.is_active) ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white'"
                        class="px-4 py-2 font-bold text-xs rounded-xl shadow-sm active:scale-95 transition-all">
                    <span x-text="(toggleUser && toggleUser.is_active) ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan'"></span>
                </button>
            </form>
        </div>
    </x-modal>
</div>
@endsection