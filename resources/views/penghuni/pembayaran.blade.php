@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
<h1 class="text-xl font-bold mb-5 dark:text-white">Pembayaran</h1>

@if($rekening)
{{-- Info Rekening --}}
<x-card class="mb-4 bg-gradient-to-br from-emerald-500 to-emerald-600 border-0 text-white">
    <p class="text-[11px] font-bold text-emerald-100 uppercase tracking-wider mb-3">Rekening Pembayaran</p>
    <div class="flex items-center justify-between mb-3">
        <div>
            <p class="text-lg font-bold">{{ $rekening->bank }}</p>
            <p class="text-sm font-mono text-emerald-100 tracking-widest">{{ $rekening->no_rekening }}</p>
        </div>
        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
        </div>
    </div>
    <p class="text-xs text-emerald-100">A/n {{ $rekening->nama_pemilik_rekening }}</p>
</x-card>
@endif

{{-- Yang Harus Dibayar (Pending / Belum Upload) --}}
<h2 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Menunggu Pembayaran</h2>

@php
$menunggu = $pembayarans->where('status', 'pending');
@endphp

@if($menunggu->isEmpty())
<x-card class="mb-5">
    <div class="text-center py-6">
        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Tidak ada tagihan saat ini</p>
        <p class="text-xs text-gray-400 mt-1">Semua pembayaran sudah lunas</p>
    </div>
