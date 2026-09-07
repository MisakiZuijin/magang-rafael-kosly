@php
$isSuperAdmin = request()->is('superadmin*');
$createRoute = $isSuperAdmin ? route('superadmin.pengumuman.create') : route('admin.pengumuman.create');
@endphp

@extends('layouts.app')

@section('title', 'Broadcast & Pengumuman')

@section('content')
<div class="space-y-4" x-data="{
    showModal: false,
    selectedP: null,
    openDetail(item) {
        this.selectedP = item;
        this.showModal = true;
    }
}">
    {{-- Header --}}
    <x-page-header title="Broadcast Pengumuman" subtitle="Log pengumuman dan pengiriman pesan ke anak kos" backUrl="{{ route('dashboard') }}" />

    <x-btn href="{{ $createRoute }}" size="sm" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-sm active:scale-95 transition-all text-xs flex items-center justify-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Kirim Pengumuman</span>
    </x-btn>

    {{-- Log Pengumuman --}}
    @if($pengumumans->isEmpty())
    <x-empty-state message="Belum ada pengumuman yang dikirim. Klik + Kirim Pengumuman untuk membuat pengumuman baru." />
    @else
    <div class="space-y-3">
        @foreach($pengumumans as $p)
        @php
        $targetDesc = $p->getTargetDescription(false);

        $itemData = [
            'id' => $p->id,
            'judul' => $p->judul,
            'isi' => $p->isi,
            'tipe' => $p->tipe,
            'channel' => $p->channel,
            'pembuat' => $p->dibuatOleh->nama ?? ($p->pembuat->nama ?? 'Admin'),
            'target' => $targetDesc,
            'created_at' => $p->created_at ? $p->created_at->format('d M Y H:i') : '-',
        ];
        @endphp

        <div @click="openDetail({{ json_encode($itemData) }})"
            class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-xs cursor-pointer hover:border-emerald-300 dark:hover:border-emerald-700 transition-all space-y-2.5">
            <div class="flex justify-between items-start gap-2">
                <div>
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        {{-- Badge Tipe --}}
                        @if($p->tipe === 'pembayaran')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] uppercase font-bold rounded-md bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Pembayaran</span>
                        </span>
                        @elseif($p->tipe === 'aturan')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] uppercase font-bold rounded-md bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Aturan</span>
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] uppercase font-bold rounded-md bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Info</span>
                        </span>
                        @endif

                        {{-- Badge Channel --}}
                        @if($p->channel === 'whatsapp')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                            <svg class="w-3 h-3 fill-current text-emerald-600 dark:text-emerald-400 flex-shrink-0" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                                <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                            </svg>
                            <span>WhatsApp</span>
                        </span>
                        @elseif($p->channel === 'keduanya')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-md bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span>Web & WA</span>
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-md bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            <span>Web App</span>
                        </span>
                        @endif

                        <span class="text-[11px] text-gray-400 font-mono">
                            {{ $p->created_at ? $p->created_at->format('d M Y H:i') : '-' }}
                        </span>
                    </div>
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white leading-snug">{{ $p->judul }}</h3>
                </div>
                <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex-shrink-0">Detail</span>
            </div>

            <p class="text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl border border-gray-100 dark:border-gray-800 leading-relaxed line-clamp-2">
                {{ $p->isi }}
            </p>

            <div class="grid grid-cols-1 gap-3 text-[11px] text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
                <span>Dibuat oleh: <strong class="text-gray-700 dark:text-gray-300">{{ $p->dibuatOleh->nama ?? ($p->pembuat->nama ?? 'Admin') }}</strong></span>
                <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 font-bold border border-emerald-200 dark:border-emerald-800">
                    {{ $targetDesc }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Detail Popup Modal --}}
    <div x-show="showModal"
        x-cloak
        class="fixed -inset-10 z-[99999] min-h-screen overflow-y-auto flex items-center justify-center bg-gray-900/75 backdrop-blur-xs p-3 sm:p-4 box-border">

        <div class="bg-white dark:bg-gray-900 rounded-3xl w-[calc(100%-2rem)] max-w-[340px] sm:max-w-[360px] my-auto p-4 sm:p-5 border border-gray-200 dark:border-gray-800 shadow-2xl space-y-3.5 text-left box-border max-h-[80vh] overflow-y-auto no-scrollbar"
            @click.away="showModal = false"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-start justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-3">
                <div>
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300" x-text="selectedP?.tipe"></span>
                        <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300" x-text="selectedP?.channel === 'whatsapp' ? 'WhatsApp' : (selectedP?.channel === 'keduanya' ? 'Web & WA' : 'Web App')"></span>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-snug" x-text="selectedP?.judul"></h3>
                </div>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-lg font-bold">✕</button>
            </div>

            <div class="space-y-3">
                <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line bg-gray-50 dark:bg-gray-800/50 p-3.5 rounded-2xl border border-gray-100 dark:border-gray-800 font-normal" x-text="selectedP?.isi"></p>
                <div class="space-y-1 text-[11px] text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/30 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800">
                    <p>Dibuat oleh: <strong class="text-gray-700 dark:text-gray-300" x-text="selectedP?.pembuat"></strong></p>
                    <p>Sasaran: <strong class="text-emerald-600 dark:text-emerald-400" x-text="selectedP?.target"></strong></p>
                    <p>Waktu: <span class="font-mono text-gray-600 dark:text-gray-400" x-text="selectedP?.created_at"></span></p>
                </div>
            </div>

            <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
                <button @click="showModal = false" class="w-full py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold rounded-xl text-xs active:scale-95 transition-all">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection