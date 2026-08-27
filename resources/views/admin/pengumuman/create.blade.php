@extends('layouts.app')

@php
$isSuperAdmin = request()->is('superadmin*');
$indexRoute = $isSuperAdmin ? route('superadmin.pengumuman.index') : route('admin.pengumuman.index');
$storeRoute = $isSuperAdmin ? route('superadmin.pengumuman.store') : route('admin.pengumuman.store');

$initTargetTipe = (!empty($selectedKamarIds) || isset($selectedKamarId)) ? 'kamar' : 'semua';
$initJudul = old('judul');
$initIsi = old('isi');

if (!$initJudul) {
if (isset($prefilledKamar)) {
$initJudul = "Pengingat Pembayaran Sewa Kamar " . $prefilledKamar->kode_kamar;
} elseif (!empty($selectedKamarIds)) {
$countKamar = count($selectedKamarIds);
$initJudul = "Penting: Pengingat Pembayaran Sewa Jatuh Tempo";
}
}

if (!$initIsi) {
if (isset($prefilledKamar)) {
$initIsi = "Halo Penghuni Kamar " . $prefilledKamar->kode_kamar . " (" . ($prefilledKamar->kos->nama ?? 'Kos') . "),\n\nIni adalah pengingat bahwa masa sewa kamar Anda telah mendekati / melewati tanggal jatuh tempo. Mohon segera melakukan konfirmasi pembayaran sewa melalui aplikasi. Terima kasih!";
} elseif (!empty($selectedKamarIds)) {
$initIsi = "Halo Penghuni Kos,\n\nPemberitahuan penting bagi Anda yang mendapati pembayaran sewa telah mendekati / melewati tanggal jatuh tempo. Mohon untuk segera melakukan perpanjangan sewa dan unggah bukti transfer pembayaran melalui menu Pembayaran di aplikasi.\n\nJika ada kendala pembayaran, silakan hubungi pengelola / admin kos. Terima kasih!";
}
}
@endphp

@section('title', 'Buat Pengumuman Baru')

@section('content')
<div class="space-y-4" x-data="{ 
    kategoriTipe: 'pembayaran', 
    targetTipe: '{{ $initTargetTipe }}', 
    onKategoriChange() {
        if (this.kategoriTipe === 'aturan' && this.targetTipe === 'kamar') {
            this.targetTipe = 'semua';
        }
    }
}">
    <x-page-header
        title="Form Pengumuman"
        subtitle="Kirim pengumuman jatuh tempo atau aturan baru"
        backUrl="{{ $indexRoute }}" />

    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm">
        <form action="{{ $storeRoute }}" method="POST" class="space-y-3.5">
            @csrf

            {{-- Jenis Pengumuman --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Jenis / Kategori Pengumuman</label>
                <select name="tipe" x-model="kategoriTipe" @change="onKategoriChange()" required class="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-900 dark:text-white">
                    <option value="pembayaran">Jatuh Tempo Pembayaran</option>
                    <option value="aturan">Aturan Kos Baru</option>
                    <option value="info">Informasi Umum</option>
                </select>
                @error('tipe') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Saluran Media Pengiriman --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Saluran Media Pengiriman</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                        <input type="radio" name="channel" value="web" checked class="sr-only peer">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400 flex items-center gap-1">
                            🌐 Web App
                        </span>
                    </label>

                    <label class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                        <input type="radio" name="channel" value="whatsapp" class="sr-only peer">
                        <span class="[&>svg]:h-4 [&>svg]:w-4 text-xs font-bold text-gray-700 dark:text-gray-300 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400 flex items-center gap-1">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor"
                                viewBox="0 0 448 512">
                                <path
                                    d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                            </svg>
                            WhatsApp
                        </span>
                    </label>

                    <label class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                        <input type="radio" name="channel" value="keduanya" class="sr-only peer">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400 flex items-center gap-1">
                            🔔 Keduanya
                        </span>
                    </label>
                </div>
            </div>

            {{-- Judul --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Judul Pengumuman</label>
                <input type="text" name="judul" value="{{ $initJudul }}" required placeholder="Contoh: Pengingat Pembayaran Sewa Bulan Ini"
                    class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">
                @error('judul') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Isi --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Isi Pengumuman / Pesan</label>
                <textarea name="isi" rows="4" required placeholder="Tuliskan pesan lengkap pengumuman di sini..."
                    class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">{{ $initIsi }}</textarea>
                @error('isi') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Target Penerima --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Target Sasaran Penerima</label>
                <div class="grid gap-2 mb-2" :class="kategoriTipe === 'aturan' ? 'grid-cols-2' : 'grid-cols-3'">
                    <label class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                        <input type="radio" name="target_tipe" value="semua" x-model="targetTipe" class="sr-only peer">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400">Semua User</span>
                    </label>

                    <label class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                        <input type="radio" name="target_tipe" value="kos" x-model="targetTipe" class="sr-only peer">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400">Per Kos</span>
                    </label>

                    <label x-show="kategoriTipe !== 'aturan'" x-transition class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                        <input type="radio" name="target_tipe" value="kamar" x-model="targetTipe" class="sr-only peer">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400">Per Kamar</span>
                    </label>
                </div>
                <p x-show="kategoriTipe === 'aturan'" class="text-[11px] text-amber-600 dark:text-amber-400 italic">
                    * Pengumuman Aturan Kos Baru berlaku untuk tingkat gedung kos atau seluruh pengguna.
                </p>
            </div>

            {{-- Target List Kos --}}
            <div x-show="targetTipe === 'kos'" x-transition class="space-y-2">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Pilih Kos Target</label>
                <div class="max-h-44 overflow-y-auto space-y-1.5 p-2 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700">
                    @foreach($kosList as $k)
                    <label class="flex items-center gap-2 text-xs font-medium text-gray-800 dark:text-gray-200 p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg cursor-pointer">
                        <input type="checkbox" name="target_ids[]" value="{{ $k->id }}" class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span>{{ $k->nama }} (Mitra: {{ $k->mitra->nama ?? '-' }})</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Target List Kamar --}}
            @php
            $allKamars = \App\Models\Kamar::with('kos')->get();
            @endphp
            <div x-show="targetTipe === 'kamar' && kategoriTipe !== 'aturan'" x-transition class="space-y-2">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Pilih Kode Kamar Target</label>
                <div class="max-h-44 overflow-y-auto space-y-1.5 p-2 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700">
                    @foreach($allKamars as $km)
                    @php
                    $isChecked = in_array($km->id, $selectedKamarIds ?? []);
                    @endphp
                    <label class="flex items-center gap-2 text-xs font-medium text-gray-800 dark:text-gray-200 p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg cursor-pointer">
                        <input type="checkbox" name="target_ids[]" value="{{ $km->id }}" {{ $isChecked ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span>Kamar {{ $km->kode_kamar }} · {{ $km->kos->nama ?? '-' }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="pt-2">
                <x-btn type="submit" variant="primary" size="md" class="w-full">
                    Kirim & Broadcast Pengumuman
                </x-btn>
            </div>
        </form>
    </div>
</div>
@endsection