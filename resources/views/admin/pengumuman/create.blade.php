@extends('layouts.app')

@section('title', 'Buat Pengumuman Baru')

@section('content')
<div class="space-y-4" x-data="{ 
    kategoriTipe: 'pembayaran', 
    targetTipe: 'semua', 
    onKategoriChange() {
        if (this.kategoriTipe === 'aturan' && this.targetTipe === 'kamar') {
            this.targetTipe = 'semua';
        }
    }
}">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Form Pengumuman</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kirim pengumuman jatuh tempo atau aturan baru</p>
        </div>
        <x-btn href="{{ route('admin.pengumuman.index') }}" variant="secondary" size="sm" class="!min-h-[36px] !py-1 text-xs">
            &larr; Kembali
        </x-btn>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm">
        <form action="{{ route('admin.pengumuman.store') }}" method="POST" class="space-y-3.5">
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
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400 flex items-center gap-1">
                            💬 WhatsApp
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
                <input type="text" name="judul" value="{{ old('judul') }}" required placeholder="Contoh: Pengingat Pembayaran Sewa Bulan Ini" 
                       class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">
                @error('judul') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Isi --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Isi Pengumuman / Pesan</label>
                <textarea name="isi" rows="4" required placeholder="Tuliskan pesan lengkap pengumuman di sini..." 
                          class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-emerald-500 focus:outline-none">{{ old('isi') }}</textarea>
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
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Pilih Properti Kos Target</label>
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
                        <label class="flex items-center gap-2 text-xs font-medium text-gray-800 dark:text-gray-200 p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg cursor-pointer">
                            <input type="checkbox" name="target_ids[]" value="{{ $km->id }}" class="rounded text-emerald-600 focus:ring-emerald-500">
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
