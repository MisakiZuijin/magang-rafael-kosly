@php
$role = Auth::user()->role;
$menus = [];

// Flowbite Icons Path Data
$iconDashboard = 'm4 12 8-8 8 8M6 10.5V19a1 1 0 0 0 1 1h3v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h3a1 1 0 0 0 1-1v-8.5';
$iconAturan = 'M15 4h3a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h3m0 3h6m-6 5h6m-6 4h4M9 3v4h6V3H9Z';
$iconBayar = 'M3 10h18M6 14h2m3 0h4M5 5h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z';
$iconKamar = 'M18 20V6a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v14m12 0H6m12 0h2M6 20H4m10-7h.01';
$iconPengguna = 'M16 12h4m-2 2v-4M4 18v-1a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1M12 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z';
$iconMap = 'M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z M17.8 13.938C16.5 16 14 19 12 21c-2-2-4.5-5-5.8-7.062C5.2 12.4 4.5 10.7 4.5 9a7.5 7.5 0 1 1 15 0c0 1.7-.7 3.4-1.7 4.938Z';
$iconPengumuman = 'M11 16v-5.5A3.5 3.5 0 0 1 14.5 7H18v9h-3.5a3.5 3.5 0 0 1-3.5-3.5ZM6 8h2v8H6V8Zm-2 2h2v4H4v-4Z';
$iconLaporan = 'M3 15v4m6-6v6m6-10v10m6-16v16';
$iconPencairan = 'M12 6v13m0-13 4 4m-4-4-4 4';
$iconWAGateway = 'M16 12H4m12 0-4 4m4-4-4-4';
$iconPengaturan = 'M21 13v-2a1 1 0 0 0-1-1h-.76a7.12 7.12 0 0 0-.62-1.5l.54-.54a1 1 0 0 0 0-1.41l-1.42-1.42a1 1 0 0 0-1.41 0l-.54.54a7.12 7.12 0 0 0-1.5-.62V4a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v.76a7.12 7.12 0 0 0-1.5.62l-.54-.54a1 1 0 0 0-1.41 0L5.97 6.26a1 1 0 0 0 0 1.41l.54.54a7.12 7.12 0 0 0-.62 1.5H5a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h.76a7.12 7.12 0 0 0 .62 1.5l-.54.54a1 1 0 0 0 0 1.41l1.42 1.42a1 1 0 0 0 1.41 0l.54-.54a7.12 7.12 0 0 0 1.5.62V20a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-.76a7.12 7.12 0 0 0 1.5-.62l.54.54a1 1 0 0 0 1.41 0l1.42-1.42a1 1 0 0 0 0-1.41l-.54-.54a7.12 7.12 0 0 0 .62-1.5H20a1 1 0 0 0 1-1ZM12 15a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z';

// Sidebar Penghuni
if (in_array($role, ['penghuni'])) {
    $menus[] = ['icon' => $iconDashboard, 'label' => 'Dashboard', 'route' => 'penghuni.dashboard'];
    $menus[] = ['icon' => $iconAturan, 'label' => 'Aturan', 'route' => 'penghuni.aturan'];
    $menus[] = ['icon' => $iconBayar, 'label' => 'Bayar', 'route' => 'penghuni.pembayaran'];
}

// Sidebar Mitra
if (in_array($role, ['mitra'])) {
    $menus[] = ['icon' => $iconDashboard, 'label' => 'Dashboard', 'route' => 'mitra.dashboard'];
    $menus[] = ['icon' => $iconKamar, 'label' => 'Kamar', 'route' => 'mitra.kamar'];
}

