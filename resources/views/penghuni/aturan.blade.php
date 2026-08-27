@extends('layouts.app')

@section('title', 'Aturan Kos')

@section('content')
<div class="space-y-4" x-data="{
    showModal: false,
    selectedAturan: null,
    selectedNum: 1,
    openDetail(item, num) {
        this.selectedAturan = item;
        this.selectedNum = num;
        this.showModal = true;
    }
}">
    <x-page-header title="Aturan Kos" subtitle="Tata tertib dan aturan sewa kos yang berlaku" backUrl="{{ route('penghuni.dashboard') }}" />

    @if($aturans->isEmpty())
    <x-card>
        <x-empty-state message="Belum ada aturan yang ditetapkan oleh pemilik kos." />
    </x-card>
    @else
    <div class="space-y-3">
        @foreach($aturans as $index => $aturan)
        @php
        $aturanData = [
            'id' => $aturan->id,
            'isi_aturan' => $aturan->isi_aturan,
            'updated_human' => $aturan->created_at->diffForHumans(),
            'updated_exact' => $aturan->created_at->format('d M Y H:i'),
        ];
        @endphp
        <div @click="openDetail({{ json_encode($aturanData) }}, {{ $index + 1 }})"
             class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-xs cursor-pointer hover:border-emerald-300 dark:hover:border-emerald-700 transition-all">
            <div class="flex items-start gap-3.5">
                {{-- Nomor urut rapi di kiri --}}
                <div class="w-8 h-8 bg-emerald-500 rounded-xl flex items-center justify-center flex-shrink-0 shadow-xs mt-0.5">
                    <span class="text-xs font-bold text-white">{{ $index + 1 }}</span>
                </div>

                <div class="flex-1 min-w-0 pt-0.5">
                    <p class="text-xs text-gray-700 dark:text-gray-200 leading-relaxed line-clamp-3">{{ $aturan->isi_aturan }}</p>
                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <span class="text-[10px] text-gray-400 font-mono">Diperbarui {{ $aturan->created_at->diffForHumans() }}</span>
                        <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline">🔍 Detail Aturan</span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Detail Popup Modal --}}
    <div x-show="showModal" 
         x-cloak 
         class="fixed -inset-10 z-[99999] min-h-screen overflow-y-auto flex items-center justify-center bg-gray-900/75 backdrop-blur-xs p-3 sm:p-4 box-border">
        
        <div class="bg-white dark:bg-gray-900 rounded-3xl w-[calc(100%-2rem)] max-w-[340px] sm:max-w-[360px] my-auto p-4 sm:p-5 border border-gray-200 dark:border-gray-800 shadow-2xl space-y-3.5 text-left box-border max-h-[80vh] overflow-y-auto no-scrollbar" 
             @click.away="showModal = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-bold text-xs shadow-xs" x-text="selectedNum"></div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-tight">Detail Aturan Kos</h3>
                </div>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-lg font-bold">✕</button>
            </div>

            <div class="space-y-2">
                <p class="text-xs text-gray-700 dark:text-gray-200 leading-relaxed whitespace-pre-line bg-gray-50 dark:bg-gray-800/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-800 font-normal" x-text="selectedAturan?.isi_aturan"></p>
                <div class="flex items-center justify-between text-[10px] text-gray-400 font-mono px-1">
                    <span x-text="'Waktu Penetapan: ' + (selectedAturan?.updated_exact || '')"></span>
                </div>
            </div>

            <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
                <button @click="showModal = false" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs active:scale-95 transition-all shadow-xs">
                    Saya Mengerti
                </button>
            </div>
        </div>
    </div>

    {{-- Konfirmasi sudah baca --}}
    <div class="mt-4">
        <x-card class="bg-emerald-50 dark:bg-emerald-950/30 border-emerald-100 dark:border-emerald-800/50">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-emerald-100 dark:bg-emerald-900/50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300">Tata Tertib Kos</p>
                    <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5">Dengan tinggal di kos ini, Anda menyetujui seluruh tata tertib yang berlaku demi kenyamanan bersama.</p>
                </div>
            </div>
        </x-card>
    </div>
    @endif
</div>
@endsection