<nav class="absolute top-0 left-0 right-0 h-16 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 z-60 flex items-center justify-between px-4">

    <!-- Kiri: Burger Flowbite Icon -->
    <button @click="sidebarOpen = !sidebarOpen" class="p-2 -ml-2 rounded-xl active:bg-gray-100 dark:active:bg-gray-800 dark:text-white min-w-[44px] min-h-[44px] flex items-center justify-center">
        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15h18M3 9h18"/>
        </svg>
    </button>

    <!-- Tengah: Logo -->
    <div class="absolute left-1/2 transform -translate-x-1/2 flex items-center gap-2">
        <img src="{{ asset('images/logo.png') }}" alt="Kosly Logo" class="w-8 h-8 object-contain">
        <span class="font-bold text-lg tracking-tight dark:text-white">Kosly</span>
    </div>

    <!-- Kanan: Notif + Profile Flowbite Icons -->
    <div class="flex items-center gap-1 ml-auto">
        @php
        $hasUnreadNotif = \App\Models\Notifikasi::where('user_id', Auth::id())
            ->where('status', 'terkirim')
            ->exists();
        @endphp
        <a href="{{ route('notifikasi.index') }}" class="p-2.5 rounded-xl active:bg-gray-100 dark:active:bg-gray-800 dark:text-white min-w-[44px] min-h-[44px] flex items-center justify-center relative">
            <svg class="w-5 h-5 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5.365V3m0 2.365a5.338 5.338 0 0 0-5.133 5.368v1.8c0 .323-.13.633-.36.861l-1.01 1.004A1 1 0 0 0 6.2 16h11.6a1 1 0 0 0 .707-1.702l-1.01-1.004a1.18 1.18 0 0 1-.36-.861v-1.8A5.337 5.338 0 0 0 12 5.365ZM9 17a3 3 0 0 0 6 0M9 17h6"/>
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
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17v1a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-4a3 3 0 0 0-3 3Zm8-9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                    Profil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 dark:text-red-400 active:bg-red-50 dark:active:bg-red-900/20">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H8m12 0-4 4m4-4-4-4M9 4H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h2"/>
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>