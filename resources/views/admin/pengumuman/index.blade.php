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
        $targetDesc = 'Semua User / Anak Kos';
        if ($p->targets && $p->targets->isNotEmpty()) {
        $firstTarget = $p->targets->first();
        $targetTipe = $firstTarget->target_tipe;
        $targetIds = $p->targets->pluck('target_id')->toArray();

        if ($targetTipe === 'kos') {
        $kosNames = \App\Models\Kos::whereIn('id', $targetIds)->pluck('nama')->toArray();
        $targetDesc = 'Target Kos: ' . implode(', ', $kosNames);
        } elseif ($targetTipe === 'kamar') {
        $kamarList = \App\Models\Kamar::with('kos')->whereIn('id', $targetIds)->get();
        $kamarNames = $kamarList->map(function($km) {
        return 'Kamar ' . $km->kode_kamar . ' (' . ($km->kos->nama ?? '-') . ')';
        })->toArray();
        $targetDesc = 'Target Kamar: ' . implode(', ', $kamarNames);
        }
        }

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
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2 py-0.5 text-[10px] uppercase font-bold rounded-md 
                                    {{ $p->tipe === 'pembayaran' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : '' }}
                                    {{ $p->tipe === 'aturan' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : '' }}
                                    {{ $p->tipe === 'info' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : '' }}">
                            {{ ucfirst($p->tipe) }}
                        </span>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                            {{ $p->channel === 'whatsapp' ? '💬 WhatsApp' : ($p->channel === 'keduanya' ? '🔔 Web & WA' : '🌐 Web App') }}
                        </span>
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
                        <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300" x-text="selectedP?.channel"></span>
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