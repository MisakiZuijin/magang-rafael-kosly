@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
<div class="mb-5">
    <x-page-header title="Pembayaran" subtitle="Kelola bukti transfer & riwayat pembayaran" backUrl="{{ route('penghuni.dashboard') }}" />
</div>

@if($rekening)
{{-- Info Rekening --}}
<x-card class="mb-4 bg-gradient-to-br from-emerald-500 to-emerald-600 border-0 text-white">
    <p class="text-[11px] font-bold text-emerald-100 uppercase tracking-wider mb-3">Rekening Pembayaran</p>
    <div class="flex items-center justify-between mb-3">
        <div>
            <p class="text-lg font-bold">{{ $rekening->bank }}</p>
            <div class="flex items-center gap-2 mt-0.5">
                <p class="text-base font-mono text-emerald-100 font-bold tracking-wider">{{ $rekening->no_rekening }}</p>
                <button type="button"
                    x-data="{ copied: false }"
                    @click="navigator.clipboard.writeText('{{ $rekening->no_rekening }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg bg-white/20 hover:bg-white/30 text-white backdrop-blur transition-all active:scale-95"
                    title="Salin Nomor Rekening">
                    <svg x-show="!copied" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <svg x-show="copied" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span x-text="copied ? 'Tersalin!' : 'Salin'">Salin</span>
                </button>
            </div>
        </div>
        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur flex-shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
        </div>
    </div>
    <p class="text-xs text-emerald-100">A/n {{ $rekening->nama_pemilik_rekening }}</p>
</x-card>
@endif

{{-- Yang Harus Dibayar (Pending / Belum Upload) --}}
<h2 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Menunggu Pembayaran</h2>

@if($roommateFullPaid)
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
@elseif($roommateFullPending)
<x-card class="mb-5 border-l-4 border-l-purple-500 bg-purple-50/50 dark:bg-purple-950/20">
    <div class="space-y-3">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center text-purple-600 flex-shrink-0 mt-0.5">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-bold text-purple-900 dark:text-purple-200">Pelunasan 1 Kamar Menunggu Verifikasi Admin</h3>
                <p class="text-xs text-purple-800 dark:text-purple-300 mt-1 leading-relaxed">
                    Bukti transfer sewa 1 kamar penuh sebesar <strong>Rp {{ number_format($roommatePendingJumlah, 0, ',', '.') }}</strong> telah diunggah oleh <strong>{{ $roommatePendingName }}</strong> pada <strong>{{ $roommatePendingTime }}</strong>.
                </p>
                <p class="text-[11px] text-purple-600 dark:text-purple-400 mt-1 font-medium">
                    💡 Anda tidak perlu mengunggah bukti transfer. Setelah pembayaran diverifikasi oleh Admin, status sewa Anda akan otomatis menjadi lunas.
                </p>
            </div>
        </div>

        @if($roommatePendingBuktiUrl)
        <div class="pt-2 border-t border-purple-100 dark:border-purple-900/40">
            <a href="{{ asset('storage/' . $roommatePendingBuktiUrl) }}" target="_blank"
                class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-800 text-purple-700 dark:text-purple-300 rounded-xl border border-purple-200 dark:border-purple-700 text-xs font-bold hover:bg-purple-50 dark:hover:bg-purple-950/40 transition-all shadow-xs">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Lihat Bukti Transfer Teman Sekamar</span>
            </a>
        </div>
        @endif
    </div>
</x-card>
@else
@php
$menunggu = $pembayarans->where('status', 'pending');
if (!empty($roommateUnpaidInitial) && !empty($myVerifiedInitial)) {
    // Jika diri sendiri sudah bayar awal tapi rekan sekamar belum, sembunyikan tagihan perpanjangan/pembayaran ulang
    $menunggu = collect();
}
@endphp

