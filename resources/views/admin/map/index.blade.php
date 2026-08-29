@extends('layouts.app')

@section('title', 'Lokasi Kos & Kantor')

@section('content')
<div class="space-y-4" x-data="{ 
    mode: 'rute', // 'rute', 'kantor', 'kos'
    selectedOfficeId: '{{ $kantors->first()->id ?? '' }}', 
    selectedKosId: '{{ $locations->first()->id ?? '' }}',
    searchKantor: '{{ $kantors->first()->nama ?? '' }}',
    searchKos: '{{ $locations->first()->nama ?? '' }}',
    openKantor: false,
    openKos: false,
    kantors: @js($kantors),
    locations: @js($locations),
    
    get filteredKantors() {
        if (!this.searchKantor) return this.kantors;
        const q = this.searchKantor.toLowerCase().trim();
        return this.kantors.filter(k => 
            (k.nama && k.nama.toLowerCase().includes(q)) || 
            (k.alamat && k.alamat.toLowerCase().includes(q))
        );
    },

    get filteredKos() {
        if (!this.searchKos) return this.locations;
        const q = this.searchKos.toLowerCase().trim();
        return this.locations.filter(k => 
            (k.nama && k.nama.toLowerCase().includes(q)) || 
            (k.alamat && k.alamat.toLowerCase().includes(q)) ||
            (k.mitra && k.mitra.nama && k.mitra.nama.toLowerCase().includes(q))
        );
    },

    selectKantor(item) {
        this.selectedOfficeId = item.id;
        this.searchKantor = item.nama;
        this.openKantor = false;
    },

    selectKos(item) {
        this.selectedKosId = item.id;
        this.searchKos = item.nama;
        this.openKos = false;
    },
    
    get selectedOffice() {
        return this.kantors.find(k => k.id == this.selectedOfficeId) || null;
    },
    get selectedKos() {
        return this.locations.find(k => k.id == this.selectedKosId) || null;
    },
    
    extractQuery(item) {
        if (!item) return '';
        if (item.link_gmaps) {
            const matchQ = item.link_gmaps.match(/[?&]q=([^&]+)/);
            if (matchQ) return decodeURIComponent(matchQ[1]);
            const matchAt = item.link_gmaps.match(/@(-?\d+\.\d+,-?\d+\.\d+)/);
            if (matchAt) return matchAt[1];
        }
        return item.alamat || item.nama || '';
    },

    get routeUrl() {
        if (!this.selectedOffice || !this.selectedKos) return '#';
        const origin = this.extractQuery(this.selectedOffice);
        const destination = this.extractQuery(this.selectedKos);
        if (!origin || !destination) return '#';
        return `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}`;
    },

    get officeUrl() {
        return this.selectedOffice?.link_gmaps || (this.selectedOffice ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(this.extractQuery(this.selectedOffice))}` : '#');
    },

    get kosUrl() {
        return this.selectedKos?.link_gmaps || (this.selectedKos ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(this.extractQuery(this.selectedKos))}` : '#');
    }
}">
    {{-- Header --}}
    <x-page-header title="Lokasi Kos & Kantor" subtitle="Navigasi rute dan titik lokasi Google Maps untuk kos dan kantor admin" backUrl="{{ route('dashboard') }}" />

    {{-- Panel Kontrol Mode & Peta --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-4">
        {{-- Pilihan Mode Tampilan --}}
        <div class="flex items-center gap-1.5 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
            <button type="button" @click="mode = 'rute'"
                class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                :class="mode === 'rute' ? 'bg-white dark:bg-gray-900 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'">
                <span>🚗</span>
                <span>Rute</span>
            </button>
            <button type="button" @click="mode = 'kantor'"
                class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                :class="mode === 'kantor' ? 'bg-white dark:bg-gray-900 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'">
                <span>🏢</span>
                <span>Titik Kantor</span>
            </button>
            <button type="button" @click="mode = 'kos'"
                class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                :class="mode === 'kos' ? 'bg-white dark:bg-gray-900 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'">
                <span>🏡</span>
                <span>Titik Kos</span>
            </button>
        </div>

        {{-- Mode 1: Rute Navigasi Kantor -> Kos --}}
        <div x-show="mode === 'rute'" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {{-- Input Ketik Kantor --}}
                <div class="relative">
                    <label class="grid grid-cols-1 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5 items-center">
                        <span class="items-center gap-1">
                            <span class="text-blue-500 font-bold">🅰️</span>
                            <span>Titik Asal: Kantor Admin</span>
                        </span>
                        <span class="text-[10px] text-gray-400 font-normal text-center">Ketik / pilih</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                            x-model="searchKantor"
                            @focus="openKantor = true"
                            @click.away="openKantor = false"
                            @input="openKantor = true"
                            placeholder="Ketik nama atau alamat kantor..."
                            class="w-full py-2 pl-3 pr-8 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <button type="button" @click="openKantor = !openKantor" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>

                    {{-- Dropdown Popover Suggestions --}}
                    <div x-show="openKantor && filteredKantors.length > 0"
                        x-transition
                        class="absolute left-0 right-0 top-full mt-1 max-h-52 overflow-y-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl z-50 p-1 space-y-1">
                        <template x-for="item in filteredKantors" :key="item.id">
                            <div @click="selectKantor(item)"
                                class="p-2 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-lg cursor-pointer text-xs flex justify-between items-center transition-all"
                                :class="selectedOfficeId == item.id ? 'bg-blue-50/80 dark:bg-blue-950/60 font-bold' : ''">
                                <div class="min-w-0 pr-2">
                                    <div class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5 truncate">
                                        <span>🏢</span>
                                        <span x-text="item.nama"></span>
                                    </div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate" x-text="item.alamat"></div>
                                </div>
                                <template x-if="selectedOfficeId == item.id">
                                    <span class="text-blue-600 dark:text-blue-400 text-xs font-bold">✓</span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Input Ketik Kos --}}
                <div class="relative">
                    <label class="grid grid-cols-1 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5 items-center">
                        <span class="items-center gap-1">
                            <span class="text-emerald-500 font-bold">🅱️</span>
                            <span>Titik Tujuan: Kos</span>
                        </span>
                        <span class="text-[10px] text-gray-400 font-normal text-center">Ketik / pilih</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                            x-model="searchKos"
                            @focus="openKos = true"
                            @click.away="openKos = false"
                            @input="openKos = true"
                            placeholder="Ketik nama kos, alamat, atau mitra..."
                            class="w-full py-2 pl-3 pr-8 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        <button type="button" @click="openKos = !openKos" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>

                    {{-- Dropdown Popover Suggestions --}}
                    <div x-show="openKos && filteredKos.length > 0"
                        x-transition
                        class="absolute left-0 right-0 top-full mt-1 max-h-52 overflow-y-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl z-50 p-1 space-y-1">
                        <template x-for="item in filteredKos" :key="item.id">
                            <div @click="selectKos(item)"
                                class="p-2 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 rounded-lg cursor-pointer text-xs flex justify-between items-center transition-all"
                                :class="selectedKosId == item.id ? 'bg-emerald-50/80 dark:bg-emerald-950/60 font-bold' : ''">
                                <div class="min-w-0 pr-2">
                                    <div class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5 truncate">
                                        <span>🏡</span>
                                        <span x-text="item.nama"></span>
                                    </div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate" x-text="item.alamat"></div>
                                </div>
                                <template x-if="selectedKosId == item.id">
                                    <span class="text-emerald-600 dark:text-emerald-400 text-xs font-bold">✓</span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Banner Preview Rute --}}
            <div class="p-3.5 bg-emerald-50/70 dark:bg-emerald-950/40 rounded-xl border border-emerald-100 dark:border-emerald-900/50 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <div class="p-2 bg-white/80 dark:bg-gray-900/80 rounded-lg border border-emerald-100 dark:border-emerald-900/30 space-y-0.5">
                        <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider block">🏢 Titik Asal (Kantor)</span>
                        <p class="font-bold text-gray-900 dark:text-white truncate" x-text="selectedOffice ? selectedOffice.nama : '-'"></p>
                        <p class="text-[11px] text-gray-500 truncate" x-text="selectedOffice ? (selectedOffice.alamat || 'Alamat tidak tersedia') : ''"></p>
                    </div>
                    <div class="p-2 bg-white/80 dark:bg-gray-900/80 rounded-lg border border-emerald-100 dark:border-emerald-900/30 space-y-0.5">
                        <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">🏡 Titik Tujuan (Kos)</span>
                        <p class="font-bold text-gray-900 dark:text-white truncate" x-text="selectedKos ? selectedKos.nama : '-'"></p>
                        <p class="text-[11px] text-gray-500 truncate" x-text="selectedKos ? (selectedKos.alamat || 'Alamat tidak tersedia') : ''"></p>
                    </div>
                </div>

                <a :href="routeUrl" target="_blank"
                    class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm active:scale-95 transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span>Buka Rute Navigasi di Google Maps</span>
                </a>
            </div>
        </div>

        {{-- Mode 2: Titik Lokasi Kantor --}}
        <div x-show="mode === 'kantor'" class="space-y-3" x-cloak>
            <div class="relative">
                <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                    <span>Pilih / Ketik Kantor Admin</span>
                    <span class="text-[10px] text-gray-400 font-normal">Ketik untuk mencari</span>
                </label>
                <div class="relative">
                    <input type="text"
                        x-model="searchKantor"
                        @focus="openKantor = true"
                        @click.away="openKantor = false"
                        @input="openKantor = true"
                        placeholder="Ketik nama atau alamat kantor..."
                        class="w-full py-2 pl-3 pr-8 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <button type="button" @click="openKantor = !openKantor" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                {{-- Dropdown Popover Suggestions --}}
                <div x-show="openKantor && filteredKantors.length > 0"
                    x-transition
                    class="absolute left-0 right-0 top-full mt-1 max-h-52 overflow-y-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl z-50 p-1 space-y-1">
                    <template x-for="item in filteredKantors" :key="item.id">
                        <div @click="selectKantor(item)"
                            class="p-2 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-lg cursor-pointer text-xs flex justify-between items-center transition-all"
                            :class="selectedOfficeId == item.id ? 'bg-blue-50/80 dark:bg-blue-950/60 font-bold' : ''">
                            <div class="min-w-0 pr-2">
                                <div class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5 truncate">
                                    <span>🏢</span>
                                    <span x-text="item.nama"></span>
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate" x-text="item.alamat"></div>
                            </div>
                            <template x-if="selectedOfficeId == item.id">
                                <span class="text-blue-600 dark:text-blue-400 text-xs font-bold">✓</span>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div class="p-3.5 bg-blue-50/70 dark:bg-blue-950/40 rounded-xl border border-blue-100 dark:border-blue-900/50 space-y-3">
                <div class="space-y-1">
                    <p class="text-xs font-bold text-blue-900 dark:text-blue-200 flex items-center gap-1.5">
                        <span>🏢</span>
                        <span x-text="selectedOffice ? selectedOffice.nama : 'Pilih Kantor'"></span>
                    </p>
                    <p class="text-[11px] text-gray-600 dark:text-gray-400" x-text="selectedOffice ? (selectedOffice.alamat || 'Alamat tidak tersedia') : ''"></p>
                    <template x-if="selectedOffice && selectedOffice.no_telp">
                        <p class="text-[11px] text-gray-500 font-mono" x-text="'📞 ' + selectedOffice.no_telp"></p>
                    </template>
                </div>

                <a :href="officeUrl" target="_blank"
                    class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-sm active:scale-95 transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Buka Titik Lokasi Kantor di Google Maps</span>
                </a>
            </div>
        </div>

        {{-- Mode 3: Titik Lokasi Kos --}}
        <div x-show="mode === 'kos'" class="space-y-3" x-cloak>
            <div class="relative">
                <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                    <span>Pilih / Ketik Lokasi Kos</span>
                    <span class="text-[10px] text-gray-400 font-normal">Ketik untuk mencari</span>
                </label>
                <div class="relative">
                    <input type="text"
                        x-model="searchKos"
                        @focus="openKos = true"
                        @click.away="openKos = false"
                        @input="openKos = true"
                        placeholder="Ketik nama kos, alamat, atau mitra..."
                        class="w-full py-2 pl-3 pr-8 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    <button type="button" @click="openKos = !openKos" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                {{-- Dropdown Popover Suggestions --}}
                <div x-show="openKos && filteredKos.length > 0"
                    x-transition
                    class="absolute left-0 right-0 top-full mt-1 max-h-52 overflow-y-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl z-50 p-1 space-y-1">
                    <template x-for="item in filteredKos" :key="item.id">
                        <div @click="selectKos(item)"
                            class="p-2 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 rounded-lg cursor-pointer text-xs flex justify-between items-center transition-all"
                            :class="selectedKosId == item.id ? 'bg-emerald-50/80 dark:bg-emerald-950/60 font-bold' : ''">
                            <div class="min-w-0 pr-2">
                                <div class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5 truncate">
                                    <span>🏡</span>
                                    <span x-text="item.nama"></span>
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate" x-text="item.alamat"></div>
                            </div>
                            <template x-if="selectedKosId == item.id">
                                <span class="text-emerald-600 dark:text-emerald-400 text-xs font-bold">✓</span>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div class="p-3.5 bg-emerald-50/70 dark:bg-emerald-950/40 rounded-xl border border-emerald-100 dark:border-emerald-900/50 space-y-3">
                <div class="space-y-1">
                    <p class="text-xs font-bold text-emerald-900 dark:text-emerald-200 flex items-center gap-1.5">
                        <span>🏡</span>
                        <span x-text="selectedKos ? selectedKos.nama : 'Pilih Kos'"></span>
                    </p>
                    <p class="text-[11px] text-gray-600 dark:text-gray-400" x-text="selectedKos ? (selectedKos.alamat || 'Alamat tidak tersedia') : ''"></p>
                    <template x-if="selectedKos && selectedKos.mitra">
                        <p class="text-[11px] text-gray-500" x-text="'Mitra Pengelola: ' + (selectedKos.mitra.nama || '-') + ' (' + (selectedKos.mitra.no_hp || '-') + ')'"></p>
                    </template>
                </div>

                <a :href="kosUrl" target="_blank"
                    class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm active:scale-95 transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Buka Titik Lokasi Kos di Google Maps</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Daftar Lokasi Kantor Admin --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2.5">
            <h2 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                <span class="text-base">🏢</span>
                <span>Daftar Titik Kantor Admin ({{ $kantors->count() }})</span>
            </h2>
        </div>

        <div class="space-y-2.5">
            @forelse($kantors as $kan)
            <div class="p-3 bg-gray-50/80 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-lg flex-shrink-0">
                        🏢
                    </div>
                    <div class="min-w-0 space-y-0.5">
                        <h3 class="font-bold text-xs text-gray-900 dark:text-white truncate">{{ $kan->nama }}</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ $kan->alamat ?? 'Alamat belum diatur' }}</p>
                        @if(!empty($kan->no_telp))
                        <p class="text-[11px] text-gray-600 dark:text-gray-300 font-mono">📞 {{ $kan->no_telp }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-center flex-shrink-0">
                    @if($kan->link_gmaps)
                    <a href="{{ $kan->link_gmaps }}" target="_blank"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-1.5 active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span>Buka Map Kantor</span>
                    </a>
                    @else
                    <span class="px-3 py-1.5 bg-gray-200 dark:bg-gray-800 text-gray-400 rounded-xl text-xs font-semibold">Map Tidak Ada</span>
                    @endif
                </div>
            </div>
            @empty
            <x-empty-state message="Belum ada data lokasi kantor terdaftar." />
            @endforelse
        </div>
    </div>

    {{-- Daftar Kos & Kontak Mitra --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2.5">
            <h2 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                <span class="text-base">🏡</span>
                <span>Daftar Titik Kos & Kontak Mitra ({{ $locations->count() }})</span>
            </h2>
        </div>

        <div class="space-y-2.5">
            @forelse($locations as $loc)
            <div class="p-3 bg-gray-50/80 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold text-lg flex-shrink-0">
                        🏡
                    </div>
                    <div class="min-w-0 space-y-0.5">
                        <h3 class="font-bold text-xs text-gray-900 dark:text-white truncate">{{ $loc->nama }}</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ $loc->alamat ?? 'Alamat belum diatur' }}</p>
                        <div class="flex flex-wrap items-center gap-2 pt-0.5 text-[11px] text-gray-600 dark:text-gray-300">
                            <span class="font-semibold text-emerald-700 dark:text-emerald-400">Mitra: {{ $loc->mitra->nama ?? '-' }}</span>
                            @if(!empty($loc->mitra->no_hp))
                            <span class="text-gray-400">•</span>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $loc->mitra->no_hp) }}" target="_blank" class="font-mono text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1">
                                📞 {{ $loc->mitra->no_hp }}
                            </a>
                            @else
                            <span class="text-gray-400">• 📞 Telepon tidak tersedia</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-center flex-shrink-0">
                    @if($loc->link_gmaps)
                    <a href="{{ $loc->link_gmaps }}" target="_blank"
                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-1.5 active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span>Buka Map Kos</span>
                    </a>
                    @else
                    <span class="px-3 py-1.5 bg-gray-200 dark:bg-gray-800 text-gray-400 rounded-xl text-xs font-semibold">
                        Map Tidak Ada
                    </span>
                    @endif
                </div>
            </div>
            @empty
            <x-empty-state message="Belum ada data kos terdaftar." />
            @endforelse
        </div>
    </div>
</div>
@endsection