@extends('layouts.app')

@php
$initTargetTipe = (!empty($selectedKamarIds) || isset($selectedKamarId)) ? 'kamar' : 'semua';
$initJudul = old('judul');
$initIsi = old('isi');

if (!$initJudul) {
    if (isset($prefilledKamar)) {
        $initJudul = "Pengingat Pembayaran Sewa Kamar " . $prefilledKamar->kode_kamar;
    } elseif (!empty($selectedKamarIds)) {
        $initJudul = "Penting: Pengingat Pembayaran Sewa Jatuh Tempo";
    }
}

if (!$initIsi) {
    if (isset($prefilledKamar)) {
        $initIsi = "Halo Penghuni Kamar " . $prefilledKamar->kode_kamar . " (" . ($prefilledKamar->kos->nama ?? 'Kos') . "),\n\nIni adalah pengingat bahwa masa sewa kamar Anda telah mendekati / melewati tanggal jatuh tempo. Mohon segera melakukan konfirmasi pembayaran sewa melalui aplikasi. Terima kasih!";
    } elseif (!empty($selectedKamarIds)) {
        $initIsi = "Halo Penghuni Kos,\n\nPemberitahuan penting bagi Anda yang mendapati pembayaran sewa telah mendekati / melewati tanggal jatuh tempo. Mohon untuk segera melakukan perpanjangan sewa dan unggah bukti transfer pembayaran melalui menu Pembayaran di aplikasi.\n\nJika ada kendala pembayaran, silakan hubungi pemilik kos. Terima kasih!";
    }
}
@endphp

@section('title', 'Buat Pengumuman Baru - Mitra Pro')

