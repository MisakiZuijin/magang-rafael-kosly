@extends('layouts.app')

@section('title', 'Kelola Admin System')

@section('content')
<div class="space-y-4" x-data="{ 
    modalTambah: false, 
    modalEdit: false, 
    editAdmin: {}, 
    search: '',
    matchSearch(text) {
        if (!this.search) return true;
        return text.toLowerCase().includes(this.search.toLowerCase());
    }
}">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Kelola Akun Admin</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Tambah, update, dan atur hak akses akun pengelola / Admin</p>
        </div>
    </div>

    <x-btn @click="modalTambah = true" size="sm" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-sm active:scale-95 transition-all text-xs flex items-center justify-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tambah Admin</span>
    </x-btn>

    {{-- Search Bar --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-3 border border-gray-200 dark:border-gray-800 shadow-sm">
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
        <div x-show="matchSearch(@js($adm->nama . ' ' . $adm->email . ' ' . ($adm->no_hp ?? '')))"
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
                <button type="button" @click="editAdmin = @js($adm); modalEdit = true"
                    class="flex-1 py-1.5 px-3 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition-all text-center">
                    ✏️ Edit Data
                </button>

                <form action="{{ route('superadmin.admin.toggle', $adm->id) }}" method="POST" class="flex-1">
                    @csrf
                    <x-btn type="submit" size="sm" variant="{{ $adm->is_active ? 'danger' : 'primary' }}" class="w-full !min-h-[34px] !py-1 text-xs">
                        {{ $adm->is_active ? '🚫 Nonaktifkan' : '✅ Aktifkan' }}
                    </x-btn>
                </form>

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