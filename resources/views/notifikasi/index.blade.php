@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="space-y-4" x-data="{ 
    showModal: false, 
    selectedNotif: null,
    openDetail(notif) {
        this.selectedNotif = notif;
        this.showModal = true;
    },
    markRead(id) {
        if (!id) return;
        fetch('/notifikasi/' + id + '/read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        }).then(() => {
            window.location.reload();
        });
    }
}">
    {{-- Header --}}
    <div class="mb-5">
        <x-page-header title="Notifikasi" subtitle="Pemberitahuan & pengumuman akun Anda" backUrl="{{ route('dashboard') }}">
            @slot('action')
            @if($notifikasis->where('status', 'terkirim')->count() > 0)
            <form action="{{ route('notifikasi.read-all') }}" method="POST">
                @csrf
                <button type="submit" class="text-xs font-bold px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-600 dark:text-emerald-400 rounded-xl active:scale-95 transition-all shadow-xs">
                    Tandai Semua Dibaca
                </button>
            </form>
            @endif
            @endslot
        </x-page-header>
    </div>

    @if($notifikasis->isEmpty())
    <x-card>
        <x-empty-state message="Belum ada notifikasi untuk Anda." />
    </x-card>
    @else
    <div class="space-y-3">
        @foreach($notifikasis as $n)
        @php
        $notifData = [
        'id' => $n->id,
        'judul' => $n->judul,
        'pesan' => $n->pesan,
        'channel' => $n->channel,
        'status' => $n->status,
        'time_human' => $n->created_at->diffForHumans(),
        'time_exact' => $n->created_at->format('d M Y H:i'),
        ];
        @endphp

        <div @click="openDetail({{ json_encode($notifData) }})"
            class="bg-white dark:bg-gray-900 rounded-2xl p-4 border cursor-pointer hover:border-emerald-300 dark:hover:border-emerald-700 transition-all shadow-xs {{ $n->status === 'terkirim' ? 'border-emerald-200 dark:border-emerald-800/80 bg-emerald-50/20 dark:bg-emerald-950/10' : 'border-gray-200 dark:border-gray-800/50 bg-gray-50/40 dark:bg-gray-800/20' }}">
            <div class="flex items-start gap-3">
                {{-- Icon Notifikasi Web --}}
                <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <h3 class="text-xs font-bold text-gray-900 dark:text-white leading-snug line-clamp-1">{{ $n->judul }}</h3>
                        @if($n->status === 'terkirim')
                        <span class="px-1.5 py-0.5 text-[8px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 rounded-md flex-shrink-0">Baru</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-300 line-clamp-2 leading-relaxed mb-2">{{ $n->pesan }}</p>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-gray-400 font-mono">{{ $n->created_at->diffForHumans() }}</span>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline">Detail</span>
                            @if($n->status === 'terkirim')
                            <form action="{{ route('notifikasi.read', $n->id) }}" method="POST" @click.stop class="inline">
                                @csrf
                                <button type="submit" class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 px-2 py-0.5 rounded-lg active:scale-95 transition-all">
                                    Tandai Dibaca
                                </button>
                            </form>
                            @else
                            <span class="text-[10px] text-gray-400 font-medium">Dibaca</span>
                            @endif
                        </div>
                    </div>
                </div>
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
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 flex items-center justify-center font-bold text-sm">
                        🔔
                    </div>
                    <div>
                        <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">Notifikasi Web</span>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-snug" x-text="selectedNotif?.judul"></h3>
                    </div>
                </div>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-lg font-bold">✕</button>
            </div>

            <div class="space-y-2">
                <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line bg-gray-50 dark:bg-gray-800/50 p-3.5 rounded-2xl border border-gray-100 dark:border-gray-800 font-normal" x-text="selectedNotif?.pesan"></p>
                <div class="flex items-center justify-between text-[10px] text-gray-400 font-mono px-1">
                    <span x-text="'Waktu: ' + (selectedNotif?.time_exact || '')"></span>
                    <span class="px-2 py-0.5 rounded-md font-bold uppercase" :class="selectedNotif?.status === 'terkirim' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'" x-text="selectedNotif?.status === 'terkirim' ? 'Belum Dibaca' : 'Sudah Dibaca'"></span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                <template x-if="selectedNotif?.status === 'terkirim'">
                    <button @click="markRead(selectedNotif?.id)" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-xs active:scale-95 transition-all">
                        Tandai Dibaca
                    </button>
                </template>
                <button @click="showModal = false" class="w-full py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold rounded-xl text-xs active:scale-95 transition-all" :class="selectedNotif?.status === 'terkirim' ? '' : 'col-span-2'">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection