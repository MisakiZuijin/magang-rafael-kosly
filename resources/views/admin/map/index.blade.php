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
        if (item.gmaps_query && typeof item.gmaps_query === 'string' && item.gmaps_query.trim() !== '') {
            return item.gmaps_query.trim();
        }
        if (item.link_gmaps && typeof item.link_gmaps === 'string' && item.link_gmaps.trim() !== '' && item.link_gmaps !== '-') {
            const raw = item.link_gmaps.trim();

            const matchPin = raw.match(/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/);
            if (matchPin) return `${matchPin[1]},${matchPin[2]}`;

            const matchQ = raw.match(/[?&]q=(?:loc:)?(-?\d+\.\d+,-?\d+\.\d+)/);
            if (matchQ) return matchQ[1];

            const matchPlace = raw.match(/\/place\/([^\/@?]+)/);
            if (matchPlace) return decodeURIComponent(matchPlace[1].replace(/\+/g, ' '));

            const matchAt = raw.match(/@(-?\d+\.\d+,-?\d+\.\d+)/);
            if (matchAt) return matchAt[1];
        }
        return item.alamat || item.nama || '';
    },

    get routeUrl() {
        if (!this.selectedKos) return '#';
        const destination = this.extractQuery(this.selectedKos);
        const origin = this.selectedOffice ? this.extractQuery(this.selectedOffice) : '';

        if (!destination) return '#';

        if (origin) {
            return `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}`;
        }
        
        if (this.selectedKos.link_gmaps && this.selectedKos.link_gmaps.trim() !== '' && this.selectedKos.link_gmaps !== '-') {
            return this.selectedKos.link_gmaps.trim();
        }
        return `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destination)}`;
    },

    get officeUrl() {
        if (!this.selectedOffice) return '#';
        if (this.selectedOffice.link_gmaps && this.selectedOffice.link_gmaps.trim() !== '' && this.selectedOffice.link_gmaps !== '-') {
            return this.selectedOffice.link_gmaps.trim();
        }
        const query = this.extractQuery(this.selectedOffice);
        return query ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}` : '#';
    },

    get kosUrl() {
        if (!this.selectedKos) return '#';
        if (this.selectedKos.link_gmaps && this.selectedKos.link_gmaps.trim() !== '' && this.selectedKos.link_gmaps !== '-') {
            return this.selectedKos.link_gmaps.trim();
        }
        const query = this.extractQuery(this.selectedKos);
        return query ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}` : '#';
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
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                <span>Rute</span>
            </button>
            <button type="button" @click="mode = 'kantor'"
                class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                :class="mode === 'kantor' ? 'bg-white dark:bg-gray-900 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>Titik Kantor</span>
            </button>
            <button type="button" @click="mode = 'kos'"
                class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                :class="mode === 'kos' ? 'bg-white dark:bg-gray-900 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>Titik Kos</span>
            </button>
        </div>

        {{-- Mode 1: Rute Navigasi Kantor -> Kos --}}
        <div x-show="mode === 'rute'" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {{-- Input Ketik Kantor --}}
                <div class="relative">
                    <label class="flex items-center gap-2 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">
                        <span class="w-4 h-4 rounded-full bg-blue-100 dark:bg-blue-900/60 text-blue-600 dark:text-blue-400 text-[10px] font-black inline-flex items-center justify-center flex-shrink-0">A</span>
                        <div class="flex flex-col">
                            <span class="text-gray-700 dark:text-gray-300 font-bold leading-tight">Titik Asal: Kantor Admin</span>
                            <span class="text-[10px] text-gray-400 font-normal leading-tight">Ketik / pilih</span>
                        </div>
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
                                        <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span x-text="item.nama"></span>
                                    </div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate pl-5" x-text="item.alamat"></div>
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
                    <label class="flex items-center gap-2 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">
                        <span class="w-4 h-4 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-600 dark:text-emerald-400 text-[10px] font-black inline-flex items-center justify-center flex-shrink-0">B</span>
                        <div class="flex flex-col">
                            <span class="text-gray-700 dark:text-gray-300 font-bold leading-tight">Titik Tujuan: Kos</span>
                            <span class="text-[10px] text-gray-400 font-normal leading-tight">Ketik / pilih</span>
                        </div>
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
                                        <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        <span x-text="item.nama"></span>
                                    </div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate pl-5" x-text="item.alamat"></div>
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
                    <div class="p-2.5 bg-white/80 dark:bg-gray-900/80 rounded-xl border border-blue-100 dark:border-blue-900/30 space-y-1">
                        <div class="flex items-center gap-1.5 text-blue-600 dark:text-blue-400">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span class="text-[10px] font-bold uppercase tracking-wider">Titik Asal (Kantor)</span>
                        </div>
                        <p class="font-bold text-gray-900 dark:text-white truncate text-xs" x-text="selectedOffice ? selectedOffice.nama : '-'"></p>
                        <p class="text-[11px] text-gray-500 truncate" x-text="selectedOffice ? (selectedOffice.alamat || 'Alamat tidak tersedia') : ''"></p>
                        <template x-if="selectedOffice && selectedOffice.link_gmaps">
                            <a :href="selectedOffice.link_gmaps" target="_blank" class="inline-flex items-center gap-1 text-[10px] text-blue-600 dark:text-blue-400 font-semibold hover:underline pt-0.5">
                                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Buka Titik di Maps</span>
                            </a>
                        </template>
                    </div>
                    <div class="p-2.5 bg-white/80 dark:bg-gray-900/80 rounded-xl border border-emerald-100 dark:border-emerald-900/30 space-y-1">
                        <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="text-[10px] font-bold uppercase tracking-wider">Titik Tujuan (Kos)</span>
                        </div>
                        <p class="font-bold text-gray-900 dark:text-white truncate text-xs" x-text="selectedKos ? selectedKos.nama : '-'"></p>
                        <p class="text-[11px] text-gray-500 truncate" x-text="selectedKos ? (selectedKos.alamat || 'Alamat tidak tersedia') : ''"></p>
                        <template x-if="selectedKos && selectedKos.link_gmaps">
                            <a :href="selectedKos.link_gmaps" target="_blank" class="inline-flex items-center gap-1 text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold hover:underline pt-0.5">
                                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Buka Titik di Maps</span>
                            </a>
                        </template>
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
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>Pilih / Ketik Kantor Admin</span>
                    </span>
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
                                    <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span x-text="item.nama"></span>
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate pl-5" x-text="item.alamat"></div>
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
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span x-text="selectedOffice ? selectedOffice.nama : 'Pilih Kantor'"></span>
                    </p>
                    <p class="text-[11px] text-gray-600 dark:text-gray-400" x-text="selectedOffice ? (selectedOffice.alamat || 'Alamat tidak tersedia') : ''"></p>
                    <template x-if="selectedOffice && selectedOffice.no_telp">
                        <div class="flex items-center gap-1.5 text-[11px] text-gray-500 font-mono pt-0.5">
                            <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span x-text="selectedOffice.no_telp"></span>
                        </div>
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
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Pilih / Ketik Lokasi Kos</span>
                    </span>
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
                                    <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                    <span x-text="item.nama"></span>
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate pl-5" x-text="item.alamat"></div>
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
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
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
                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>Daftar Titik Kantor Admin ({{ $kantors->count() }})</span>
            </h2>
        </div>

        <div class="space-y-2.5">
            @forelse($kantors as $kan)
            <div class="p-3 bg-gray-50/80 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 border border-blue-200/70 dark:border-blue-800/60 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="min-w-0 space-y-0.5">
                        <h3 class="font-bold text-xs text-gray-900 dark:text-white truncate">{{ $kan->nama }}</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ $kan->alamat ?? 'Alamat belum diatur' }}</p>
                        @if(!empty($kan->no_telp))
                        <div class="flex items-center gap-1 text-[11px] text-gray-600 dark:text-gray-300 font-mono pt-0.5">
                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span>{{ $kan->no_telp }}</span>
                        </div>
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
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>Daftar Titik Kos & Kontak Mitra ({{ $locations->count() }})</span>
            </h2>
        </div>

        <div class="space-y-2.5">
            @forelse($locations as $loc)
            <div class="p-3 bg-gray-50/80 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200/70 dark:border-emerald-800/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <div class="min-w-0 space-y-0.5">
                        <h3 class="font-bold text-xs text-gray-900 dark:text-white truncate">{{ $loc->nama }}</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ $loc->alamat ?? 'Alamat belum diatur' }}</p>
                        <div class="flex flex-wrap items-center gap-2 pt-0.5 text-[11px] text-gray-600 dark:text-gray-300">
                            <span class="font-semibold text-emerald-700 dark:text-emerald-400">Mitra: {{ $loc->mitra->nama ?? '-' }}</span>
                            @if(!empty($loc->mitra->no_hp))
                            <span class="text-gray-400">•</span>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $loc->mitra->no_hp) }}" target="_blank" class="font-mono text-emerald-600 dark:text-emerald-400 hover:underline inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span>{{ $loc->mitra->no_hp }}</span>
                            </a>
                            @else
                            <span class="text-gray-400">• Telepon tidak tersedia</span>
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