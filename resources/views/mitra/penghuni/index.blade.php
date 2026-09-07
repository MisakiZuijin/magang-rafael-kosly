@extends('layouts.app')

@section('title', 'Daftar Penghuni')

@section('content')
@php
$penghunisCount = $penghunis->count();
$totalAktif = $penghunis->where('is_active', true)->count();
$totalNonaktif = $penghunis->where('is_active', false)->count();
@endphp

<div class="space-y-4" x-data="{ 
    statusFilter: 'semua',
    search: '',
    showToggleModal: false,
    toggleUser: null,
    toggleUrl: '',
    showDeleteModal: false,
    deleteUser: null,
    deleteUrl: '',
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
    },
    confirmDelete(id, nama, email, url) {
        this.deleteUser = { id: id, nama: nama, email: email };
        this.deleteUrl = url;
        this.showDeleteModal = true;
    }
}">
    {{-- Header --}}
    <x-page-header title="Daftar Penghuni" subtitle="Kelola akun penghuni yang Anda daftarkan" backUrl="{{ route('mitra.dashboard') }}" />

    {{-- Tambah Button --}}
    <x-btn href="{{ route('mitra.penghuni.create') }}" size="sm" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-sm active:scale-95 transition-all text-xs flex items-center justify-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tambah Penghuni Baru</span>
    </x-btn>

    {{-- Filter Status & Search Input --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-3 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        {{-- Filter Status Pills (Semua / Aktif / Nonaktif) --}}
        <div class="grid grid-cols-3 gap-1.5 p-1 bg-gray-50 dark:bg-gray-800/60 rounded-xl text-xs font-semibold border border-gray-100 dark:border-gray-800">
            <button type="button" @click="statusFilter = 'semua'"
                :class="statusFilter === 'semua' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400'"
                class="py-1.5 rounded-lg transition-all text-center">
                Semua ({{ $penghunisCount }})
            </button>
            <button type="button" @click="statusFilter = 'aktif'"
                :class="statusFilter === 'aktif' ? 'bg-emerald-500 text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400'"
                class="py-1.5 rounded-lg transition-all text-center">
                Aktif ({{ $totalAktif }})
            </button>
            <button type="button" @click="statusFilter = 'nonaktif'"
                :class="statusFilter === 'nonaktif' ? 'bg-red-500 text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400'"
                class="py-1.5 rounded-lg transition-all text-center">
                Nonaktif ({{ $totalNonaktif }})
            </button>
        </div>

        {{-- Search Input --}}
        <div class="relative">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" x-model="search" placeholder="Cari nama atau email penghuni..."
                class="w-full pl-9 pr-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-emerald-500 focus:outline-none">
        </div>
    </div>

    {{-- List Penghuni --}}
    <div class="space-y-3">
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
                        @php
                        $activePk = $p->penghuniKamar ? $p->penghuniKamar->where('status', 'aktif')->first() : null;
                        $waUrl = \App\Services\WhatsAppService::generatePenghuniUrl($p, $activePk);
                        @endphp
                        <a href="{{ $waUrl }}" target="_blank" class="inline-flex items-center gap-1.5 text-[11px] text-emerald-600 dark:text-emerald-400 font-mono mt-0.5 hover:underline font-bold" title="Chat WhatsApp ke {{ $p->nama }}">
                            <span>📱 {{ $p->no_hp }}</span>
                            <span class="px-1.5 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-[9px] font-bold">Chat WA</span>
                        </a>
                        @endif
                    </div>
                </div>
                <x-badge type="{{ $p->is_active ? 'success' : 'danger' }}">
                    {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                </x-badge>
            </div>

            <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                <x-btn href="{{ route('mitra.penghuni.edit', $p->slug ?? $p->id) }}" size="sm" variant="secondary" class="flex-1 !min-h-[36px] !py-1 text-xs">
                    Edit
                </x-btn>
                <button type="button"
                    @click="confirmToggle({{ $p->id }}, '{{ addslashes($p->nama) }}', '{{ addslashes($p->email) }}', {{ $p->is_active ? 'true' : 'false' }}, '{{ route('mitra.penghuni.toggle-active', $p->id) }}')"
                    class="flex-1 min-h-[36px] py-1 px-3 text-xs font-bold rounded-xl text-white transition-all active:scale-95 {{ $p->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                    {{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
                <button type="button"
                    @click="confirmDelete({{ $p->id }}, '{{ addslashes($p->nama) }}', '{{ addslashes($p->email) }}', '{{ route('mitra.penghuni.destroy', $p->slug ?? $p->id) }}')"
                    class="flex-1 min-h-[36px] py-1 px-3 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-900/50 text-red-600 dark:text-red-400 rounded-xl transition-all font-bold text-xs" title="Hapus Akun Penghuni">
                    Hapus
                </button>
            </div>
        </div>
        @empty
        <x-empty-state message="Belum ada akun Penghuni terdaftar." />
        @endforelse
    </div>

    {{-- Modal Konfirmasi Status Pengguna --}}
    <x-modal show="showToggleModal" title="Konfirmasi Status Akun Pengguna">
        <div class="space-y-4">
            <div class="p-3.5 rounded-2xl border text-xs space-y-1.5"
                :class="(toggleUser && toggleUser.is_active) ? 'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-800/50 text-red-700 dark:text-red-300' : 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-300'">
                <p class="font-bold text-sm" x-text="(toggleUser && toggleUser.is_active) ? '⚠️ Menonaktifkan Akun Penghuni' : '✅ Mengaktifkan Akun Penghuni'"></p>
                <p>
                    Apakah Anda yakin ingin <span class="font-bold" x-text="(toggleUser && toggleUser.is_active) ? 'menonaktifkan' : 'mengaktifkan'"></span> akun <strong x-text="toggleUser ? toggleUser.nama : ''"></strong> (<span x-text="toggleUser ? toggleUser.email : ''"></span>)?
                </p>
                <p class="text-[11px] leading-relaxed font-semibold mt-1 p-2 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-lg" x-show="toggleUser && toggleUser.is_active">
                    ℹ️ <strong>Informasi Penonaktifan:</strong> Data akun penghuni <u>tetap disimpan di database</u> (status nonaktif). Penghuni yang dinonaktifkan <strong>tidak akan dapat melakukan login</strong> ke sistem.
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

    {{-- Modal Hapus Akun Penghuni Permanen --}}
    <x-modal show="showDeleteModal" title="Hapus Akun Penghuni Permanen">
        <div class="space-y-4">
            <div class="p-3.5 rounded-2xl border border-red-200 dark:border-red-800/50 bg-red-50 dark:bg-red-950/30 text-red-700 dark:text-red-300 text-xs space-y-1.5">
                <p class="font-bold text-sm">🗑️ Hapus Akun Permanen</p>
                <p>
                    Apakah Anda yakin ingin menghapus akun Penghuni <strong x-text="deleteUser ? deleteUser.nama : ''"></strong> (<span x-text="deleteUser ? deleteUser.email : ''"></span>)?
                </p>
                <p class="text-[11px] leading-relaxed font-semibold text-red-600 dark:text-red-400">
                    ⚠️ <strong>PERINGATAN:</strong> Tindakan ini akan menghapus data akun penghuni beserta riwayat terkait dari database secara <strong>PERMANEN</strong> dan tidak dapat dikembalikan.
                </p>
            </div>

            <form :action="deleteUrl" method="POST" class="pt-2 flex justify-end gap-2">
                @csrf
                @method('DELETE')
                <x-btn type="button" variant="secondary" size="sm" @click="showDeleteModal = false">Batal</x-btn>
                <button type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 font-bold text-xs rounded-xl shadow-sm active:scale-95 transition-all">
                    Ya, Hapus Permanen
                </button>
            </form>
        </div>
    </x-modal>
</div>
@endsection
