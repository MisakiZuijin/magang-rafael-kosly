@extends('layouts.app')

@section('title', 'Kelola Aturan Kos')

@section('content')
<div class="space-y-4" x-data="{ 
    modalTambah: false, 
    modalEdit: false, 
    filterKosId: 'all',
    editData: { id: '', kos_id: '', isi_aturan: '' },
    editUrl: '',
    openEditModal(aturan) {
        this.editData = {
            id: aturan.id,
            kos_id: aturan.kos_id,
            isi_aturan: aturan.isi_aturan || ''
        };
        const prefix = '{{ request()->is('superadmin*') ? 'superadmin' : 'admin' }}';
        this.editUrl = '/' + prefix + '/aturan/' + aturan.id;
        this.modalEdit = true;
    }
}">
    {{-- Header --}}
    <x-page-header title="Kelola Aturan Kos" subtitle="Tambah, edit, dan hapus tata tertib untuk setiap kos" backUrl="{{ route('dashboard') }}" />

    {{-- Action Button --}}
    <button @click="modalTambah = true" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-sm active:scale-95 transition-all text-xs flex items-center justify-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tambah Aturan Baru</span>
    </button>

    {{-- Filter Selector Bar --}}
    @if(!$kosList->isEmpty())
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-3 border border-gray-200 dark:border-gray-800 shadow-sm space-y-1.5">
        <div class="flex items-center justify-between">
            <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                Filter Kos:
            </label>
            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 font-mono"
                x-text="filterKosId === 'all' ? 'Semua Gedung Kos' : 'Kos Terpilih'"></span>
        </div>
        <select x-model="filterKosId" class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-xs font-bold text-gray-900 dark:text-white focus:ring-emerald-500">
            <option value="all">-- Semua Gedung Kos ({{ $kosList->count() }}) --</option>
            @foreach($kosList as $kItem)
            <option value="{{ $kItem->id }}">{{ $kItem->nama }}</option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- List Aturan --}}
    @if($aturans->isEmpty())
    <x-empty-state message="Belum ada aturan kos yang ditambahkan. Klik + Tambah Aturan Baru untuk membuatnya." />
    @else
    <div class="space-y-3">
        @foreach($aturans as $index => $aturan)
        <div x-show="filterKosId === 'all' || filterKosId == '{{ $aturan->kos_id }}'"
            x-transition
            class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <div class="flex justify-between items-start gap-2">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 text-[10px] uppercase font-bold rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        {{ $aturan->kos->nama ?? 'Semua Kos' }}
                    </span>
                    <span class="text-[11px] text-gray-400 font-mono">
                        {{ $aturan->updated_at ? $aturan->updated_at->diffForHumans() : '-' }}
                    </span>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-1.5">
                    <button type="button"
                        @click="openEditModal(@js($aturan))"
                        class="px-2 py-1 text-[11px] font-bold text-amber-700 dark:text-amber-300 bg-amber-100 hover:bg-amber-200 dark:bg-amber-900/40 dark:hover:bg-amber-900/60 rounded-lg transition-all">
                        Edit
                    </button>

                    <form action="{{ route('admin.aturan.destroy', $aturan->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus aturan ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-2 py-1 text-[11px] font-bold text-red-700 dark:text-red-300 bg-red-100 hover:bg-red-200 dark:bg-red-900/40 dark:hover:bg-red-900/60 rounded-lg transition-all">
                            🗑️ Hapus
                        </button>
                    </form>
                </div>
            </div>

            <div class="flex items-start gap-3 bg-gray-50 dark:bg-gray-800/40 p-3 rounded-xl border border-gray-100 dark:border-gray-800">
                <div class="w-6 h-6 bg-emerald-500 text-white rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0 mt-0.5">
                    {{ $index + 1 }}
                </div>
                <p class="text-xs text-gray-700 dark:text-gray-200 leading-relaxed whitespace-pre-line flex-1">
                    {{ $aturan->isi_aturan }}
                </p>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Modal Tambah Aturan --}}
    <x-modal show="modalTambah" title="Tambah Aturan Kos Baru">
        <form action="{{ route('admin.aturan.store') }}" method="POST" class="space-y-3.5">
            @csrf

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Target Kos</label>
                <select name="kos_id" required class="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                    <option value="">-- Pilih Target Kos --</option>
                    <option value="all" class="font-bold text-emerald-600 dark:text-emerald-400">🌐 Semua Kos (Terapkan ke Seluruh Gedung Kos)</option>
                    @foreach($kosList as $k)
                    <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Isi Aturan Kos</label>
                <textarea name="isi_aturan" rows="4" required placeholder="Tuliskan aturan kos lengkap di sini..." class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500"></textarea>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <x-btn type="button" variant="secondary" size="sm" @click="modalTambah = false">Batal</x-btn>
                <x-btn type="submit" variant="primary" size="sm">Simpan Aturan</x-btn>
            </div>
        </form>
    </x-modal>

    {{-- Modal Edit Aturan --}}
    <x-modal show="modalEdit" title="Edit Aturan Kos">
        <form :action="editUrl" method="POST" class="space-y-3.5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Target Kos</label>
                <select name="kos_id" x-model="editData.kos_id" required class="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                    <option value="">-- Pilih Kos --</option>
                    @foreach($kosList as $k)
                    <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Isi Aturan Kos</label>
                <textarea name="isi_aturan" x-model="editData.isi_aturan" rows="4" required class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500"></textarea>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <x-btn type="button" variant="secondary" size="sm" @click="modalEdit = false">Batal</x-btn>
                <x-btn type="submit" variant="primary" size="sm">Update Aturan</x-btn>
            </div>
        </form>
    </x-modal>
</div>
@endsection