</x-card>
@else
<div class="space-y-3 mb-5">
    @foreach($menunggu as $p)
    <x-card class="border-l-4 border-l-amber-400">
        <div class="flex justify-between items-start mb-3">
            <div>
                <p class="text-sm font-bold dark:text-white">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Periode {{ $p->periode_mulai->format('d M Y') }} - {{ $p->periode_selesai->format('d M Y') }}</p>
            </div>
            <x-badge type="warning">Menunggu</x-badge>
        </div>

        @if(!$p->bukti_transfer_url)
        @php
        $kamar = $p->penghuniKamar->kamar ?? null;
        $hargaBulan = $kamar->harga_per_bulan ?? 0;
        $hargaHari = ($kamar->harga_per_hari ?? 0) > 0 ? $kamar->harga_per_hari : round($hargaBulan / 30);

        $hasPreviousVerified = $pembayarans->where('status', 'terverifikasi')->isNotEmpty();
        $tanggalKeluarRef = $p->penghuniKamar->tanggal_keluar
        ? \Carbon\Carbon::parse($p->penghuniKamar->tanggal_keluar)
        : \Carbon\Carbon::parse($p->periode_selesai);
        $sisaHariRef = (int) \Carbon\Carbon::now()->startOfDay()->diffInDays($tanggalKeluarRef->startOfDay(), false);

        // Fitur perpanjangan (Bulanan/Harian) HANYA aktif jika:
        // 1. Penghuni SUDAH PERNAH menyelesaikan pembayaran pendaftaran awal ($hasPreviousVerified === true), DAN
        // 2. Sisa hari menuju tanggal_keluar <= 7 hari.
            // Jika pembayaran awal belum pernah dilunasi ($hasPreviousVerified===false), perpanjangan HARUS DISEMBUNYIKAN
            // sehingga penghuni wajib melunasi pembayaran pendaftaran awalnya terlebih dahulu!
            $isExtensionMode=$hasPreviousVerified && ($sisaHariRef <=7);
            @endphp

            @if($isExtensionMode)
            {{-- MODE PERPANJANGA SEWA (SISA <= 7 HARI ATAU PERPANJANGAN SIKLUS BERIKUTNYA) --}}
            <form action="{{ route('penghuni.pembayaran.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4"
            x-data="{
                  tipe: '{{ $p->tipe_perpanjangan ?? 'bulanan' }}',
                  jumlahHari: {{ $p->jumlah_hari && $p->jumlah_hari != 30 ? $p->jumlah_hari : 1 }},
                  hargaBulan: {{ $hargaBulan }},
                  hargaHari: {{ $hargaHari }},
                  baseDateStr: '{{ $tanggalKeluarRef->format('Y-m-d') }}',
                  get addedDays() {
                      return this.tipe === 'harian' ? (this.jumlahHari || 1) : 30;
                  },
                  get totalCalculated() {
                      if (this.tipe === 'harian') {
                          return (this.jumlahHari || 1) * this.hargaHari;
                      }
                      return this.hargaBulan;
                  },
                  get formattedStartDate() {
                      if (!this.baseDateStr) return '-';
                      let parts = this.baseDateStr.split('-');
                      let d = new Date(parts[0], parts[1] - 1, parts[2]);
                      return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(d);
                  },
                  get formattedEndDate() {
                      if (!this.baseDateStr) return '-';
                      let parts = this.baseDateStr.split('-');
                      let d = new Date(parts[0], parts[1] - 1, parts[2]);
                      d.setDate(d.getDate() + this.addedDays);
                      return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(d);
                  },
                  formatRupiah(num) {
                      return 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
                  }
              }">
            @csrf
            <input type="hidden" name="pembayaran_id" value="{{ $p->id }}">

            {{-- Opsi Tipe Perpanjangan --}}
            <div class="space-y-2">
                <label class="block text-[11px] font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Pilih Skema Perpanjangan Sewa (Sisa {{ $sisaHariRef }} Hari)
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <label :class="tipe === 'bulanan' ? 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 ring-2 ring-emerald-500/20' : 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40 text-gray-700 dark:text-gray-300'"
                        class="p-3 rounded-xl border cursor-pointer flex flex-col justify-between transition-all">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold">1 Bulan</span>
                            <input type="radio" name="tipe_perpanjangan" value="bulanan" x-model="tipe" class="text-emerald-600 focus:ring-emerald-500">
                        </div>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 font-mono">Tambah 30 Hari</p>
                        <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mt-1" x-text="formatRupiah(hargaBulan)"></p>
                    </label>

                    <label :class="tipe === 'harian' ? 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 ring-2 ring-emerald-500/20' : 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40 text-gray-700 dark:text-gray-300'"
                        class="p-3 rounded-xl border cursor-pointer flex flex-col justify-between transition-all">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold">Harian (Custom)</span>
                            <input type="radio" name="tipe_perpanjangan" value="harian" x-model="tipe" class="text-emerald-600 focus:ring-emerald-500">
                        </div>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 font-mono">Pilih Jumlah Hari</p>
                        <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mt-1" x-text="formatRupiah(hargaHari) + ' / hari'"></p>
                    </label>
                </div>
            </div>

            {{-- Input Jumlah Hari (Tampil Jika Harian) --}}
            <div x-show="tipe === 'harian'" x-transition class="space-y-1.5 p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-100 dark:border-gray-700">
                <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300">
                    Berapa Hari Ingin Menambah Sewa?
                </label>
                <div class="flex items-center gap-2">
                    <input type="number" name="jumlah_hari" min="1" max="365" x-model.number="jumlahHari"
                        class="w-full px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-mono font-bold text-gray-900 dark:text-white">
                    <span class="text-xs text-gray-500 font-semibold whitespace-nowrap">Hari</span>
                </div>
            </div>

            {{-- Ringkasan Biaya & Periode Hasil Perpanjangan --}}
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 rounded-xl border border-emerald-100 dark:border-emerald-900/50 space-y-2">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-semibold">Total Yang Harus Ditransfer</p>
                        <p class="text-sm font-bold text-emerald-700 dark:text-emerald-300 font-mono" x-text="formatRupiah(totalCalculated)"></p>
                    </div>
                    <div class="text-right">
                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300"
                            x-text="tipe === 'bulanan' ? '+ 30 Hari' : '+ ' + (jumlahHari || 1) + ' Hari'"></span>
                    </div>
                </div>

                {{-- Tanggal Periode Baru Hasil Perpanjangan --}}
                <div class="pt-2 border-t border-emerald-200/60 dark:border-emerald-900/40 flex items-center justify-between text-xs">
                    <span class="text-gray-600 dark:text-gray-400 text-[11px] font-medium">Periode Perpanjangan Baru:</span>
                    <span class="font-bold font-mono text-emerald-800 dark:text-emerald-200" x-text="formattedStartDate + ' s/d ' + formattedEndDate"></span>
                </div>
            </div>

            {{-- Upload File Bukti --}}
            <label class="block w-full">
                <span class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Unggah Bukti Transfer</span>
                <input type="file" name="bukti_transfer" accept="image/*" required
                    class="block w-full text-xs text-gray-500 dark:text-gray-400 
                   file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 
                   file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 
                   dark:file:bg-emerald-900/30 dark:file:text-emerald-300
                   hover:file:bg-emerald-100 cursor-pointer">
            </label>

            <x-btn type="submit" size="sm" class="w-full">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Upload Bukti Transfer
            </x-btn>
            </form>
            @else
            {{-- MODE PEMBAYARAN AWAL PENDAFTARAN --}}
            <form action="{{ route('penghuni.pembayaran.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="pembayaran_id" value="{{ $p->id }}">
                <input type="hidden" name="tipe_perpanjangan" value="{{ $p->tipe_perpanjangan ?? 'bulanan' }}">
                <input type="hidden" name="jumlah_hari" value="{{ $p->jumlah_hari ?? 30 }}">

                <div class="p-3 bg-blue-50 dark:bg-blue-950/30 rounded-xl border border-blue-100 dark:border-blue-900/50 space-y-1">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] uppercase font-bold text-blue-700 dark:text-blue-300">
                            Pembayaran Awal Sewa Kos ({{ $p->jumlah_hari ?? 30 }} Hari Pertama)
                        </p>
                        <span class="px-2 py-0.5 rounded-md text-[9px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200">
                            {{ $p->periode_mulai ? $p->periode_mulai->format('d M Y') : '-' }} s/d {{ $p->periode_selesai ? $p->periode_selesai->format('d M Y') : '-' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-700 dark:text-gray-300 font-medium">
                        Sewa didaftarkan oleh Admin untuk periode pertama ({{ $p->jumlah_hari ?? 30 }} Hari). Silakan selesaikan pembayaran awal berikut:
                    </p>
                    <p class="text-sm font-bold text-blue-800 dark:text-blue-200 font-mono pt-1">
                        Total Yang Harus Ditransfer: Rp {{ number_format($p->jumlah, 0, ',', '.') }}
                    </p>
                </div>

                <label class="block w-full">
                    <span class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Unggah Bukti Transfer</span>
                    <input type="file" name="bukti_transfer" accept="image/*" required
                        class="block w-full text-xs text-gray-500 dark:text-gray-400 
                   file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 
                   file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 
                   dark:file:bg-emerald-900/30 dark:file:text-emerald-300
                   hover:file:bg-emerald-100 dark:hover:file:bg-emerald-800/50 cursor-pointer">
                </label>

                <x-btn type="submit" size="sm" class="w-full">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Upload Bukti Transfer (Pembayaran Awal)
                </x-btn>
            </form>
            @endif
            @else
            <div class="space-y-3">
                {{-- Status --}}
                <div class="flex items-center gap-2 p-2.5 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-xs text-amber-700 dark:text-amber-300 font-medium">Bukti sudah diupload, menunggu verifikasi admin</p>
                </div>

                {{-- LINK LIHAT BUKTI --}}
                <a href="{{ asset('storage/' . $p->bukti_transfer_url) }}" target="_blank"
                    class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700 active:bg-gray-100 dark:active:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Lihat Bukti Transfer</span>
                </a>
            </div>
            @endif
    </x-card>
    @endforeach
</div>
@endif

{{-- Riwayat Pembayaran --}}
<h2 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Riwayat Pembayaran</h2>

@php
$riwayat = $pembayarans->whereIn('status', ['terverifikasi', 'ditolak']);
@endphp

@if($riwayat->isEmpty())
<x-card>
    <x-empty-state message="Belum ada riwayat pembayaran." />
</x-card>
@else
<div class="space-y-3">
    @foreach($riwayat as $p)
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0
                        {{ $p->status === 'terverifikasi' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600' : 'bg-red-100 dark:bg-red-900/30 text-red-600' }}">
                @if($p->status === 'terverifikasi')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                @endif
            </div>
            <div>
                <p class="text-sm font-bold dark:text-white">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $p->periode_mulai->format('d M Y') }} - {{ $p->periode_selesai->format('d M Y') }}</p>
                @if($p->status === 'ditolak' && $p->catatan_verifikasi)
                <p class="text-xs text-red-500 mt-1">Catatan: {{ $p->catatan_verifikasi }}</p>
                @endif
            </div>
        </div>
        <x-badge type="{{ $p->status === 'terverifikasi' ? 'success' : 'danger' }}">
            {{ $p->status === 'terverifikasi' ? 'Lunas' : 'Ditolak' }}
        </x-badge>
    </div>
    @endforeach
</div>
@endif
@endsection