// Sidebar Gabungan Admin & Super Admin
if (in_array($role, ['admin', 'super_admin'])) {
    $p = $role === 'super_admin' ? 'superadmin.' : 'admin.';
    $menus[] = ['icon' => $iconDashboard, 'label' => 'Dashboard', 'route' => $p . 'dashboard'];
    $menus[] = ['icon' => $iconPengguna, 'label' => 'Pengguna', 'route' => $p . 'pengguna.index'];
    if ($role === 'super_admin') {
        $menus[] = ['icon' => $iconMap, 'label' => 'Lokasi Kantor', 'route' => 'superadmin.kantor.index'];
    }
    $menus[] = ['icon' => $iconKamar, 'label' => 'Kos', 'route' => $p . 'kos.index'];
    $menus[] = ['icon' => $iconPengumuman, 'label' => 'Pengumuman', 'route' => $p . 'pengumuman.index'];
    $menus[] = ['icon' => $iconAturan, 'label' => 'Aturan', 'route' => $p . 'aturan.index'];
    $menus[] = ['icon' => $iconBayar, 'label' => 'Bayar', 'route' => $p . 'pembayaran.index'];
    $menus[] = ['icon' => $iconMap, 'label' => 'Map', 'route' => $p . 'map.index'];
    $menus[] = ['icon' => $iconLaporan, 'label' => 'Laporan', 'route' => $p . 'laporan.index'];
    if ($role === 'super_admin'){
        $menus[] = ['icon' => $iconPencairan, 'label' => 'Pencairan Biaya', 'route' => 'superadmin.pencairan.index'];
    }
    $menus[] = ['icon' => $iconWAGateway, 'label' => 'WA Gateway', 'route' => $p . 'whatsapp.index'];
    if ($role === 'super_admin') {
        $menus[] = ['icon' => $iconPengaturan, 'label' => 'Pengaturan Web', 'route' => 'superadmin.pengaturan.index'];
    }
}
@endphp

<aside x-cloak
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="absolute top-16 left-0 bottom-12 w-[260px] bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 z-50 transform transition-transform duration-300 overflow-y-auto no-scrollbar">

    <div class="p-2 pt-4">
        <nav class="space-y-1">
            @foreach($menus as $menu)
            @php
            $isActive = request()->routeIs($menu['route']) || request()->routeIs($menu['route'] . '.*');
            @endphp
            <a href="{{ route($menu['route']) }}" @click="sidebarOpen = false"
                class="flex items-center gap-3.5 px-4 py-3.5 rounded-xl text-sm font-semibold transition-all active:scale-[0.98]
                   {{ $isActive ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 active:bg-gray-100 dark:active:bg-gray-800' }}">
                <svg class="w-5 h-5 flex-shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $menu['icon'] }}" />
                </svg>
                <span>{{ $menu['label'] }}</span>
                @if($isActive)
                <div class="ml-auto w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                @endif
            </a>
            @endforeach
        </nav>
    </div>

    {{-- Dark Mode Toggle di Sidebar dengan Flowbite Icons --}}
    <div class="px-4 mt-4">
        <button id="theme-toggle" type="button" class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-xl text-sm font-semibold text-gray-600 dark:text-gray-400 active:bg-gray-100 dark:active:bg-gray-800 transition-all">
            {{-- Flowbite Moon --}}
            <svg id="theme-toggle-dark-icon" class="w-5 h-5 hidden" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 0 1-.5-17.986V3c-.354.966-.5 1.911-.5 3a9 9 0 0 0 9 9c.242 0 .474-.014.7-.033A9 9 0 0 1 12 21Z"/>
            </svg>
            {{-- Flowbite Sun --}}
            <svg id="theme-toggle-light-icon" class="w-5 h-5 hidden" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5V3m0 18v-2m7-7h2M3 12h2m13.364 6.364-1.414-1.414M6.343 6.343 4.929 4.929m12.728 0 1.414 1.414M6.343 17.657l-1.414 1.414M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/>
            </svg>
            <span id="theme-toggle-label">Mode</span>
        </button>
    </div>

    <div class="p-4 mt-auto">
        <button @click="sidebarOpen = false" class="w-full py-3 rounded-xl bg-gray-100 dark:bg-gray-800 text-sm font-semibold text-gray-600 dark:text-gray-400 active:bg-gray-200 dark:active:bg-gray-700">
            Tutup Menu
        </button>
    </div>
</aside>

{{-- Script Toggle Dark Mode --}}
<script>
    (function() {
        const toggleBtn = document.getElementById('theme-toggle');
        const lightIcon = document.getElementById('theme-toggle-light-icon');
        const darkIcon = document.getElementById('theme-toggle-dark-icon');
        const label = document.getElementById('theme-toggle-label');
        const html = document.documentElement;

        function updateUI() {
            const isDark = html.classList.contains('dark');
            if (isDark) {
                lightIcon.classList.remove('hidden');
                darkIcon.classList.add('hidden');
                label.textContent = 'Mode Terang';
            } else {
                lightIcon.classList.add('hidden');
                darkIcon.classList.remove('hidden');
                label.textContent = 'Mode Gelap';
            }
        }

        updateUI();

        toggleBtn.addEventListener('click', function() {
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
            updateUI();
        });
    })();
</script>