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
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Kos & Kamar Kos</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kelola dan lihat status kamar kos Anda</p>
        </div>
        <x-btn href="{{ route('mitra.dashboard') }}" variant="secondary" size="sm" class="!min-h-[36px] !py-1 text-xs">
            &larr; Dashboard
        </x-btn>
    </div>

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
                    $penghuniAktif = $kamar->penghuniKamar ? $kamar->penghuniKamar->where('status', 'aktif')->first() : null;
                    $penghuniUser = $penghuniAktif ? $penghuniAktif->penghuni : null;
                    $penghuniNama = $penghuniUser ? strtolower($penghuniUser->nama) : '';
                    $kodeLower = strtolower($kamar->kode_kamar);
                    $kosNamaLower = strtolower($kamar->kos->nama ?? '');
                @endphp

                <div x-show="
                        (selectedKos === 'semua' || selectedKos == '{{ $kamar->kos_id }}') &&
                        (statusFilter === 'semua' || statusFilter === '{{ $kamar->status }}') &&
                        (tipeFilter === 'semua' || tipeFilter === '{{ $kamar->tipe }}') &&
                        matchSearch(@js($kamar->kode_kamar . ' ' . ($penghuniUser->nama ?? '') . ' ' . ($kamar->kos->nama ?? '')))
                     "
                     x-transition
                     class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-4 space-y-3">
                    
                    {{-- Card Header --}}
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-lg text-xs font-bold font-mono bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                    Kamar {{ $kamar->kode_kamar }}
                                </span>
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider {{ $kamar->tipe === 'berbagi' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' }}">
                                    Jenis: {{ ucfirst($kamar->tipe) }}
                                </span>
                            </div>
                            <h3 class="font-bold text-sm text-gray-900 dark:text-white mt-1">
                                {{ $kamar->kos->nama ?? 'Kos' }}
                            </h3>
                        </div>

                        <x-badge type="{{ $kamar->status === 'terisi' ? 'success' : 'warning' }}">
                            {{ ucfirst($kamar->status) }}
                        </x-badge>
                    </div>

                    {{-- Price & Capacity Strip --}}
                    <div class="grid grid-cols-3 gap-2 bg-gray-50 dark:bg-gray-800/40 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800/60 text-center">
                        <div>
                            <p class="text-[10px] text-gray-500 uppercase font-semibold">Harga / Bulan</p>
                            <p class="text-xs font-bold text-gray-900 dark:text-white mt-0.5">
                                Rp {{ number_format($kamar->harga_per_bulan, 0, ',', '.') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 uppercase font-semibold">Harga / Hari</p>
                            <p class="text-xs font-bold text-gray-900 dark:text-white mt-0.5">
                                {{ $kamar->harga_per_hari ? 'Rp ' . number_format($kamar->harga_per_hari, 0, ',', '.') : '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 uppercase font-semibold">Kapasitas</p>
                            <p class="text-xs font-bold text-gray-900 dark:text-white mt-0.5">
                                {{ $kamar->kapasitas }} Orang
                            </p>
                        </div>
                    </div>

                    {{-- Occupant Info Section --}}
                    @if($kamar->status === 'terisi' && $penghuniUser)
                        <div class="p-3 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-xl border border-emerald-100 dark:border-emerald-900/50 flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="{{ $penghuniUser->foto_profile ? asset('storage/' . $penghuniUser->foto_profile) : 'https://ui-avatars.com/api/?name=' . urlencode($penghuniUser->nama) . '&background=10b981&color=fff' }}" 
                                     class="w-9 h-9 rounded-full object-cover ring-2 ring-emerald-500/30 flex-shrink-0"
                                     alt="{{ $penghuniUser->nama }}">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $penghuniUser->nama }}</p>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 font-mono">
                                        Durasi: {{ ucfirst($penghuniAktif->durasi) }}
                                    </p>
                                    @if($penghuniAktif->tanggal_masuk && $penghuniAktif->tanggal_keluar)
                                        <p class="text-[10px] text-emerald-700 dark:text-emerald-400 font-medium">
                                            {{ $penghuniAktif->tanggal_masuk->format('d/m/Y') }} - {{ $penghuniAktif->tanggal_keluar->format('d/m/Y') }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if($penghuniUser->no_hp)
                                @php
                                    $phone = preg_replace('/[^0-9]/', '', $penghuniUser->no_hp);
                                    if(str_starts_with($phone, '0')) {
                                        $phone = '62' . substr($phone, 1);
                                    }
                                    $waMessage = rawurlencode("Halo Kak {$penghuniUser->nama}, pengingat dari Pemilik Kos {$kamar->kos->nama} mengenai Kamar {$kamar->kode_kamar}.");
                                @endphp
                                <a href="https://wa.me/{{ $phone }}?text={{ $waMessage }}" 
                                   target="_blank"
                                   class="flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm active:scale-95 transition-all flex-shrink-0">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                    </svg>
                                    <span>WA</span>
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="p-2.5 bg-amber-50/50 dark:bg-amber-950/20 rounded-xl border border-amber-100 dark:border-amber-900/40 text-center">
                            <p class="text-xs text-amber-700 dark:text-amber-300 font-semibold flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Kamar ini belum terisi (Tipe: {{ ucfirst($kamar->tipe) }})
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
