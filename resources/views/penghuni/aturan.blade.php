@extends('layouts.app')

@section('title', 'Aturan Kos')

@section('content')
<div class="space-y-4">
    <x-page-header title="Aturan Kos" subtitle="Tata tertib dan aturan sewa kos yang berlaku" />

    @if($aturans->isEmpty())
    <x-card>
        <x-empty-state message="Belum ada aturan yang ditetapkan oleh pemilik kos." />
    </x-card>
    @else
    <div class="space-y-4">
        @foreach($aturans as $index => $aturan)
        <x-card>
            <div class="flex items-start gap-3.5">
                {{-- Nomor urut rapi di kiri --}}
                <div class="w-8 h-8 bg-emerald-500 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm mt-0.5">
                    <span class="text-xs font-bold text-white">{{ $index + 1 }}</span>
                </div>

                <div class="flex-1 min-w-0 pt-0.5">
                    <p class="text-sm text-gray-700 dark:text-gray-200 leading-relaxed whitespace-pre-line">{{ $aturan->isi_aturan }}</p>
                    <p class="text-[11px] text-gray-400 mt-3">Diperbarui {{ $aturan->created_at->diffForHumans() }}</p>
                </div>
            </div>
        </x-card>
        @endforeach
    </div>

    {{-- Konfirmasi sudah baca --}}
    <div class="mt-6">
        <x-card class="bg-emerald-50 dark:bg-emerald-900/20 border-emerald-100 dark:border-emerald-800/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-800/50 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-emerald-800 dark:text-emerald-300">Penting</p>
                    <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-0.5">Dengan tinggal di kos ini, Anda dianggap sudah membaca dan menyetujui semua aturan di atas.</p>
                </div>
            </div>
        </x-card>
    </div>
    @endif
</div>
@endsection