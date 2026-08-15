@props(['show' => false, 'title' => ''])

<div x-data="{ open: {{ $show ? 'true' : 'false' }} }" x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/60 backdrop-blur-sm" x-transition.opacity.duration.200ms>
    <div @click.away="open = false" class="bg-white dark:bg-gray-900 rounded-t-3xl sm:rounded-3xl w-full max-w-lg p-5 sm:p-6 shadow-2xl border border-gray-200 dark:border-gray-800 max-h-[90vh] overflow-y-auto" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full sm:translate-y-0 sm:scale-95" x-transition:enter-end="translate-y-0 sm:scale-100">
        @if($title)
        <h3 class="font-bold text-lg mb-1">{{ $title }}</h3>
        @endif
        <div class="text-sm text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
            {{ $slot }}
        </div>
        @if(isset($footer))
        <div class="flex flex-col-reverse sm:flex-row gap-2 sm:justify-end">
            {{ $footer }}
        </div>
        @endif
    </div>
</div>