@props(['show' => false, 'title' => ''])

@php
$showString = is_bool($show) ? ($show ? 'true' : 'false') : (string)$show;
$isLiteral = in_array(strtolower($showString), ['true', 'false', '1', '0']);
$showExpr = $isLiteral ? ($showString === 'true' || $showString === '1' ? 'true' : 'false') : $showString;
@endphp

@if($isLiteral)
<div x-data="{ open: {{ $showExpr }} }" 
     x-show="open" 
     x-cloak 
     class="absolute -top-20 -left-4 -right-4 -bottom-16 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md overflow-y-auto" 
     x-transition.opacity.duration.200ms>
    <div @click.away="open = false" 
         class="bg-white dark:bg-gray-900 rounded-3xl w-full max-w-[390px] p-5 shadow-2xl border border-gray-200 dark:border-gray-800 max-h-[85vh] overflow-y-auto no-scrollbar my-auto relative transform transition-all" 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100">
        @if($title)
        <div class="flex items-center justify-between mb-3 border-b border-gray-100 dark:border-gray-800 pb-2">
            <h3 class="font-bold text-base text-gray-900 dark:text-white leading-snug">{{ $title }}</h3>
            <button type="button" @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        @endif
        <div class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed space-y-3">
            {{ $slot }}
        </div>
        @if(isset($footer))
        <div class="flex flex-col gap-2 pt-3 mt-3 border-t border-gray-100 dark:border-gray-800">
            {{ $footer }}
        </div>
        @endif
    </div>
</div>
@else
<div x-data="{ 
         get open() { return {{ $showExpr }}; }, 
         set open(val) { {{ $showExpr }} = val; } 
     }" 
     x-show="open" 
     x-cloak 
     class="absolute -top-20 -left-4 -right-4 -bottom-16 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md overflow-y-auto" 
     x-transition.opacity.duration.200ms>
    <div @click.away="open = false" 
         class="bg-white dark:bg-gray-900 rounded-3xl w-full max-w-[390px] p-5 shadow-2xl border border-gray-200 dark:border-gray-800 max-h-[85vh] overflow-y-auto no-scrollbar my-auto relative transform transition-all" 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100">
        @if($title)
        <div class="flex items-center justify-between mb-3 border-b border-gray-100 dark:border-gray-800 pb-2">
            <h3 class="font-bold text-base text-gray-900 dark:text-white leading-snug">{{ $title }}</h3>
            <button type="button" @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        @endif
        <div class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed space-y-3">
            {{ $slot }}
        </div>
        @if(isset($footer))
        <div class="flex flex-col gap-2 pt-3 mt-3 border-t border-gray-100 dark:border-gray-800">
            {{ $footer }}
        </div>
        @endif
    </div>
</div>
@endif