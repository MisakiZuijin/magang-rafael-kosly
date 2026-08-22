@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="mb-5">
    <x-page-header title="Notifikasi" subtitle="Pemberitahuan & pengumuman akun Anda" backUrl="{{ route('dashboard') }}">
        @slot('action')
        @if($notifikasis->where('status', 'terkirim')->count() > 0)
        <form action="{{ route('notifikasi.read-all') }}" method="POST">
            @csrf
            <button type="submit" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 active:text-emerald-700">
                Tandai Semua
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
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border {{ $n->status === 'terkirim' ? 'border-gray-200 dark:border-gray-800' : 'border-gray-100 dark:border-gray-800/50 bg-gray-50/50 dark:bg-gray-800/30' }} shadow-sm">
        <div class="flex items-start gap-3">
            {{-- Icon berdasarkan channel --}}
            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0
                        {{ $n->channel === 'whatsapp' ? 'bg-green-100 dark:bg-green-900/30 text-green-600' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-600' }}">
                @if($n->channel === 'whatsapp')
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                </svg>
                @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2 mb-1">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-tight">{{ $n->judul }}</h3>
                    @if($n->status === 'terkirim')
                    <span class="w-2 h-2 bg-emerald-500 rounded-full flex-shrink-0 mt-1.5"></span>
                    @endif
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed mb-2">{{ $n->pesan }}</p>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] text-gray-400">{{ $n->created_at->diffForHumans() }}</span>
                    @if($n->status === 'terkirim')
                    <form action="{{ route('notifikasi.read', $n->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 active:text-emerald-700 px-2 py-1 rounded-lg active:bg-emerald-50 dark:active:bg-emerald-900/20">
                            Tandai dibaca
                        </button>
                    </form>
                    @else
                    <span class="text-[11px] text-gray-400 font-medium">Dibaca</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection