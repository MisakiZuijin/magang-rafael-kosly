@props(['logs' => []])

@if(empty($logs) || $logs->isEmpty())
    <p class="text-xs text-gray-400 text-center py-6">Belum ada catatan log aktivitas terrekam.</p>
@else
    <div class="space-y-2 max-h-[420px] overflow-y-auto no-scrollbar pr-0.5">
        @foreach($logs as $log)
        @php
            $roleColor = match($log->user->role ?? '') {
                'super_admin' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                'admin' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                'mitra' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                'penghuni' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
            };
            $aksiBadge = match(strtolower($log->aksi)) {
                'login' => '🔐 Login',
                'logout' => '🚪 Logout',
                'verifikasi_pembayaran' => '✅ Konfirmasi Bayar',
                'penolakan_pembayaran' => '❌ Penolakan Bayar',
                'upload_bukti_pembayaran' => '💳 Upload Bukti',
                'daftar_penghuni' => '👤 Daftar Penghuni',
                'checkout_penghuni' => '🏃 Checkout Penghuni',
                'kosongkan_kamar' => '🧹 Kosongkan Kamar',
                'tambah_kamar', 'update_kamar' => '🚪 Kelola Kamar',
                'tambah_kos', 'update_kos' => '🏠 Kelola Kos',
                'kirim_pengumuman' => '📢 Pengumuman',
                'tambah_aturan', 'update_aturan', 'hapus_aturan' => '📜 Aturan Kos',
                'tambah_pengguna', 'update_pengguna', 'toggle_pengguna', 'tambah_admin', 'update_admin', 'toggle_admin', 'hapus_admin' => '👥 Kelola User',
                default => '⚡ ' . strtoupper(str_replace('_', ' ', $log->aksi)),
            };
        @endphp
        <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800 flex items-start justify-between gap-3 text-xs">
            <div class="space-y-1">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-bold text-gray-900 dark:text-white">{{ $log->user->nama ?? 'Sistem' }}</span>
                    <span class="px-1.5 py-0.5 text-[9px] font-bold rounded {{ $roleColor }}">
                        {{ strtoupper(str_replace('_', ' ', $log->user->role ?? 'System')) }}
                    </span>
                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded-md bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        {{ $aksiBadge }}
                    </span>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                    {{ $log->detail ?? '-' }}
                </p>
            </div>
            <div class="text-right flex-shrink-0">
                <span class="text-[10px] text-gray-400 font-mono block">
                    {{ $log->created_at ? $log->created_at->format('d M Y') : '-' }}
                </span>
                <span class="text-[10px] text-gray-400 font-mono block">
                    {{ $log->created_at ? $log->created_at->format('H:i') : '' }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
@endif
