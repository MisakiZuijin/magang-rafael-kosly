@extends('layouts.app')

@section('title', 'Daftar Kos & Kamar')

@section('content')
<div class="space-y-4" x-data="{ 
    search: '', 
    selectedKos: 'semua', 
    statusFilter: 'semua',
    tipeFilter: 'semua',
    matchSearch(text) {
        if (!this.search) return true;
        return text.toLowerCase().includes(this.search.toLowerCase());
    }
}">
    {{-- Header --}}
    <x-page-header title="Kos & Kamar Kos" subtitle="Kelola dan lihat status kamar kos Anda" backUrl="{{ route('mitra.dashboard') }}" />

    {{-- Filter & Search Section --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        {{-- Search Input --}}
        <div class="relative">
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" 
                   x-model="search"
                   placeholder="Cari kode kamar atau nama penghuni..." 
                   class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all">
        </div>

        {{-- Filter Selects & Pills --}}
        <div class="grid grid-cols-3 gap-2">
            <div>
                <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Pilih Kos</label>
                <select x-model="selectedKos" class="w-full py-1.5 px-2.5 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-800 dark:text-gray-200 focus:ring-emerald-500">
                    <option value="semua">Semua Kos</option>
                    @foreach($kosList as $k)
                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Status</label>
                <select x-model="statusFilter" class="w-full py-1.5 px-2.5 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-800 dark:text-gray-200 focus:ring-emerald-500">
                    <option value="semua">Semua Status</option>
                    <option value="terisi">Terisi</option>
                    <option value="kosong">Kosong</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Jenis Kamar</label>
                <select x-model="tipeFilter" class="w-full py-1.5 px-2.5 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-800 dark:text-gray-200 focus:ring-emerald-500">
                    <option value="semua">Semua Jenis</option>
                    <option value="standar">Standar</option>
                    <option value="berbagi">Berbagi</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Data List --}}
    @if($kamarData->isEmpty())
        <x-empty-state message="Belum ada data kamar kos yang terdaftar." />
    @else
        <div class="space-y-3">
            @foreach($kamarData as $kamar)
                @php
                    $penghuniAktifList = $kamar->penghuniKamar ? $kamar->penghuniKamar->where('status', 'aktif') : collect();
                    $hasPenghuni = $penghuniAktifList->isNotEmpty();
                    $allPenghuniNames = $penghuniAktifList->map(fn($p) => $p->penghuni->nama ?? '')->implode(' ');
                @endphp

                <div x-show="
                        (selectedKos === 'semua' || selectedKos == '{{ $kamar->kos_id }}') &&
                        (statusFilter === 'semua' || statusFilter === '{{ $kamar->status }}') &&
                        (tipeFilter === 'semua' || tipeFilter === '{{ $kamar->tipe }}') &&
                        matchSearch(@js($kamar->kode_kamar . ' ' . $allPenghuniNames . ' ' . ($kamar->kos->nama ?? '')))
                     "
                     x-transition
                     class="bg-white dark:bg-gray-900 rounded-2xl border-l-4 {{ $hasPenghuni ? 'border-l-emerald-500 border-gray-200 dark:border-gray-800' : 'border-l-amber-400 border-gray-200 dark:border-gray-800' }} shadow-sm p-4 space-y-3.5 hover:shadow-md transition-all">
                    
                    {{-- Card Header --}}
                    <div class="flex items-start justify-between gap-2 border-b border-gray-100 dark:border-gray-800/80 pb-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-1 rounded-xl text-xs font-black font-mono bg-emerald-50 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800">
                                    🚪 Kamar {{ $kamar->kode_kamar }}
                                </span>
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider {{ $kamar->tipe === 'berbagi' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' }}">
                                    {{ $kamar->tipe === 'berbagi' ? '👥 Kamar Berbagi' : '👤 Kamar Standar' }}
                                </span>
                            </div>
                            <h3 class="font-bold text-sm text-gray-800 dark:text-gray-200 leading-tight">
                                {{ $kamar->kos->nama ?? 'Properti Kos' }}
                            </h3>
                        </div>

                        <span class="inline-flex items-center gap-1 px-3 py-1 text-[11px] font-bold rounded-full {{ $hasPenghuni ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $hasPenghuni ? 'bg-emerald-600 animate-pulse' : 'bg-amber-500' }}"></span>
                            {{ $hasPenghuni ? 'Terisi (' . $penghuniAktifList->count() . '/' . $kamar->kapasitas . ')' : 'Kosong' }}
                        </span>
                    </div>

                    {{-- Price & Capacity Grid Strip --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-gray-50/80 dark:bg-gray-800/40 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800/60 text-center">
                        <div class="p-1">
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Harga / Bulan</p>
                            <p class="text-xs font-bold font-mono text-gray-900 dark:text-white mt-0.5">
                                Rp {{ number_format($kamar->harga_per_bulan, 0, ',', '.') }}
                            </p>
                        </div>
                        <div class="p-1 border-l sm:border-l border-gray-200 dark:border-gray-700/60">
                            <p class="text-[10px] text-purple-600 dark:text-purple-400 uppercase font-bold tracking-wider">Harga / Minggu</p>
                            <p class="text-xs font-bold font-mono text-purple-700 dark:text-purple-300 mt-0.5">
                                {{ $kamar->harga_per_minggu ? 'Rp ' . number_format($kamar->harga_per_minggu, 0, ',', '.') : '-' }}
                            </p>
                        </div>
                        <div class="p-1 border-t sm:border-t-0 sm:border-l border-gray-200 dark:border-gray-700/60">
                            <p class="text-[10px] text-blue-600 dark:text-blue-400 uppercase font-bold tracking-wider">Harga / Hari</p>
                            <p class="text-xs font-bold font-mono text-blue-700 dark:text-blue-300 mt-0.5">
                                {{ $kamar->harga_per_hari ? 'Rp ' . number_format($kamar->harga_per_hari, 0, ',', '.') : '-' }}
                            </p>
                        </div>
                        <div class="p-1 border-t sm:border-t-0 border-l border-gray-200 dark:border-gray-700/60">
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Kapasitas</p>
                            <p class="text-xs font-bold font-mono text-gray-900 dark:text-white mt-0.5">
                                {{ $kamar->kapasitas }} Orang
                            </p>
                        </div>
                    </div>

                    {{-- Occupants Section --}}
                    @if($hasPenghuni)
                        <div class="space-y-2">
                            <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-1">
                                <span>👤 Penghuni Aktif Kamar ({{ $penghuniAktifList->count() }})</span>
                            </p>
                            @foreach($penghuniAktifList as $pk)
                                @php
                                    $pUser = $pk->penghuni;
                                @endphp
                                @if($pUser)
                                <div class="p-3 bg-gradient-to-r from-emerald-50/80 to-emerald-100/30 dark:from-emerald-950/30 dark:to-emerald-900/10 rounded-xl border border-emerald-200/70 dark:border-emerald-900/50 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="relative flex-shrink-0">
                                            <img src="{{ $pUser->foto_profile ? asset('storage/' . $pUser->foto_profile) : 'https://ui-avatars.com/api/?name=' . urlencode($pUser->nama) . '&background=10b981&color=fff' }}" 
                                                 class="w-10 h-10 rounded-full object-cover ring-2 ring-emerald-500/40 shadow-xs"
                                                 alt="{{ $pUser->nama }}">
                                            <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-white dark:border-gray-900 rounded-full"></span>
                                        </div>
                                        <div class="min-w-0 space-y-0.5">
                                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate flex items-center gap-1.5">
                                                <span>{{ $pUser->nama }}</span>
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-emerald-200/80 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200">
                                                    {{ ucfirst($pk->durasi) }}
                                                </span>
                                            </p>
                                            @if($pk->tanggal_masuk && $pk->tanggal_keluar)
                                                <p class="text-[10px] text-emerald-800 dark:text-emerald-300 font-mono font-medium flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    {{ $pk->tanggal_masuk->format('d M Y') }} s/d {{ $pk->tanggal_keluar->format('d M Y') }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    @if($pUser->no_hp)
                                        @php
                                            $phone = preg_replace('/[^0-9]/', '', $pUser->no_hp);
                                            if(str_starts_with($phone, '0')) {
                                                $phone = '62' . substr($phone, 1);
                                            }
                                            $waMessage = rawurlencode("Halo Kak {$pUser->nama}, pengingat dari Pemilik Kos {$kamar->kos->nama} mengenai Kamar {$kamar->kode_kamar}.");
                                        @endphp
                                        <a href="https://wa.me/{{ $phone }}?text={{ $waMessage }}" 
                                           target="_blank"
                                           class="flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs font-bold shadow-xs transition-all flex-shrink-0">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                            </svg>
                                            <span>WA</span>
                                        </a>
                                    @endif
                                </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="p-3 bg-amber-50/60 dark:bg-amber-950/20 rounded-xl border border-amber-200/60 dark:border-amber-900/40 text-center">
                            <p class="text-xs text-amber-800 dark:text-amber-300 font-semibold flex items-center justify-center gap-1.5">
                                🏠 Kamar ini sedang kosong &amp; siap dihuni (Tipe {{ ucfirst($kamar->tipe) }})
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
