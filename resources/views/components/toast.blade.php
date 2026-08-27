@props(['type' => 'success', 'message' => ''])

<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)" x-transition.opacity.duration.300ms class="mb-4">
    <div class="flex items-start gap-3 px-4 py-3.5 rounded-2xl text-sm shadow-lg border
        {{ $type === 'success' ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' : '' }}
        {{ $type === 'error' ? 'bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-300 border-red-200 dark:border-red-800' : '' }}">
        
        @if($type === 'success')
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11.917 9.724 16.5 19 7.5"/>
        </svg>
        @else
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-red-600 dark:text-red-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
        </svg>
        @endif

        <span class="font-medium leading-5">{{ $message }}</span>
        <button @click="show = false" class="ml-auto hover:opacity-60 p-1 -mr-1">
            <svg class="w-4 h-4 opacity-70" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
            </svg>
        </button>
    </div>
</div>