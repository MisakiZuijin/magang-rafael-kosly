@extends('layouts.app')

@section('title', 'Kelola Admin System')

@section('content')
<div class="space-y-4" x-data="{ 
    modalTambah: false, 
    modalEdit: false, 
    showToggleModal: false,
    toggleUser: null,
    toggleUrl: '',
    editAdmin: {}, 
    search: '',
    statusFilter: 'semua',
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
    <x-page-header title="Kelola Akun Admin" subtitle="Tambah, update, dan atur hak akses akun pengelola / Admin" />

    <x-btn @click="modalTambah = true" size="sm" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-sm active:scale-95 transition-all text-xs flex items-center justify-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tambah Admin</span>
    </x-btn>

    {{-- Search & Status Filter Bar --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-3 border border-gray-200 dark:border-gray-800 shadow-sm space-y-2.5">
        {{-- Filter Status Pills --}}
        <div class="grid grid-cols-3 gap-1.5 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl text-xs font-semibold">
            <button type="button" @click="statusFilter = 'semua'"
                :class="statusFilter === 'semua' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400'"
                class="py-1.5 rounded-lg transition-all text-center">
                Semua ({{ $admins->count() }})
            </button>
            <button type="button" @click="statusFilter = 'aktif'"
                :class="statusFilter === 'aktif' ? 'bg-emerald-500 text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400'"
                class="py-1.5 rounded-lg transition-all text-center">
                Aktif ({{ $admins->where('is_active', true)->count() }})
            </button>
            <button type="button" @click="statusFilter = 'nonaktif'"
                :class="statusFilter === 'nonaktif' ? 'bg-red-500 text-white shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400'"
                class="py-1.5 rounded-lg transition-all text-center">
                Nonaktif ({{ $admins->where('is_active', false)->count() }})
            </button>
        </div>

        {{-- Search Input --}}
        <div class="relative">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" x-model="search" placeholder="Cari nama, email, atau no HP admin..."
                class="w-full pl-9 pr-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">
        </div>
    </div>

    {{-- List Cards Admin --}}
    <div class="space-y-3">
        @forelse($admins as $adm)
        <div x-show="matchSearch('{{ addslashes(strtolower($adm->nama . ' ' . $adm->email . ' ' . ($adm->no_hp ?? ''))) }}', {{ $adm->is_active ? 'true' : 'false' }})"
            class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/40 rounded-full flex items-center justify-center text-purple-700 dark:text-purple-300 font-bold text-sm flex-shrink-0">
                        {{ substr($adm->nama, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $adm->nama }}</p>
                            <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                ADMIN
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 font-mono truncate mt-0.5">{{ $adm->email }}</p>
                        @if($adm->no_hp)
                        <p class="text-[11px] text-gray-400 mt-0.5">📱 {{ $adm->no_hp }}</p>
                        @endif
                    </div>
                </div>
                <x-badge type="{{ $adm->is_active ? 'success' : 'danger' }}">
                    {{ $adm->is_active ? 'Aktif' : 'Nonaktif' }}
                </x-badge>
            </div>

            <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                <button type="button" @click="editAdmin = { id: {{ $adm->id }}, nama: '{{ addslashes($adm->nama) }}', email: '{{ addslashes($adm->email) }}', no_hp: '{{ addslashes($adm->no_hp ?? '') }}' }; modalEdit = true"
                    class="flex-1 py-1.5 px-3 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition-all text-center">
                    ✏️ Edit Data
                </button>

                <button type="button"
                        @click="confirmToggle({{ $adm->id }}, '{{ addslashes($adm->nama) }}', '{{ addslashes($adm->email) }}', {{ $adm->is_active ? 'true' : 'false' }}, '{{ route('superadmin.admin.toggle', $adm->id) }}')"
                        class="flex-1 min-h-[34px] py-1 px-3 text-xs font-bold rounded-xl text-white transition-all active:scale-95 {{ $adm->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                    {{ $adm->is_active ? '🚫 Nonaktifkan' : '✅ Aktifkan' }}
                </button>

                <form action="{{ route('superadmin.admin.destroy', $adm->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun admin ini?')" class="flex-shrink-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-2 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-900/50 text-red-600 dark:text-red-400 rounded-xl transition-all" title="Hapus Admin">
                        🗑️
                    </button>
                </form>
            </div>
        </div>
        @empty
        <x-empty-state message="Belum ada akun Admin terdaftar di sistem." />
        @endforelse
    </div>

    {{-- Modal Konfirmasi Status Admin --}}
    <x-modal show="showToggleModal" title="Konfirmasi Status Akun Admin">
        <div class="space-y-4">
            <div class="p-3.5 rounded-2xl border text-xs space-y-1.5"
                 :class="(toggleUser && toggleUser.is_active) ? 'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-800/50 text-red-700 dark:text-red-300' : 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-300'">
                <p class="font-bold text-sm" x-text="(toggleUser && toggleUser.is_active) ? '⚠️ Menonaktifkan Akun Admin' : '✅ Mengaktifkan Akun Admin'"></p>
                <p>
                    Apakah Anda yakin ingin <span class="font-bold" x-text="(toggleUser && toggleUser.is_active) ? 'menonaktifkan' : 'mengaktifkan'"></span> akun admin <strong x-text="toggleUser ? toggleUser.nama : ''"></strong> (<span x-text="toggleUser ? toggleUser.email : ''"></span>)?
                </p>
                <p class="text-[11px] leading-relaxed opacity-90" x-show="toggleUser && toggleUser.is_active">
                    Saat dinonaktifkan, akun admin ini tidak akan dapat login atau mengakses sistem. Anda dapat mengaktifkannya kembali kapan saja melalui filter 'Nonaktif'.
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

    {{-- Modal Tambah Admin --}}
    <div x-show="modalTambah" x-transition class="absolute -top-20 -left-4 -right-4 -bottom-16 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md overflow-y-auto" x-cloak>
        <div class="bg-white dark:bg-gray-900 rounded-3xl w-full max-w-[390px] max-h-[85vh] overflow-y-auto no-scrollbar p-5 border border-gray-200 dark:border-gray-800 shadow-2xl space-y-4 my-auto relative transform transition-all" @click.away="modalTambah = false">
            <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-800 pb-3">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Tambah Akun Admin Baru</h3>
                <button @click="modalTambah = false" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
            </div>

            <form action="{{ route('superadmin.admin.store') }}" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Lengkap</label>
                    <input type="text" name="nama" required placeholder="Masukkan nama admin" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Email</label>
                    <input type="email" name="email" required placeholder="admin@domain.com" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">No. HP / WhatsApp</label>
                    <input type="text" name="no_hp" placeholder="08xxxxxxxxxx" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Password</label>
                    <input type="password" name="password" required placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <x-btn type="button" variant="secondary" size="sm" @click="modalTambah = false">Batal</x-btn>
                    <x-btn type="submit" variant="primary" size="sm">Simpan Akun Admin</x-btn>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Admin --}}
    <div x-show="modalEdit" x-transition class="absolute -top-20 -left-4 -right-4 -bottom-16 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md overflow-y-auto" x-cloak>
        <div class="bg-white dark:bg-gray-900 rounded-3xl w-full max-w-[390px] max-h-[85vh] overflow-y-auto no-scrollbar p-5 border border-gray-200 dark:border-gray-800 shadow-2xl space-y-4 my-auto relative transform transition-all" @click.away="modalEdit = false">
            <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-800 pb-3">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Edit Akun Admin</h3>
                <button @click="modalEdit = false" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
            </div>

            <form :action="`{{ url('/superadmin/admin') }}/${editAdmin.id}`" method="POST" class="space-y-3.5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Lengkap</label>
                    <input type="text" name="nama" x-model="editAdmin.nama" required class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Email</label>
                    <input type="email" name="email" x-model="editAdmin.email" required class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">No. HP / WhatsApp</label>
                    <input type="text" name="no_hp" x-model="editAdmin.no_hp" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Password Baru (Opsional)</label>
                    <input type="password" name="password" placeholder="Kosongkan jika tidak ingin merubah" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <x-btn type="button" variant="secondary" size="sm" @click="modalEdit = false">Batal</x-btn>
                    <x-btn type="submit" variant="primary" size="sm">Update Admin</x-btn>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection