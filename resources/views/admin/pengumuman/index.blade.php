@extends('layouts.app')

@section('title', 'Broadcast & Pengumuman')

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Broadcast Pengumuman</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Log pengumuman &amp; pengiriman pesan ke anak kos</p>
        </div>
    </div>

    <x-btn href="{{ route('admin.pengumuman.create') }}" size="sm" class="!min-h-[36px] !py-1 text-xs">
        + Kirim Pengumuman
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
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-2.5">
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
            </div>

            <p class="text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl border border-gray-100 dark:border-gray-800 leading-relaxed">
                {{ $p->isi }}
            </p>

            <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
                <span>Dibuat oleh: <strong class="text-gray-700 dark:text-gray-300">{{ $p->dibuatOleh->nama ?? ($p->pembuat->nama ?? 'Admin') }}</strong></span>
            </div>
            <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
                <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 font-bold border border-emerald-200 dark:border-emerald-800">
                    {{ $targetDesc }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection