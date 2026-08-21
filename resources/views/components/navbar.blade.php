<nav class="absolute top-0 left-0 right-0 h-16 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 z-60 flex items-center justify-between px-4">

    <!-- Kiri: Burger -->
    <button @click="sidebarOpen = !sidebarOpen" class="p-2 -ml-2 rounded-xl active:bg-gray-100 dark:active:bg-gray-800 dark:text-white min-w-[44px] min-h-[44px] flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    <!-- Tengah: Logo -->
    <div class="absolute left-1/2 transform -translate-x-1/2 flex items-center gap-2">
        <div class="w-8 h-8 bg-emerald-500 rounded-lg flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-emerald-500/30">K</div>
        <span class="font-bold text-lg tracking-tight dark:text-white">Kosly</span>
    </div>

    <!-- Kanan: Notif + Profile -->
    <div class="flex items-center gap-1 ml-auto">
        @php
        $hasUnreadNotif = \App\Models\Notifikasi::where('user_id', Auth::id())
            ->where('status', 'terkirim')
            ->exists();
        @endphp
        <a href="{{ route('notifikasi.index') }}" class="p-2.5 rounded-xl active:bg-gray-100 dark:active:bg-gray-800 dark:text-white min-w-[44px] min-h-[44px] flex items-center justify-center relative">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            @if($hasUnreadNotif)
            <span id="notif-badge" class="absolute top-2.5 right-2.5 w-2.5 h-2.5 bg-red-500 rounded-full ring-2 ring-white dark:ring-gray-900 animate-pulse"></span>
            @endif
        </a>

        <!-- Profile -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-2 p-1 pl-1 pr-2 rounded-xl active:bg-gray-100 dark:active:bg-gray-800 min-h-[44px]">
                <img src="{{ Auth::user()->foto_profile ? asset('storage/' . Auth::user()->foto_profile) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->nama) . '&background=10b981&color=fff&size=128' }}"
                    class="w-8 h-8 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700"
                    onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->nama) }}&background=10b981&color=fff&size=128'">
            </button>

            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-60 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 z-[60] py-2 overflow-hidden" x-cloak>
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-bold truncate dark:text-white">{{ Auth::user()->nama }}</p>
                    <p class="text-xs text-gray-500 truncate mt-0.5">{{ Auth::user()->email }}</p>
                    <span class="inline-block mt-2 px-2.5 py-1 text-[10px] uppercase tracking-wider font-bold rounded-full 
                        {{ Auth::user()->role === 'super_admin' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : '' }}
                        {{ Auth::user()->role === 'admin' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : '' }}
                        {{ Auth::user()->role === 'mitra' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : '' }}
                        {{ Auth::user()->role === 'penghuni' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : '' }}">
                        {{ str_replace('_', ' ', Auth::user()->role) }}
                    </span>
                </div>
                <a href="{{ route('profile.show') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium active:bg-gray-50 dark:text-white dark:active:bg-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Profil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 dark:text-red-400 active:bg-red-50 dark:active:bg-red-900/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>