@section('content')
<div class="space-y-4" x-data="{ 
    channel: 'web',
    kategoriTipe: 'pembayaran', 
    targetTipe: '{{ $initTargetTipe }}', 
    searchKos: '',
    searchKamar: '',
    onChannelChange() {
        if ((this.channel === 'whatsapp' || this.channel === 'keduanya') && this.targetTipe === 'semua') {
            this.targetTipe = 'kos';
        }
    },
    onKategoriChange() {
        if (this.kategoriTipe === 'aturan' && this.targetTipe === 'kamar') {
            this.targetTipe = (this.channel === 'whatsapp' || this.channel === 'keduanya') ? 'kos' : 'semua';
        }
    }
}">
    <x-page-header
        title="Form Pengumuman"
        subtitle="Kirim pengumuman jatuh tempo atau aturan ke penghuni kos Anda"
        backUrl="{{ route('mitra.pengumuman.index') }}" />

    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm">
        <form action="{{ route('mitra.pengumuman.store') }}" method="POST" class="space-y-3.5">
            @csrf

            {{-- Jenis Pengumuman --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Jenis / Kategori Pengumuman</label>
                <select name="tipe" x-model="kategoriTipe" @change="onKategoriChange()" required class="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                    <option value="pembayaran">Jatuh Tempo Pembayaran</option>
                    <option value="aturan">Aturan Kos Baru</option>
                    <option value="info">Informasi Umum</option>
                </select>
                @error('tipe') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Saluran Media Pengiriman --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Saluran Media Pengiriman</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                        <input type="radio" name="channel" value="web" x-model="channel" @change="onChannelChange()" class="sr-only peer">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400 flex items-center gap-1.5">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            <span>Web App</span>
                        </span>
                    </label>

                    <label class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                        <input type="radio" name="channel" value="whatsapp" x-model="channel" @change="onChannelChange()" class="sr-only peer">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400 flex items-center gap-1.5">
                            <svg class="w-4 h-4 fill-current flex-shrink-0" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                                <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                            </svg>
                            <span>WhatsApp</span>
                        </span>
                    </label>

                    <label class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                        <input type="radio" name="channel" value="keduanya" x-model="channel" @change="onChannelChange()" class="sr-only peer">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400 flex items-center gap-1.5">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span>Keduanya</span>
                        </span>
                    </label>
                </div>

                {{-- Peringatan Status WhatsApp Gateway --}}
                <div x-show="channel === 'whatsapp' || channel === 'keduanya'" x-transition class="mt-2.5">
                    @if(!empty($waStatus['connected']))
                    <div class="p-2.5 bg-emerald-50/80 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 rounded-xl text-xs text-emerald-800 dark:text-emerald-300 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse flex-shrink-0"></span>
                            <span class="text-[11px] font-semibold">WhatsApp Gateway <strong>Terhubung</strong> ({{ $waStatus['device'] ?? 'Device Aktif' }})</span>
                        </div>
                    </div>
                    @else
                    <div class="p-2.5 bg-amber-50/90 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-xl text-xs text-amber-800 dark:text-amber-300 flex items-start justify-between gap-2">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div class="text-[11px] leading-relaxed">
                                <span class="font-bold">Perhatian:</span> WhatsApp Gateway pribadi Anda saat ini <strong>{{ $waStatus['status_text'] ?? 'Belum Terhubung / Token Kosong' }}</strong>. Pesan WhatsApp tidak akan terkirim ke penghuni jika gateway offline.
                            </div>
                        </div>
                        <a href="{{ route('mitra.whatsapp.index') }}" class="flex-shrink-0 text-[10px] font-bold px-2 py-1 bg-amber-200/70 hover:bg-amber-300 dark:bg-amber-900/60 dark:hover:bg-amber-800 text-amber-900 dark:text-amber-200 rounded-lg transition-colors">
                            Atur &rarr;
                        </a>
                    </div>
                    @endif
                </div>
                @error('channel') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Target Penerima --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Target Sasaran Penerima</label>
                <div class="grid grid-cols-3 gap-2 mb-2">
                    <label x-show="channel === 'web'" class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                        <input type="radio" name="target_tipe" value="semua" x-model="targetTipe" class="sr-only peer">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400 flex items-center justify-center gap-1.5 text-center">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span>Semua Kos</span>
                        </span>
                    </label>

                    <label class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                        :class="channel !== 'web' ? 'col-span-1 sm:col-span-1' : ''">
                        <input type="radio" name="target_tipe" value="kos" x-model="targetTipe" class="sr-only peer">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400 flex items-center justify-center gap-1.5 text-center">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Per Kos</span>
                        </span>
                    </label>

                    <label x-show="kategoriTipe !== 'aturan'" class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                        :class="channel !== 'web' ? 'col-span-1 sm:col-span-1' : ''">
                        <input type="radio" name="target_tipe" value="kamar" x-model="targetTipe" class="sr-only peer">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400 flex items-center justify-center gap-1.5 text-center">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span>Per Kamar</span>
                        </span>
                    </label>
                </div>

                {{-- Pilihan Kos --}}
                <div x-show="targetTipe === 'kos'" class="space-y-2 pt-1">
                    <div class="relative">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" x-model="searchKos" placeholder="Cari nama kos..."
                            class="w-full pl-9 pr-3 py-1.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div class="max-h-40 overflow-y-auto space-y-1.5 p-2 bg-gray-50 dark:bg-gray-800/40 rounded-xl border border-gray-100 dark:border-gray-800">
                        @foreach($kosList as $k)
                        <label x-show="!searchKos || '{{ strtolower($k->nama) }}'.includes(searchKos.toLowerCase())"
                            class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1.5 rounded-lg hover:bg-white dark:hover:bg-gray-800">
                            <input type="checkbox" name="target_ids[]" value="{{ $k->id }}" class="rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="font-semibold">{{ $k->nama }}</span>
                            <span class="text-[10px] text-gray-400">({{ $k->kamar->count() }} kamar)</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Pilihan Kamar --}}
                <div x-show="targetTipe === 'kamar' && kategoriTipe !== 'aturan'" class="space-y-2 pt-1">
                    <div class="relative">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" x-model="searchKamar" placeholder="Cari kode kamar atau nama kos..."
                            class="w-full pl-9 pr-3 py-1.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div class="max-h-48 overflow-y-auto space-y-1.5 p-2 bg-gray-50 dark:bg-gray-800/40 rounded-xl border border-gray-100 dark:border-gray-800">
                        @foreach($kosList as $k)
                            @foreach($k->kamar as $km)
                            <label x-show="!searchKamar || '{{ strtolower($km->kode_kamar . ' ' . $k->nama) }}'.includes(searchKamar.toLowerCase())"
                                class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1.5 rounded-lg hover:bg-white dark:hover:bg-gray-800">
                                <input type="checkbox" name="target_ids[]" value="{{ $km->id }}"
                                    {{ (isset($selectedKamarId) && $selectedKamarId == $km->id) || (!empty($selectedKamarIds) && in_array($km->id, $selectedKamarIds)) ? 'checked' : '' }}
                                    class="rounded text-emerald-600 focus:ring-emerald-500">
                                <span class="font-bold text-gray-900 dark:text-white">Kamar {{ $km->kode_kamar }}</span>
                                <span class="text-[10px] text-gray-400">· {{ $k->nama }}</span>
                            </label>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Judul Pengumuman</label>
                <input type="text" name="judul" value="{{ $initJudul }}" required placeholder="Contoh: Pengingat Pembayaran Sewa"
                    class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">
                @error('judul') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Isi Pesan Pengumuman</label>
                <textarea name="isi" rows="5" required placeholder="Tuliskan isi pengumuman atau instruksi untuk penghuni..."
                    class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">{{ $initIsi }}</textarea>
                @error('isi') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="pt-2">
                <x-btn type="submit" variant="primary" size="md" class="w-full">
                    Kirim Pengumuman Sekarang
                </x-btn>
            </div>
        </form>
    </div>
</div>
@endsection
