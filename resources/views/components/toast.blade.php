@props(['type' => 'success', 'message' => ''])

<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)" x-transition.opacity.duration.300ms class="mb-4">
    <div class="flex items-start gap-3 px-4 py-3.5 rounded-2xl text-sm shadow-lg border
        {{ $type === 'success' ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' : '' }}
        {{ $type === 'error' ? 'bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-300 border-red-200 dark:border-red-800' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            @if($type === 'success')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            @else
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            @endif
        </svg>
        <span class="font-medium leading-5">{{ $message }}</span>
        <button @click="show = false" class="ml-auto hover:opacity-60 p-1 -mr-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>