@php
$role = Auth::user()->role;
$menus = [];

if (in_array($role, ['penghuni'])) {
$menus[] = ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Dashboard', 'route' => 'penghuni.dashboard'];
$menus[] = ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => 'Aturan', 'route' => 'penghuni.aturan'];
$menus[] = ['icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'label' => 'Bayar', 'route' => 'penghuni.pembayaran'];
}

if (in_array($role, ['mitra'])) {
$menus[] = ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Dashboard', 'route' => 'mitra.dashboard'];
$menus[] = ['icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'label' => 'Kamar', 'route' => 'mitra.kamar'];
}

if (in_array($role, ['admin', 'super_admin'])) {
$p = $role === 'super_admin' ? 'superadmin.' : 'admin.';
$menus[] = ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Dashboard', 'route' => $p . 'dashboard'];
$menus[] = ['icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'label' => 'Pengguna', 'route' => $p . 'pengguna.index'];
$menus[] = ['icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'label' => 'Kos', 'route' => $p . 'kos.index'];
$menus[] = ['icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z', 'label' => 'Pengumuman', 'route' => $p . 'pengumuman.index'];
$menus[] = ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => 'Aturan', 'route' => $p . 'aturan.index'];
$menus[] = ['icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'label' => 'Bayar', 'route' => $p . 'pembayaran.index'];
$menus[] = ['icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z', 'label' => 'Map', 'route' => $p . 'map.index'];
$menus[] = ['icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => 'Laporan', 'route' => $p . 'laporan.index'];
}

if ($role === 'super_admin') {
$menus[] = ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'label' => 'Kelola Admin', 'route' => 'superadmin.admin.index'];
$menus[] = ['icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'label' => 'Lokasi Kantor', 'route' => 'superadmin.kantor.index'];
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
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $menu['icon'] }}" />
                </svg>
                <span>{{ $menu['label'] }}</span>
                @if($isActive)
                <div class="ml-auto w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                @endif
            </a>
            @endforeach
        </nav>
    </div>

    {{-- Dark Mode Toggle di Sidebar --}}
    <div class="px-4 mt-4">
        <button id="theme-toggle" type="button" class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-xl text-sm font-semibold text-gray-600 dark:text-gray-400 active:bg-gray-100 dark:active:bg-gray-800 transition-all">
            {{-- Moon (tampil saat light mode) --}}
            <svg id="theme-toggle-dark-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
            {{-- Sun (tampil saat dark mode) --}}
            <svg id="theme-toggle-light-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
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