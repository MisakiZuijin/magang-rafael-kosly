@extends('layouts.app')

@section('title', 'Verifikasi Pembayaran - Super Admin')

@section('content')
<div class="space-y-4" x-data="{ 
    tab: 'pending', 
    showReviewModal: false,
    showRejectReason: false,
    selectedPenghuni: '',
    selectedKosKamar: '',
    selectedJumlah: '',
    selectedTanggal: '',
    selectedBuktiUrl: '',
    verifyUrl: '',
    rejectUrl: ''
}">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Verifikasi Pembayaran</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kelola konfirmasi bukti pembayaran dari anak kos (Super Admin)</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="grid grid-cols-3 gap-1.5 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
        <button @click="tab = 'pending'"
            :class="tab === 'pending' ? 'bg-white dark:bg-gray-900 text-amber-700 dark:text-amber-400 shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400 font-medium'"
            class="py-2 text-xs rounded-lg transition-all text-center">
            Pending ({{ $pending->count() }})
        </button>
        <button @click="tab = 'terverifikasi'"
            :class="tab === 'terverifikasi' ? 'bg-white dark:bg-gray-900 text-emerald-700 dark:text-emerald-400 shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400 font-medium'"
            class="py-2 text-xs rounded-lg transition-all text-center">
            Verifikasi ({{ $terverifikasi->count() }})
        </button>
        <button @click="tab = 'ditolak'"
            :class="tab === 'ditolak' ? 'bg-white dark:bg-gray-900 text-red-700 dark:text-red-400 shadow-sm font-bold' : 'text-gray-500 dark:text-gray-400 font-medium'"
            class="py-2 text-xs rounded-lg transition-all text-center">
            Ditolak ({{ $ditolak->count() }})
        </button>
    </div>

    {{-- Tab Pending --}}
    <div x-show="tab === 'pending'" class="space-y-3" x-transition>
        @forelse($pending as $p)
        @php
        $penghuniNama = $p->penghuniKamar->penghuni->nama ?? 'Anak Kos';
        $kosKamar = ($p->penghuniKamar->kamar->kode_kamar ?? '-') . ' · ' . ($p->penghuniKamar->kamar->kos->nama ?? '-');
        $jumlahFormatted = 'Rp ' . number_format($p->jumlah, 0, ',', '.');
        $tanggalFormatted = $p->tanggal_bayar ? $p->tanggal_bayar->format('d M Y') : '-';
        $buktiUrl = $p->bukti_transfer_url ? asset('storage/' . $p->bukti_transfer_url) : '';
        $vUrl = route('superadmin.pembayaran.verify', $p->id);
        $rUrl = route('superadmin.pembayaran.reject', $p->id);
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-amber-200 dark:border-amber-900/50 shadow-sm space-y-3">
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-amber-600 dark:text-amber-400">Menunggu Verifikasi</span>
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white mt-0.5">
                        {{ $penghuniNama }}
                    </h3>
                    <p class="text-xs text-gray-500 font-mono">
                        {{ $kosKamar }}
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-base font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                        {{ $jumlahFormatted }}
                    </p>
                    <p class="text-[10px] text-gray-400 font-mono">
                        {{ $tanggalFormatted }}
                    </p>
                </div>
            </div>

            {{-- Button Tinjau & Cek Bukti Pembayaran --}}
            <div class="pt-1">
                <button type="button"
                    @click="
                                selectedPenghuni = '{{ addslashes($penghuniNama) }}';
                                selectedKosKamar = '{{ addslashes($kosKamar) }}';
                                selectedJumlah = '{{ $jumlahFormatted }}';
                                selectedTanggal = '{{ $tanggalFormatted }}';
                                selectedBuktiUrl = '{{ $buktiUrl }}';
                                verifyUrl = '{{ $vUrl }}';
                                rejectUrl = '{{ $rUrl }}';
                                showRejectReason = false;
                                showReviewModal = true;
                            "
                    class="w-full py-2.5 px-4 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 font-bold text-xs rounded-xl border border-emerald-200 dark:border-emerald-800 flex items-center justify-center gap-2 active:scale-[0.98] transition-all">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>Cek Bukti Pembayaran &amp; Verifikasi</span>
                </button>
            </div>
        </div>
        @empty
        <x-empty-state message="Tidak ada pembayaran yang menunggu verifikasi." />
        @endforelse
    </div>

    {{-- Tab Terverifikasi --}}
    <div x-show="tab === 'terverifikasi'" class="space-y-3" x-transition x-cloak>
        @forelse($terverifikasi as $p)
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-2">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white">
                        {{ $p->penghuniKamar->penghuni->nama ?? 'Anak Kos' }}
                    </h3>
                    <p class="text-xs text-gray-500 font-mono">
                        {{ $p->penghuniKamar->kamar->kode_kamar ?? '-' }} · {{ $p->penghuniKamar->kamar->kos->nama ?? '-' }}
                    </p>
                </div>
                <x-badge type="success">Terverifikasi</x-badge>
            </div>

            <div class="flex justify-between items-center text-xs pt-2 border-t border-gray-100 dark:border-gray-800">
                <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400">
                    Rp {{ number_format($p->jumlah, 0, ',', '.') }}
                </span>
                <span class="text-gray-400 font-mono">
                    Diverifikasi oleh: {{ $p->verifier->nama ?? 'Super Admin' }}
                </span>
            </div>
        </div>
        @empty
        <x-empty-state message="Belum ada pembayaran yang terverifikasi." />
        @endforelse
    </div>

    {{-- Tab Ditolak --}}
    <div x-show="tab === 'ditolak'" class="space-y-3" x-transition x-cloak>
        @forelse($ditolak as $p)
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-red-200 dark:border-red-900/50 shadow-sm space-y-2">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white">
                        {{ $p->penghuniKamar->penghuni->nama ?? 'Anak Kos' }}
                    </h3>
                    <p class="text-xs text-gray-500 font-mono">
                        {{ $p->penghuniKamar->kamar->kode_kamar ?? '-' }} · {{ $p->penghuniKamar->kamar->kos->nama ?? '-' }}
                    </p>
                </div>
                <x-badge type="danger">Ditolak</x-badge>
            </div>

            <div class="p-2.5 bg-red-50/50 dark:bg-red-950/30 rounded-xl border border-red-100 dark:border-red-900/40 text-xs">
                <span class="font-bold text-red-700 dark:text-red-300">Catatan Penolakan:</span>
                <p class="text-red-600 dark:text-red-400 mt-0.5">{{ $p->catatan ?? '-' }}</p>
            </div>
        </div>
        @empty
        <x-empty-state message="Tidak ada catatan pembayaran yang ditolak." />
        @endforelse
    </div>

    {{-- Modal Tinjau Bukti & Verifikasi Pembayaran --}}
    <x-modal show="showReviewModal" title="Tinjau Bukti Pembayaran">
        <div class="space-y-4">
            {{-- Display Gambar Bukti Pembayaran --}}
            <div class="bg-gray-100 dark:bg-gray-800 rounded-2xl p-2 border border-gray-200 dark:border-gray-700 text-center overflow-hidden">
                <template x-if="selectedBuktiUrl">
                    <img :src="selectedBuktiUrl" class="w-full max-h-[300px] object-contain rounded-xl shadow-sm mx-auto" alt="Bukti Transfer">
                </template>
                <template x-if="!selectedBuktiUrl">
                    <div class="py-8 text-xs text-gray-400 font-medium">
                        (User tidak menyertakan foto bukti transfer)
                    </div>
                </template>
            </div>

            {{-- Detail Rincian Pembayaran --}}
            <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-3 border border-gray-100 dark:border-gray-800 space-y-1.5 text-xs">
                <div class="flex justify-between">
                    <span class="text-gray-500 font-semibold">Nama Penghuni:</span>
                    <span class="font-bold text-gray-900 dark:text-white" x-text="selectedPenghuni"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-semibold">Kamar &amp; Kos:</span>
                    <span class="font-mono text-gray-800 dark:text-gray-200" x-text="selectedKosKamar"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-semibold">Jumlah Nominal:</span>
                    <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 text-sm" x-text="selectedJumlah"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-semibold">Tanggal Bayar:</span>
                    <span class="font-mono text-gray-600 dark:text-gray-400" x-text="selectedTanggal"></span>
                </div>
            </div>

            {{-- Form Penolakan --}}
            <div x-show="showRejectReason" x-transition class="space-y-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                <label class="block text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-wider">
                    Alasan Penolakan Pembayaran
                </label>
                <form :action="rejectUrl" method="POST" class="space-y-2">
                    @csrf
                    <textarea name="catatan" rows="3" required placeholder="Tuliskan catatan penolakan untuk anak kos..."
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-red-200 dark:border-red-800/60 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-red-500 focus:outline-none"></textarea>

                    <div class="flex gap-2">
                        <x-btn type="button" variant="secondary" size="sm" @click="showRejectReason = false" class="flex-1 !min-h-[36px] text-xs">
                            Batal Tolak
                        </x-btn>
                        <x-btn type="submit" variant="danger" size="sm" class="flex-1 !min-h-[36px] text-xs">
                            Kirim Penolakan
                        </x-btn>
                    </div>
                </form>
            </div>

            {{-- Action Buttons Utama Modal --}}
            <div x-show="!showRejectReason" class="flex flex-col sm:flex-row gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                {{-- Form Confirm / Verifikasi --}}
                <form :action="verifyUrl" method="POST" class="flex-1">
                    @csrf
                    <x-btn type="submit" variant="primary" size="sm" class="w-full !min-h-[40px] text-xs font-bold">
                        ✓ Confirm / Verifikasi
                    </x-btn>
                </form>

                {{-- Button Tolak Pembayaran --}}
                <x-btn type="button" variant="danger" size="sm" @click="showRejectReason = true" class="flex-1 !min-h-[40px] text-xs font-bold">
                    ✕ Tolak Pembayaran
                </x-btn>

                {{-- Button Batal / Tutup Modal --}}
                <x-btn type="button" variant="secondary" size="sm" @click="showReviewModal = false" class="flex-1 !min-h-[40px] text-xs font-bold">
                    Batal / Tutup
                </x-btn>
            </div>
        </div>
    </x-modal>
</div>
@endsection
