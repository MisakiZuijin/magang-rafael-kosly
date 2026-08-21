@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div x-data="{ showCheckoutModal: false }" class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Selamat datang,</p>
            <h1 class="text-xl font-bold dark:text-white">{{ Auth::user()->nama }}</h1>
        </div>
    </div>

    @if(session('show_aturan_popup'))
    <x-modal show="true" title="Aturan Kos">
        <p class="mb-2">Harap baca dan patuhi aturan kos yang berlaku di tempat tinggal Anda.</p>
        @slot('footer')
        <x-btn variant="secondary" @click="open = false">Nanti</x-btn>
        <x-btn @click="open = false; fetch('{{ route('penghuni.aturan.dismiss') }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'}, body:JSON.stringify({kos_id:{{ session('kos_id_popup') ?? 'null' }}})})">
            Mengerti
        </x-btn>
        @endslot
    </x-modal>
    @endif

    @if($data['kos'])
    {{-- Info Card --}}
    <x-card class="border-l-4 border-l-emerald-500 space-y-3">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Kos Anda</p>
                <h2 class="font-bold text-lg leading-tight dark:text-white">{{ $data['kos']->nama }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kamar {{ $data['kamar']->kode_kamar }}</p>
            </div>
            <x-badge type="{{ $data['is_future'] ? 'info' : 'success' }}">{{ $data['is_future'] ? 'Reservasi' : ucfirst($data['durasi']) }}</x-badge>
        </div>

        <x-stat-grid :items="[
            ['label' => 'Masa Sewa', 'value' => ucfirst($data['durasi'])],
            ['label' => 'Penghuni', 'value' => $data['jumlah_penghuni'] . ' org'],
            ['label' => 'Kapasitas', 'value' => $data['kamar']->kapasitas . ' org'],
        ]" />

        {{-- Estimasi & Informasi Tarif Lengkap (Bulanan & Harian) --}}
        <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-100 dark:border-gray-700/60 space-y-2">
            <div class="grid grid-cols-1 gap-2">
                <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-1">
                    💰 Informasi Tarif & Perkiraan Sewa Kamar
                </span>
                @if($data['is_berbagi'])
                <span class="px-2 py-0.5 text-center text-[9px] w-[100px] font-bold rounded bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                    Kamar Berbagi
                </span>
                @else
                <span class="px-2 py-0.5 text-center text-[9px] w-[100px] font-bold rounded bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                    Kamar Standart
                </span>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs">
                {{-- Tarif Bulanan --}}
                <div class="p-2 bg-white dark:bg-gray-900 rounded-lg border border-gray-200/70 dark:border-gray-700 space-y-0.5">
                    <p class="text-[10px] text-gray-400 font-semibold uppercase">Tarif Bulanan</p>
                    @if($data['is_berbagi'])
                    <p class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                        Rp {{ number_format(round($data['harga_bulan'] / 2), 0, ',', '.') }} <span class="text-[9px] font-normal text-gray-400">/ 50%</span>
                    </p>
                    <p class="text-[9px] text-gray-400 font-mono">
                        Full: Rp {{ number_format($data['harga_bulan'], 0, ',', '.') }}
                    </p>
                    @else
                    <p class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                        Rp {{ number_format($data['harga_bulan'], 0, ',', '.') }} <span class="text-[9px] font-normal text-gray-400">/ bulan</span>
                    </p>
                    @endif
                </div>

                {{-- Tarif Harian --}}
                <div class="p-2 bg-white dark:bg-gray-900 rounded-lg border border-gray-200/70 dark:border-gray-700 space-y-0.5">
                    <p class="text-[10px] text-gray-400 font-semibold uppercase">Tarif Harian</p>
                    @if($data['is_berbagi'])
                    <p class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                        Rp {{ number_format(round($data['harga_hari'] / 2), 0, ',', '.') }} <span class="text-[9px] font-normal text-gray-400">/ 50% hari</span>
                    </p>
                    <p class="text-[9px] text-gray-400 font-mono">
                        Full: Rp {{ number_format($data['harga_hari'], 0, ',', '.') }}/hari
                    </p>
                    @else
                    <p class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                        Rp {{ number_format($data['harga_hari'], 0, ',', '.') }} <span class="text-[9px] font-normal text-gray-400">/ hari</span>
                    </p>
                    @endif
                </div>
            </div>
        </div>

        @if($data['is_future'])
        {{-- Tampilan khusus jika tanggal masuk belum tiba (Future Reservation) --}}
        <div class="p-3.5 bg-blue-50 dark:bg-blue-950/30 rounded-xl border border-blue-100 dark:border-blue-900/50 space-y-2">
            <div class="flex items-center gap-2">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                </span>
                <p class="text-[11px] font-bold text-blue-700 dark:text-blue-300 uppercase tracking-wider">
                    📅 Reservasi Sewa Mendatang
                </p>
            </div>
            <p class="text-xs text-blue-900 dark:text-blue-200 font-medium leading-relaxed">
                Pendaftaran kamar ini telah berhasil atas nama Anda untuk periode mulai
                <strong>{{ $data['tanggal_masuk']->format('d M Y') }}</strong> s/d <strong>{{ $data['tanggal_keluar']->format('d M Y') }}</strong>.
            </p>
            <div class="p-2.5 bg-white dark:bg-gray-900 rounded-lg border border-blue-200/60 dark:border-blue-900/40 flex items-center justify-between text-xs">
                <span class="text-gray-600 dark:text-gray-300 font-medium">Status Kamar:</span>
                <span class="font-bold text-blue-600 dark:text-blue-400">
                    Belum Dapat Dihuni (Mulai {{ $data['sisa_hari_masuk'] }} Hari Lagi)
                </span>
            </div>
        </div>
        @elseif($data['tanggal_keluar'])
        {{-- Countdown jika masa sewa sudah berjalan --}}
        @php
        $diff = now()->diff($data['tanggal_keluar']);
        $isExpired = now()->gt($data['tanggal_keluar']);
        $initialText = $isExpired
        ? 'Sudah habis'
        : ($diff->d > 0
        ? $diff->d . ' hari ' . $diff->h . ' jam ' . $diff->i . ' menit'
        : ($diff->h > 0
        ? $diff->h . ' jam ' . $diff->i . ' menit'
        : $diff->i . ' menit'));
        @endphp

        <div x-data="{ 
                        target: new Date('{{ $data['tanggal_keluar']->format('Y-m-d H:i:s') }}').getTime(),
                        formatted: '{{ $initialText }}',
                        timer: null,
                        start() {
                            this.update();
                            this.timer = setInterval(() => this.update(), 60000);
                        },
                        update() {
                            const distance = this.target - new Date().getTime();
                            if (distance < 0) { 
                                this.formatted = 'Sudah habis'; 
                                clearInterval(this.timer); 
                                return; 
                            }
                            const d = Math.floor(distance / (1000*60*60*24));
                            const h = Math.floor((distance % (1000*60*60*24)) / (1000*60*60));
                            const m = Math.floor((distance % (1000*60*60)) / (1000*60));
                            this.formatted = d > 0 ? `${d} hari ${h} jam ${m} menit` : (h > 0 ? `${h} jam ${m} menit` : `${m} menit`);
                        }
                    }" x-init="start()" class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-800/50 flex flex-col justify-center">
            <p class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">Sisa Waktu Masa Sewa</p>
            <p class="text-xl font-bold text-emerald-700 dark:text-emerald-300 font-mono" x-text="formatted">{{ $initialText }}</p>
        </div>
        @endif

        {{-- Tombol Checkout Self Service --}}
        <div class="pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <span class="text-xs text-gray-500 font-medium">{{ $data['is_future'] ? 'Reservasi Terjadwal' : 'Sewa Kamar Aktif' }}</span>
            <button type="button"
                @click="showCheckoutModal = true"
                class="px-3 py-1.5 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-900/50 text-red-600 dark:text-red-300 font-bold text-xs rounded-xl border border-red-200 dark:border-red-900/50 flex items-center gap-1.5 active:scale-95 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Checkout Sewa Kamar</span>
            </button>
        </div>
    </x-card>

    {{-- Rekening Card --}}
    <x-card title="Pembayaran" class="dark:text-white">
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold dark:text-white">{{ $data['kos']->bank }}</p>
                        <p class="text-xs text-gray-500 font-mono">{{ $data['kos']->no_rekening }}</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 pl-10">{{ $data['kos']->nama_pemilik_rekening }}</p>
            </div>
            <x-btn href="{{ route('penghuni.pembayaran') }}" size="sm">Bayar</x-btn>
        </div>
    </x-card>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('penghuni.aturan') }}" class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm active:scale-[0.98] transition-transform">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <p class="text-sm font-bold dark:text-white">Aturan</p>
            <p class="text-xs text-gray-500 mt-0.5">Lihat aturan kos</p>
        </a>
        <a href="{{ route('penghuni.pembayaran') }}" class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm active:scale-[0.98] transition-transform">
            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <p class="text-sm font-bold dark:text-white">Riwayat</p>
            <p class="text-xs text-gray-500 mt-0.5">Cek pembayaran</p>
        </a>
    </div>

    {{-- Modal Confirmation Checkout --}}
    <x-modal show="showCheckoutModal" title="Konfirmasi Checkout Sewa Kos">
        <div class="space-y-4">
            <div class="p-3.5 bg-red-50 dark:bg-red-950/30 rounded-2xl border border-red-200 dark:border-red-800/50 text-xs text-red-700 dark:text-red-300 space-y-1.5">
                <p class="font-bold text-sm">⚠️ Konfirmasi Akhiri Sewa</p>
                <p>Apakah Anda yakin ingin mengakhiri masa sewa dan checkout dari <strong>Kamar {{ $data['kamar']->kode_kamar ?? '' }}</strong> di <strong>{{ $data['kos']->nama ?? '' }}</strong>?</p>
                <p class="text-[11px] text-red-600/80 dark:text-red-400/80 leading-relaxed">
                    Setelah melakukan checkout, masa huni Anda akan diselesaikan, kamar ini akan kembali kosong untuk disewa pengguna lain, dan riwayat sewa Anda akan tersimpan di sistem.
                </p>
            </div>

            <form action="{{ route('penghuni.checkout') }}" method="POST" class="pt-2 flex justify-end gap-2">
                @csrf
                <x-btn type="button" variant="secondary" size="sm" @click="showCheckoutModal = false">Batal</x-btn>
                <x-btn type="submit" variant="danger" size="sm">Ya, Checkout Sekarang</x-btn>
            </form>
        </div>
    </x-modal>
    @else
    <x-empty-state message="Anda belum terdaftar di kamar manapun." />
    @endif
</div>
@endsection