@if(!empty($roommateUnpaidInitial) && !empty($myVerifiedInitial))
<x-card class="mb-5 border-l-4 border-l-amber-500 bg-amber-50/50 dark:bg-amber-950/20">
    <div class="space-y-2 py-1">
        <div class="flex items-center gap-2">
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
            </span>
            <h3 class="text-sm font-bold text-amber-900 dark:text-amber-200">Pembayaran Awal Anda (50%) Terverifikasi</h3>
        </div>
        <p class="text-xs text-amber-800 dark:text-amber-300 leading-relaxed">
            Pembayaran biaya awal bagian Anda sebesar 50% telah lunas dan diverifikasi oleh Admin. Saat ini sistem sedang menunggu rekan sekamar Anda (<strong>{{ $roommateUnpaidName }}</strong>) untuk menyelesaikan pembayaran bagiannya.
        </p>
        <p class="text-[11px] text-amber-700 dark:text-amber-400 font-medium">
            💡 Kamar dan form perpanjangan sewa akan otomatis aktif setelah kedua penghuni menyelesaikan pembayaran awal masing-masing.
        </p>
    </div>
</x-card>
@endif

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
    @php
    $kamar = $p->penghuniKamar->kamar ?? null;
    $hargaBulan = $kamar->harga_per_bulan ?? 0;
    $hargaMinggu = ($kamar->harga_per_minggu ?? 0) > 0 ? $kamar->harga_per_minggu : round($hargaBulan / 4);
    $hargaHari = ($kamar->harga_per_hari ?? 0) > 0 ? $kamar->harga_per_hari : round($hargaBulan / 30);

    $hasPreviousVerified = $pembayarans->where('status', 'terverifikasi')->isNotEmpty();
    $tanggalKeluarRef = $p->penghuniKamar->tanggal_keluar
    ? \Carbon\Carbon::parse($p->penghuniKamar->tanggal_keluar)
    : \Carbon\Carbon::parse($p->periode_selesai);
    $sisaHariRef = (int) \Carbon\Carbon::now()->startOfDay()->diffInDays($tanggalKeluarRef->startOfDay(), false);

    $isExtensionMode = $hasPreviousVerified;

        $isHarianAwal=($p->tipe_perpanjangan === 'harian') || ($p->penghuniKamar && $p->penghuniKamar->durasi === 'harian');
        $isMingguanAwal = ($p->tipe_perpanjangan === 'mingguan') || ($p->penghuniKamar && $p->penghuniKamar->durasi === 'mingguan');
        $fullRoomPriceAwal = $isHarianAwal
        ? (($p->jumlah_hari ?: 1) * $hargaHari)
        : ($isMingguanAwal ? $hargaMinggu : $hargaBulan);

        $activeCount = ($p->penghuniKamar && $p->penghuniKamar->kamar_id)
        ? \App\Models\PenghuniKamar::where('kamar_id', $p->penghuniKamar->kamar_id)->where('status', 'aktif')->count()
        : 2;
        if ($activeCount < 1) $activeCount=1;
            @endphp

            <div class="space-y-0"
            x-data="{
            isExtension: {{ $isExtensionMode ? 'true' : 'false' }},
            isHarianAwal: {{ $isHarianAwal ? 'true' : 'false' }},
            tipe: '{{ $onlyHalfOption ? $roommateHalfTipe : ($isExtensionMode ? ($p->penghuniKamar->durasi === 'harian' ? 'harian' : ($p->penghuniKamar->durasi === 'mingguan' ? 'mingguan' : ($p->tipe_perpanjangan ?? 'bulanan'))) : ($isHarianAwal ? 'harian' : ($isMingguanAwal ? 'mingguan' : 'bulanan'))) }}',
            activeCount: {{ $activeCount }},
            onlyHalf: {{ $onlyHalfOption ? 'true' : 'false' }},
            porsiBayar: {{ $onlyHalfOption ? 50 : (($isKamarBerbagi && $activeCount <= 2) ? ($p->porsi_bayar ?? 100) : 100) }},
            jumlahHari: {{ $onlyHalfOption ? $roommateHalfDays : ($p->jumlah_hari && $p->jumlah_hari != 30 ? $p->jumlah_hari : 1) }},
            hargaBulan: {{ $hargaBulan }},
            hargaMinggu: {{ $hargaMinggu }},
            hargaHari: {{ $hargaHari }},
            fullPriceAwal: {{ $fullRoomPriceAwal }},
            baseDateStr: '{{ $tanggalKeluarRef->format('Y-m-d') }}',

            get fullPeriodPrice() {
                if (!this.isExtension) {
                    return this.fullPriceAwal;
                }
                if (this.tipe === 'harian') {
                    return (this.jumlahHari || 1) * this.hargaHari;
                }
                if (this.tipe === 'mingguan') {
                    return this.hargaMinggu;
                }
                return this.hargaBulan;
            },
            get addedDays() {
                if (this.tipe === 'harian') return parseInt(this.jumlahHari || 1);
                if (this.tipe === 'mingguan') return 7;
                return 30;
            },
            get totalCalculated() {
                return (this.porsiBayar == 50 && this.activeCount <= 2) ? Math.round(this.fullPeriodPrice / 2) : this.fullPeriodPrice;
            },
            get formattedStartDate() {
                if (!this.baseDateStr) return '-';
                const parts = this.baseDateStr.split('-');
                if (parts.length < 3) return this.baseDateStr;
                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            },
            get formattedEndDate() {
                if (!this.baseDateStr) return '-';
                const parts = this.baseDateStr.split('-');
                if (parts.length < 3) return '-';
                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                if (this.tipe === 'bulanan') {
                    d.setMonth(d.getMonth() + 1);
                } else if (this.tipe === 'mingguan') {
                    d.setDate(d.getDate() + 7);
                } else {
                    d.setDate(d.getDate() + parseInt(this.jumlahHari || 1));
                }
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            },
            formatRupiah(num) {
                return 'Rp ' + (num || 0).toLocaleString('id-ID');
            },
            fileNameExt: '',
            fileNameAwal: '',
            handleFileExt(e) {
                const file = e.target.files[0];
                this.fileNameExt = file ? file.name : '';
            },
            handleFileAwal(e) {
                const file = e.target.files[0];
                this.fileNameAwal = file ? file.name : '';
            }
        }">
            <x-card class="border-l-4 border-l-amber-400">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        @if($p->bukti_transfer_url)
                        <p class="text-sm font-bold dark:text-white">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Periode {{ $p->periode_mulai ? $p->periode_mulai->format('d M Y') : '-' }} s/d {{ $p->periode_selesai ? $p->periode_selesai->format('d M Y') : '-' }}
                        </p>
                        @else
                        <p class="text-sm font-bold dark:text-white" x-text="formatRupiah(totalCalculated)"></p>
                        @if($isExtensionMode)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="'Periode Perpanjangan: ' + formattedStartDate + ' - ' + formattedEndDate"></p>
                        @else
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Periode {{ $p->periode_mulai ? $p->periode_mulai->format('d M Y') : '-' }} - {{ $p->periode_selesai ? $p->periode_selesai->format('d M Y') : '-' }}</p>
                        @endif
                        @endif
                    </div>
                    <x-badge type="warning">Menunggu</x-badge>
                </div>

                @if(!$p->bukti_transfer_url)
                @if($isExtensionMode)
                {{-- MODE PERPANJANGAN SEWA (SISA <= 7 HARI ATAU PERPANJANGAN SIKLUS BERIKUTNYA) --}}
                <form action="{{ route('penghuni.pembayaran.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="pembayaran_id" value="{{ $p->id }}">

                    @if($isKamarBerbagi)
                    @if($activeCount <= 2)
                        @if($onlyHalfOption)
                        <div class="p-3 bg-purple-50 dark:bg-purple-950/30 rounded-xl border border-purple-200 dark:border-purple-800/60 space-y-1.5 mb-2">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-purple-900 dark:text-purple-200">
                                <span>👥 Info: Teman Sekamar Memilih Tarif 1 Orang (50%)</span>
                            </div>
                            <p class="text-[11px] text-purple-700 dark:text-purple-300 leading-relaxed">
                                Teman sekamar Anda (<strong>{{ $roommateHalfName }}</strong>) telah mengunggah pembayaran <strong>{{ $roommateHalfTipe === 'harian' ? "Sewa Harian ({$roommateHalfDays} Hari)" : ($roommateHalfTipe === 'mingguan' ? 'Sewa 1 Minggu (7 Hari)' : 'Sewa 1 Bulan (30 Hari)') }}</strong> sebesar <strong>Rp {{ number_format($roommateHalfJumlah, 0, ',', '.') }}</strong>. Pilihan durasi Anda otomatis diselaraskan.
                            </p>
                        </div>
                        @endif

                        {{-- Porsi Pembayaran Kamar Berbagi (2 Orang) --}}
                        <div class="space-y-2">
                            <label class="block text-[11px] font-bold text-purple-800 dark:text-purple-300 uppercase tracking-wider flex items-center gap-1">
                                👥 Skema Pembayaran Kamar Berbagi (2 Orang)
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label :class="porsiBayar == 100 ? 'border-purple-500 bg-purple-50/70 dark:bg-purple-950/40 text-purple-900 dark:text-purple-200 ring-2 ring-purple-500/20' : 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40 text-gray-700 dark:text-gray-300'"
                                    class="p-3 rounded-xl border cursor-pointer flex flex-col justify-between transition-all">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold">Bayar Full (1 Kamar)</span>
                                        <input type="radio" name="porsi_bayar" value="100" x-model.number="porsiBayar" class="text-purple-600 focus:ring-purple-500">
                                    </div>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 font-mono">Pelunasan Seluruh Kamar</p>
                                    <p class="text-xs font-bold text-purple-600 dark:text-purple-400 mt-1" x-text="formatRupiah(fullPeriodPrice)"></p>
                                </label>

                                <label :class="porsiBayar == 50 ? 'border-purple-500 bg-purple-50/70 dark:bg-purple-950/40 text-purple-900 dark:text-purple-200 ring-2 ring-purple-500/20' : 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40 text-gray-700 dark:text-gray-300'"
                                    class="p-3 rounded-xl border cursor-pointer flex flex-col justify-between transition-all">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold">Tarif 1 Orang</span>
                                        <input type="radio" name="porsi_bayar" value="50" x-model.number="porsiBayar" class="text-purple-600 focus:ring-purple-500">
                                    </div>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 font-mono">Separuh Harga Kamar (50%)</p>
                                    <p class="text-xs font-bold text-purple-600 dark:text-purple-400 mt-1" x-text="formatRupiah(Math.round(fullPeriodPrice / 2))"></p>
                                </label>
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="porsi_bayar" value="100">
                        <div class="p-3 bg-purple-50 dark:bg-purple-950/30 rounded-xl border border-purple-200 dark:border-purple-800/60 space-y-1">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-purple-900 dark:text-purple-200">
                                <span>👥 Pembayaran Kamar Berbagi ({{ $activeCount }} Penghuni - Pelunasan 1 Kamar)</span>
                            </div>
                            <p class="text-[11px] text-purple-700 dark:text-purple-300">
                                Pembayaran sewa kamar berbagi ({{ $activeCount }} orang) ditanggung penuh oleh 1 perwakilan kamar. Pembagian biaya dilakukan secara internal di antara Anda dan teman sekamar.
                            </p>
                        </div>
                    @endif
                    @else
                        <input type="hidden" name="porsi_bayar" value="100">
                    @endif

                        {{-- Opsi Tipe Perpanjangan --}}
                        <div class="space-y-2">
                            <label class="block text-[11px] font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pilih Skema Perpanjangan Sewa (Sisa {{ $sisaHariRef }} Hari)
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <label :class="tipe === 'bulanan' ? 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 ring-2 ring-emerald-500/20' : 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40 text-gray-700 dark:text-gray-300'"
                                    class="p-2.5 rounded-xl border cursor-pointer flex flex-col justify-between transition-all">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold">1 Bulan</span>
                                        <input type="radio" name="tipe_perpanjangan" value="bulanan" x-model="tipe" class="text-emerald-600 focus:ring-emerald-500">
                                    </div>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 font-mono">+30 Hari</p>
                                    <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mt-1" x-text="formatRupiah((porsiBayar == 50 && activeCount <= 2) ? Math.round(hargaBulan / 2) : hargaBulan)"></p>
                                </label>

                                <label :class="tipe === 'mingguan' ? 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 ring-2 ring-emerald-500/20' : 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40 text-gray-700 dark:text-gray-300'"
                                    class="p-2.5 rounded-xl border cursor-pointer flex flex-col justify-between transition-all">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold">1 Minggu</span>
                                        <input type="radio" name="tipe_perpanjangan" value="mingguan" x-model="tipe" class="text-emerald-600 focus:ring-emerald-500">
                                    </div>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 font-mono">+7 Hari</p>
                                    <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mt-1" x-text="formatRupiah((porsiBayar == 50 && activeCount <= 2) ? Math.round(hargaMinggu / 2) : hargaMinggu)"></p>
                                </label>

                                <label :class="tipe === 'harian' ? 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 ring-2 ring-emerald-500/20' : 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40 text-gray-700 dark:text-gray-300'"
                                    class="p-2.5 rounded-xl border cursor-pointer flex flex-col justify-between transition-all">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold">Harian</span>
                                        <input type="radio" name="tipe_perpanjangan" value="harian" x-model="tipe" class="text-emerald-600 focus:ring-emerald-500">
                                    </div>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 font-mono">Custom Hari</p>
                                    <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mt-1" x-text="formatRupiah((porsiBayar == 50 && activeCount <= 2) ? Math.round(hargaHari / 2) : hargaHari) + ' / hari'"></p>
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
                                        x-text="tipe === 'bulanan' ? '+ 30 Hari' : (tipe === 'mingguan' ? '+ 7 Hari' : '+ ' + (jumlahHari || 1) + ' Hari')"></span>
                                </div>
                            </div>

                            {{-- Tanggal Periode Baru Hasil Perpanjangan --}}
                            <div class="pt-2 border-t border-emerald-200/60 dark:border-emerald-900/40 flex items-center justify-between text-xs">
                                <span class="text-gray-600 dark:text-gray-400 text-[11px] font-medium">Periode Perpanjangan Baru:</span>
                                <span class="font-bold font-mono text-emerald-800 dark:text-emerald-200" x-text="formattedStartDate + ' s/d ' + formattedEndDate"></span>
                            </div>
                        </div>

                        {{-- Upload File Bukti --}}
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Unggah Foto Resi / Bukti Transfer</span>
                                <span class="text-red-500">*</span>
                            </label>

                            <label class="group relative flex flex-col items-center justify-center w-full py-4 px-3 border-2 border-dashed border-emerald-400/80 dark:border-emerald-700/80 bg-emerald-50/50 dark:bg-emerald-950/30 hover:bg-emerald-100/50 dark:hover:bg-emerald-900/40 rounded-2xl cursor-pointer transition-all text-center">
                                <input type="file" name="bukti_transfer" accept="image/*" required
                                    @change="handleFileExt($event)"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                                <div class="space-y-1 flex flex-col items-center">
                                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-300 rounded-full flex items-center justify-center shadow-xs group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>

                                    <p class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                        Klik di sini untuk memilih foto bukti transfer
                                    </p>

                                    <p class="text-[11px] font-semibold font-mono"
                                        :class="fileNameExt ? 'text-emerald-700 dark:text-emerald-300' : 'text-gray-400 dark:text-gray-500'"
                                        x-text="fileNameExt ? '📷 Foto dipilih: ' + fileNameExt : 'No file chosen'">
                                    </p>
                                </div>
                            </label>
                        </div>

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
                    <input type="hidden" name="tipe_perpanjangan" value="{{ $p->tipe_perpanjangan ?? ($isHarianAwal ? 'harian' : ($isMingguanAwal ? 'mingguan' : 'bulanan')) }}">
                    <input type="hidden" name="jumlah_hari" value="{{ $p->jumlah_hari ?? ($isHarianAwal ? ($p->jumlah_hari ?: 1) : ($isMingguanAwal ? 7 : 30)) }}">

                    @if($isKamarBerbagi)
                    @if($activeCount <= 2)
                        @if($onlyHalfOption)
                        <input type="hidden" name="porsi_bayar" value="50">
                        <input type="hidden" name="tipe_perpanjangan" value="{{ $roommateHalfTipe }}">
                        <input type="hidden" name="jumlah_hari" value="{{ $roommateHalfDays }}">
                        <div class="p-3 bg-purple-50 dark:bg-purple-950/30 rounded-xl border border-purple-100 dark:border-purple-900/50 space-y-1.5">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-purple-900 dark:text-purple-200">
                                <span>👥 Skema Pembayaran: Tarif 1 Orang (50%)</span>
                            </div>
                            <p class="text-[11px] text-purple-700 dark:text-purple-300 leading-relaxed">
                                Teman sekamar Anda (<strong>{{ $roommateHalfName }}</strong>) telah membayar <strong>{{ $roommateHalfTipe === 'harian' ? "Sewa Harian ({$roommateHalfDays} Hari)" : ($roommateHalfTipe === 'mingguan' ? 'Sewa 1 Minggu' : 'Sewa 1 Bulan') }}</strong>. Tagihan Anda diselaraskan menjadi <strong>50% (Tarif 1 Orang)</strong> senilai <strong>Rp {{ number_format($roommateHalfJumlah, 0, ',', '.') }}</strong>.
                            </p>
                        </div>
                        @else
                        <div class="p-3 bg-purple-50 dark:bg-purple-950/30 rounded-xl border border-purple-100 dark:border-purple-900/50 space-y-2">
                            <label class="block text-[11px] font-bold text-purple-800 dark:text-purple-300 uppercase tracking-wider">
                                👥 Skema Pembayaran Kamar Berbagi (2 Penghuni)
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label :class="porsiBayar == 100 ? 'border-purple-500 bg-white dark:bg-gray-800 ring-2 ring-purple-500/20' : 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40'"
                                    class="p-2.5 rounded-xl border cursor-pointer flex flex-col justify-between transition-all">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold text-gray-900 dark:text-white">Bayar Full (1 Kamar)</span>
                                        <input type="radio" name="porsi_bayar" value="100" x-model.number="porsiBayar" class="text-purple-600 focus:ring-purple-500">
                                    </div>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">Pelunasan Seluruh Kamar</p>
                                    <p class="text-xs font-bold text-purple-600 dark:text-purple-400 mt-1" x-text="formatRupiah(fullPriceAwal)"></p>
                                </label>

                                <label :class="porsiBayar == 50 ? 'border-purple-500 bg-white dark:bg-gray-800 ring-2 ring-purple-500/20' : 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40'"
                                    class="p-2.5 rounded-xl border cursor-pointer flex flex-col justify-between transition-all">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold text-gray-900 dark:text-white">Tarif 1 Orang</span>
                                        <input type="radio" name="porsi_bayar" value="50" x-model.number="porsiBayar" class="text-purple-600 focus:ring-purple-500">
                                    </div>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">Separuh Harga Kamar (50%)</p>
                                    <p class="text-xs font-bold text-purple-600 dark:text-purple-400 mt-1" x-text="formatRupiah(Math.round(fullPriceAwal / 2))"></p>
                                </label>
                            </div>
                        </div>
                        @endif
                        @else
                        <input type="hidden" name="porsi_bayar" value="100">
                        <div class="p-3 bg-purple-50 dark:bg-purple-950/30 rounded-xl border border-purple-200 dark:border-purple-800/60 space-y-1">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-purple-900 dark:text-purple-200">
                                <span>👥 Pembayaran Kamar Berbagi (3 Penghuni - Pelunasan 1 Kamar)</span>
                            </div>
                            <p class="text-[11px] text-purple-700 dark:text-purple-300">
                                Pembayaran sewa kamar berbagi (3 orang) ditanggung lunas oleh 1 perwakilan kamar. Pembagian biaya dilakukan secara internal.
                            </p>
                        </div>
                        @endif
                        @else
                        <input type="hidden" name="porsi_bayar" value="100">
                        @endif

                        <div class="p-3 bg-blue-50 dark:bg-blue-950/30 rounded-xl border border-blue-100 dark:border-blue-900/50 space-y-1">
                            <div class="grid grid-cols-1 gap-2">
                                <p class="text-[10px] uppercase font-bold text-blue-700 dark:text-blue-300">
                                    Pembayaran Awal Sewa Kos ({{ $p->jumlah_hari ?? 30 }} Hari {{ $isHarianAwal ? 'Harian' : 'Pertama' }})
                                </p>
                                <span class="px-2 py-0.5 text-center w-[150px] rounded-md text-[9px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200">
                                    {{ $p->periode_mulai ? $p->periode_mulai->format('d M Y') : '-' }} s/d {{ $p->periode_selesai ? $p->periode_selesai->format('d M Y') : '-' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-700 dark:text-gray-300 font-medium">
                                Sewa didaftarkan oleh Admin untuk periode pertama ({{ $p->jumlah_hari ?? 30 }} Hari {{ $isHarianAwal ? 'Harian' : '' }}). Silakan selesaikan pembayaran awal berikut:
                            </p>
                            <p class="text-sm font-bold text-blue-800 dark:text-blue-200 font-mono pt-1" x-text="'Total Yang Harus Ditransfer: ' + formatRupiah(totalCalculated)">
                            </p>
                        </div>

                        {{-- Upload File Bukti --}}
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Unggah Foto Resi / Bukti Transfer</span>
                                <span class="text-red-500">*</span>
                            </label>

                            <label class="group relative flex flex-col items-center justify-center w-full py-4 px-3 border-2 border-dashed border-emerald-400/80 dark:border-emerald-700/80 bg-emerald-50/50 dark:bg-emerald-950/30 hover:bg-emerald-100/50 dark:hover:bg-emerald-900/40 rounded-2xl cursor-pointer transition-all text-center">
                                <input type="file" name="bukti_transfer" accept="image/*" required
                                    @change="handleFileAwal($event)"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                                <div class="space-y-1 flex flex-col items-center">
                                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-300 rounded-full flex items-center justify-center shadow-xs group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>

                                    <p class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                        Klik di sini untuk memilih foto bukti transfer
                                    </p>

                                    <p class="text-[11px] font-semibold font-mono"
                                        :class="fileNameAwal ? 'text-emerald-700 dark:text-emerald-300' : 'text-gray-400 dark:text-gray-500'"
                                        x-text="fileNameAwal ? '📷 Foto dipilih: ' + fileNameAwal : 'No file chosen'">
                                    </p>
                                </div>
                            </label>
                        </div>

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
                    {{-- Status Menunggu Verifikasi --}}
                    @php
                    if ($isKamarBerbagi) {
                        if ($activeCount >= 3) {
                            $porsiText = 'Pelunasan Kamar 3 Orang';
                        } elseif ($p->porsi_bayar == 50) {
                            $porsiText = 'Tarif 1 Orang (50%)';
                        } else {
                            $porsiText = 'Pelunasan 1 Kamar (2 Penghuni)';
                        }
                    } else {
                        $porsiText = 'Kamar Standar';
                    }
                    @endphp

                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl space-y-1">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-xs text-amber-700 dark:text-amber-300 font-medium">Bukti transfer sebesar <strong>Rp {{ number_format($p->jumlah, 0, ',', '.') }}</strong> telah diupload, menunggu verifikasi admin ({{ $porsiText }}).</p>
                        </div>
                        @if($isKamarBerbagi && $p->porsi_bayar == 100)
                        <p class="text-[11px] text-amber-700/80 dark:text-amber-400/90 pl-6 font-medium">
                            💡 Anda mengunggah bukti pelunasan untuk seluruh kamar. Setelah diverifikasi admin, seluruh teman sekamar akan otomatis berstatus lunas.
                        </p>
                        @endif
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
</div>
@endforeach
</div>
@endif
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
    @php
    $isCoveredByRoommate = $p->catatan_verifikasi && str_contains($p->catatan_verifikasi, 'Lunas (Dibayar');
    $uploaderName = $isCoveredByRoommate ? trim(preg_replace('/^Lunas \(Dibayar (?:Full|Tarif 2 Orang|Tarif 3 Orang|Tarif 1 Kamar) oleh (.+)\)$/', '$1', $p->catatan_verifikasi)) : null;
    $tglTampil = $p->tanggal_bayar ? $p->tanggal_bayar->format('d M Y') : null;
    $waktuVerifTolak = $p->tanggal_verifikasi ? $p->tanggal_verifikasi->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB' : ($p->updated_at ? $p->updated_at->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB' : null);
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-6 gap-2 bg-white dark:bg-gray-900 rounded-2xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm">
        <div class="col-span-5 flex items-center gap-3">
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
                <div class="grid grid-cols-1 gap-1 mb-1">
                    <p class="text-sm font-bold dark:text-white">
                        Rp {{ number_format($p->jumlah, 0, ',', '.') }}
                    </p>
                    @if($isCoveredByRoommate)
                    <span class="text-[10px] text-center font-semibold text-blue-600 bg-blue-50 dark:bg-blue-950/40 px-1.5 py-0.5 rounded">Dibayar oleh {{ $uploaderName }}</span>
                    @elseif($isKamarBerbagi)
                    @php
                    $badgeInfo = $p->getTarifBadgeInfo();
                    @endphp
                    <span class="text-[10px] text-center font-semibold {{ $badgeInfo['class'] }} px-1.5 py-0.5 rounded">{{ $badgeInfo['text'] }}</span>
                    @endif
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400">Periode: {{ $p->periode_mulai ? $p->periode_mulai->format('d M Y') : '-' }} s/d {{ $p->periode_selesai ? $p->periode_selesai->format('d M Y') : '-' }}</p>

                @if($isCoveredByRoommate)
                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium mt-1">
                    👤 Pembayaran diwakilkan oleh <strong>{{ $uploaderName }}</strong> {{ $waktuVerifTolak ? "(Diverifikasi: {$waktuVerifTolak})" : '' }}
                </p>
                @else
                <div class="space-y-0.5 mt-1 text-xs">
                    @if($p->status === 'terverifikasi')
                    <p class="text-emerald-700 dark:text-emerald-400 font-medium">
                        ✓ Diverifikasi pada: <strong>{{ $waktuVerifTolak ?: '-' }}</strong>
                    </p>
                    @if($tglTampil)
                    <p class="text-gray-400 dark:text-gray-500 font-mono text-[11px]">
                        📅 Tanggal Bayar: {{ $tglTampil }}
                    </p>
                    @endif
                    @else
                    <p class="text-red-600 dark:text-red-400 font-medium">
                        ✕ Ditolak pada: <strong>{{ $waktuVerifTolak ?: '-' }}</strong>
                    </p>
                    @endif
                    @if($p->catatan_verifikasi)
                    <p class="text-xs {{ $p->status === 'ditolak' ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400 font-medium' }}">
                        {{ $p->catatan_verifikasi }}
                    </p>
                    @endif
                </div>
                @endif
            </div>
        </div>
        <div class="flex items-center justify-end">
            <x-badge type="{{ $p->status === 'terverifikasi' ? 'success' : 'danger' }}">
                {{ $p->status === 'terverifikasi' ? 'Lunas' : 'Ditolak' }}
            </x-badge>
        </div>

        <div class="grid col-start-2 col-span-4 items-center">
            @if($p->status === 'terverifikasi')
            <a href="{{ route('pembayaran.nota', $p->kode_invoice ?? $p->id) }}" class="px-2.5 py-1 text-center bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs font-bold transition-all gap-1">
                <span>Download Nota</span>
            </a>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection