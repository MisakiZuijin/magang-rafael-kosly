@props(['message' => 'Tidak ada data.'])

<div class="text-center py-10 sm:py-12 text-gray-400">
    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8 opacity-60 text-gray-400 dark:text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13h3.439a.991.991 0 0 1 .908.6 3.978 3.978 0 0 0 7.306 0 .99.99 0 0 1 .908-.6H20M4 13v6a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-6M4 13V6a1 1 0 0 1 1-1h5l2 2h7a1 1 0 0 1 1 1v5"/>
        </svg>
    </div>
    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $message }}</p>
</div>