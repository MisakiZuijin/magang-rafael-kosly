@extends('layouts.app')

@section('title', 'Kelola Lokasi Kantor')

@section('content')
<div class="space-y-4" x-data="{ 
    modalTambah: false, 
    modalEdit: false, 
    editKantor: {}, 
    search: '',
    matchSearch(text) {
        if (!this.search) return true;
        return text.toLowerCase().includes(this.search.toLowerCase());
    }
}">
    {{-- Header --}}
    <x-page-header title="Kelola Lokasi Kantor" subtitle="Pengaturan titik koordinat & alamat kantor admin untuk peta navigasi" backUrl="{{ route('dashboard') }}" />

    <x-btn @click="modalTambah = true" size="sm" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-sm active:scale-95 transition-all text-xs flex items-center justify-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tambah Kantor</span>
    </x-btn>

    {{-- Search Bar --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-3 border border-gray-200 dark:border-gray-800 shadow-sm">
        <div class="relative">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" x-model="search" placeholder="Cari nama kantor atau alamat..."
                class="w-full pl-9 pr-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">
        </div>
    </div>

    {{-- List Cards Kantor --}}
    <div class="space-y-3">
        @forelse($kantors as $kan)
        <div x-show="matchSearch(@js($kan->nama . ' ' . ($kan->alamat ?? '')))"
            class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-lg flex-shrink-0">
                        🏢
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $kan->nama }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $kan->alamat ?? 'Alamat belum diatur' }}</p>

                        <div class="flex flex-wrap items-center gap-3 mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            @if($kan->link_gmaps)
                            <a href="{{ $kan->link_gmaps }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 font-semibold">
                                <span>📍 Link Google Maps</span>
                            </a>
                            @endif
                            @if($kan->no_telp)
                            <span>📞 {{ $kan->no_telp }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <x-badge type="{{ $kan->is_active ? 'success' : 'danger' }}">
                    {{ $kan->is_active ? 'Aktif' : 'Nonaktif' }}
                </x-badge>
            </div>

            <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                <button type="button" @click="editKantor = @js($kan); modalEdit = true"
                    class="flex-1 py-1.5 px-3 !min-h-[34px] bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition-all text-center">
                    Edit Kantor
                </button>

                <form action="{{ route('superadmin.kantor.toggle', $kan->slug ?? $kan->id) }}" method="POST" class="flex-1">
                    @csrf
                    <x-btn type="submit" size="sm" variant="{{ $kan->is_active ? 'danger' : 'primary' }}" class="w-full !min-h-[34px] !py-1 text-xs">
                        {{ $kan->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </x-btn>
                </form>

                <form action="{{ route('superadmin.kantor.destroy', $kan->slug ?? $kan->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data kantor ini?')" class="flex-shrink-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-2 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-900/50 text-red-600 dark:text-red-400 rounded-xl transition-all" title="Hapus Kantor">
                        🗑️
                    </button>
                </form>
            </div>
        </div>
        @empty
        <x-empty-state message="Belum ada data kantor admin terdaftar." />
        @endforelse
    </div>

    {{-- Modal Tambah Kantor --}}
    <div x-show="modalTambah" x-transition class="absolute -top-20 -left-4 -right-4 -bottom-16 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md overflow-y-auto" x-cloak>
        <div class="bg-white dark:bg-gray-900 rounded-3xl w-full max-w-[390px] max-h-[85vh] overflow-y-auto no-scrollbar p-5 border border-gray-200 dark:border-gray-800 shadow-2xl space-y-4 my-auto relative transform transition-all" @click.away="modalTambah = false">
            <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-800 pb-3">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Tambah Lokasi Kantor Baru</h3>
                <button @click="modalTambah = false" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
            </div>

            <form action="{{ route('superadmin.kantor.store') }}" method="POST" class="space-y-3.5">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Kantor</label>
                    <input type="text" name="nama" required placeholder="Contoh: Kantor Pusat Surabaya" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Alamat Lengkap</label>
                    <textarea name="alamat" rows="3" placeholder="Tuliskan jalan, nomor, dan kota..." class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Link Google Maps</label>
                    <input type="text" name="link_gmaps" placeholder="https://maps.google.com/..." class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">No. Telp Kantor (Opsional)</label>
                    <input type="text" name="no_telp" placeholder="031-xxxxxxx" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <x-btn type="button" variant="secondary" size="sm" @click="modalTambah = false">Batal</x-btn>
                    <x-btn type="submit" variant="primary" size="sm">Simpan Lokasi Kantor</x-btn>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Kantor --}}
    <div x-show="modalEdit" x-transition class="absolute -top-20 -left-4 -right-4 -bottom-16 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md overflow-y-auto" x-cloak>
        <div class="bg-white dark:bg-gray-900 rounded-3xl w-full max-w-[390px] max-h-[85vh] overflow-y-auto no-scrollbar p-5 border border-gray-200 dark:border-gray-800 shadow-2xl space-y-4 my-auto relative transform transition-all" @click.away="modalEdit = false">
            <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-800 pb-3">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Edit Lokasi Kantor</h3>
                <button @click="modalEdit = false" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
            </div>

            <form :action="`{{ url('/superadmin/kantor') }}/${editKantor.slug || editKantor.id}`" method="POST" class="space-y-3.5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Kantor</label>
                    <input type="text" name="nama" x-model="editKantor.nama" required class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Alamat Lengkap</label>
                    <textarea name="alamat" x-model="editKantor.alamat" rows="3" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Link Google Maps</label>
                    <input type="text" name="link_gmaps" x-model="editKantor.link_gmaps" placeholder="https://maps.google.com/..." class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">No. Telp Kantor</label>
                    <input type="text" name="no_telp" x-model="editKantor.no_telp" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <x-btn type="button" variant="secondary" size="sm" @click="modalEdit = false">Batal</x-btn>
                    <x-btn type="submit" variant="primary" size="sm">Update Lokasi Kantor</x-btn>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection