@extends('layouts.app')

@section('title', 'Kelola Aturan Kos')

@section('content')
@php
$isSuperAdmin = request()->is('superadmin*');
$prefix = $isSuperAdmin ? 'superadmin' : 'admin';
@endphp
<div class="space-y-5 pb-10" x-data="{ 
    modalTambah: false, 
    modalEdit: false, 
    filterKosId: 'all',
    search: '',
    tambahKosId: 'all',
    tambahKosNama: 'Semua Kos',
    editData: { id: '', kos_id: '', kos_nama: '', isi_aturan: '' },
    editUrl: '',
    openTambahModal(kosId = 'all', kosNama = 'Semua Kos') {
        this.tambahKosId = kosId;
        this.tambahKosNama = kosNama;
        this.modalTambah = true;
    },
    openEditModal(aturan, kosNama = '') {
        this.editData = {
            id: aturan.id,
            kos_id: aturan.kos_id,
            kos_nama: kosNama || (aturan.kos ? aturan.kos.nama : 'Kos Terkait'),
            isi_aturan: aturan.isi_aturan || ''
        };
        this.editUrl = '/{{ $prefix }}/aturan/' + aturan.id;
        this.modalEdit = true;
    },
    matchKos(kosId, kosNama, rules) {
        if (this.filterKosId !== 'all' && this.filterKosId != kosId) {
            return false;
        }
        if (!this.search) return true;
        const q = this.search.toLowerCase().trim();
        if (kosNama.toLowerCase().includes(q)) return true;
        return rules.some(r => (r.isi_aturan || '').toLowerCase().includes(q));
    },
    matchRule(ruleText) {
        if (!this.search) return true;
        const q = this.search.toLowerCase().trim();
        return (ruleText || '').toLowerCase().includes(q);
    }
}">
    {{-- Header --}}
    <x-page-header title="Kelola Aturan Kos" subtitle="Atur tata tertib sewa dikelompokkan per kos" backUrl="{{ route('dashboard') }}" />

    {{-- Top Action & Summary Bar --}}
    <div class="space-y-3">
        <button @click="openTambahModal('all', 'Semua Kos')" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-sm active:scale-95 transition-all text-xs flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Tambah Aturan Baru</span>
        </button>

        {{-- Filter & Search Card --}}
        @if(!$kosList->isEmpty())
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pb-1 border-b border-gray-100 dark:border-gray-800">
                <span class="inline-flex items-center gap-1.5 font-bold uppercase tracking-wider text-[10px]">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Filter & Pencarian</span>
                </span>
                <span class="font-mono text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ $kosList->count() }} Kos · {{ $aturans->count() }} Total Aturan
                </span>
            </div>

            {{-- Search Bar --}}
            <div class="relative">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text"
                    x-model="search"
                    placeholder="Cari isi aturan atau nama kos..."
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all">
            </div>

            {{-- Filter Kos Dropdown --}}
            <div>
                <label class="inline-flex items-center gap-1 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span>Pilih Kos:</span>
                </label>
                <select x-model="filterKosId" class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold text-gray-900 dark:text-white focus:ring-emerald-500">
                    <option value="all">-- Tampilkan Semua Kos ({{ $kosList->count() }}) --</option>
                    @foreach($kosList as $kItem)
                    <option value="{{ $kItem->id }}">{{ $kItem->nama }} ({{ $kItem->aturanKos->count() }} Aturan)</option>
                    @endforeach
                </select>
            </div>
        </div>
        @endif
    </div>

    {{-- Grouped Aturan per Kos --}}
    @if($kosList->isEmpty())
    <x-empty-state message="Belum ada kos terdaftar di sistem. Silakan tambahkan kos terlebih dahulu." />
    @else
    <div class="space-y-6">
        @foreach($kosList as $kos)
        @php
        $rulesData = $kos->aturanKos->map(function($r) {
        return ['id' => $r->id, 'kos_id' => $r->kos_id, 'isi_aturan' => $r->isi_aturan];
        })->values();
        @endphp
        <div x-show="matchKos('{{ $kos->id }}', '{{ addslashes($kos->nama) }}', @js($rulesData))"
            x-transition
            class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">

            {{-- Header Kos --}}
            <div class="p-4 sm:p-5 bg-gray-50/90 dark:bg-gray-800/60 border-b border-gray-200/80 dark:border-gray-800">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-100 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-400 flex items-center justify-center flex-shrink-0 shadow-2xs mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1 space-y-1">
                        <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white truncate">{{ $kos->nama }}</h3>
                        <p class="text-xs text-gray-400 truncate flex items-center gap-1">
                            <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="truncate">{{ $kos->alamat ?: 'Alamat belum diatur' }}</span>
                        </p>
                        <div class="pt-0.5">
                            <button type="button"
                                @click="openTambahModal('{{ $kos->id }}', '{{ addslashes($kos->nama) }}')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-2xs active:scale-95 transition-all"
                                title="Tambah Aturan Khusus Kos Ini">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Tambah Aturan Kos Ini</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Body: Daftar Butir Aturan Kos Ini --}}
            <div class="p-4 sm:p-5 space-y-3">
                @if($kos->aturanKos->isEmpty())
                <div class="text-center py-6 px-4 bg-gray-50/50 dark:bg-gray-800/20 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800 space-y-1.5">
                    <div class="w-9 h-9 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-400 mx-auto flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Belum ada aturan tata tertib untuk {{ $kos->nama }}.</p>
                </div>
                @else
                <div class="flex items-center justify-between pb-1 border-b border-gray-100 dark:border-gray-800 text-xs">
                    <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Daftar Aturan</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold font-mono bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        {{ $kos->aturanKos->count() }} Aturan
                    </span>
                </div>

                <div class="space-y-2.5">
                    @foreach($kos->aturanKos as $index => $aturan)
                    <div x-show="matchRule('{{ addslashes(trim(preg_replace('/\s+/', ' ', $aturan->isi_aturan))) }}')"
                        class="p-3 sm:p-3.5 bg-gray-50/70 dark:bg-gray-800/40 rounded-xl border border-gray-100 dark:border-gray-800 hover:border-emerald-200 dark:hover:border-emerald-800/50 transition-all space-y-2.5">

                        {{-- Isi Aturan & Nomor --}}
                        <div class="flex items-start gap-2.5">
                            <div class="w-6 h-6 rounded-lg bg-emerald-500 text-white flex items-center justify-center font-bold text-xs font-mono flex-shrink-0 shadow-2xs">
                                {{ $index + 1 }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-xs sm:text-sm text-gray-800 dark:text-gray-200 leading-snug whitespace-pre-line">
                                    {{ $aturan->isi_aturan }}
                                </p>
                            </div>
                        </div>

                        {{-- Footer Aksi & Waktu --}}
                        <div class="flex items-center justify-between pt-2 border-t border-gray-200/60 dark:border-gray-800/80 text-[11px]">
                            <span class="text-gray-400 font-mono text-[10px] flex items-center gap-1">
                                <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Diperbarui {{ $aturan->updated_at ? $aturan->updated_at->diffForHumans() : '-' }}</span>
                            </span>

                            <div class="flex items-center gap-2">
                                <button type="button"
                                    @click="openEditModal(@js($aturan), '{{ addslashes($kos->nama) }}')"
                                    class="px-2 py-0.5 text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-100/80 hover:bg-amber-200 dark:bg-amber-900/40 dark:hover:bg-amber-900/60 rounded-lg transition-all flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span>Edit</span>
                                </button>

                                <form action="{{ route($prefix . '.aturan.destroy', $aturan->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus butir aturan ini dari {{ addslashes($kos->nama) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-0.5 text-xs font-bold text-red-700 dark:text-red-300 bg-red-100/80 hover:bg-red-200 dark:bg-red-900/40 dark:hover:bg-red-900/60 rounded-lg transition-all flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Modal Tambah Aturan --}}
    <x-modal show="modalTambah" title="Tambah Aturan Kos">
        <form action="{{ route($prefix . '.aturan.store') }}" method="POST" class="space-y-4">
            @csrf

            {{-- Target Indicator & Hidden kos_id --}}
            <input type="hidden" name="kos_id" :value="tambahKosId">
            <div class="p-3.5 rounded-2xl border flex items-center gap-3 text-xs shadow-2xs"
                :class="tambahKosId === 'all' ? 'bg-blue-50/90 dark:bg-blue-950/40 border-blue-200 dark:border-blue-800 text-blue-900 dark:text-blue-200' : 'bg-emerald-50/90 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200'">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 shadow-2xs"
                    :class="tambahKosId === 'all' ? 'bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300' : 'bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300'">
                    <template x-if="tambahKosId === 'all'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                    </template>
                    <template x-if="tambahKosId !== 'all'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </template>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-[11px] uppercase tracking-wider" x-text="tambahKosId === 'all' ? 'Target: SEMUA KOS' : 'Target: KOS KHUSUS'"></p>
                    <p class="text-xs font-semibold mt-0.5 truncate" x-text="tambahKosId === 'all' ? 'Aturan ini akan otomatis diterapkan ke seluruh ({{ $kosList->count() }}) kos.' : tambahKosNama"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Isi Aturan / Tata Tertib <span class="text-red-500">*</span>
                </label>
                <textarea name="isi_aturan" rows="4" required placeholder="Contoh: Dilarang membawa hewan peliharaan ke dalam area kos..."
                    class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 leading-relaxed"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <x-btn type="button" variant="secondary" size="sm" @click="modalTambah = false">Batal</x-btn>
                <x-btn type="submit" variant="primary" size="sm">Simpan Aturan</x-btn>
            </div>
        </form>
    </x-modal>

    {{-- Modal Edit Aturan --}}
    <x-modal show="modalEdit" title="Edit Butir Aturan">
        <form :action="editUrl" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <input type="hidden" name="kos_id" :value="editData.kos_id">
            <div class="p-3 rounded-2xl border bg-gray-50 dark:bg-gray-800/60 border-gray-200 dark:border-gray-700 flex items-center gap-2.5 text-xs text-gray-700 dark:text-gray-300">
                <div class="w-7 h-7 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <span class="font-bold">Kos:</span>
                <span class="font-semibold text-emerald-600 dark:text-emerald-400" x-text="editData.kos_nama"></span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Isi Aturan / Tata Tertib <span class="text-red-500">*</span>
                </label>
                <textarea name="isi_aturan" x-model="editData.isi_aturan" rows="4" required
                    class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 leading-relaxed"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <x-btn type="button" variant="secondary" size="sm" @click="modalEdit = false">Batal</x-btn>
                <x-btn type="submit" variant="primary" size="sm">Simpan Perubahan</x-btn>
            </div>
        </form>
    </x-modal>
</div>
@endsection