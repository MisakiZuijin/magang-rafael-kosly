@props(['title', 'subtitle' => null, 'preTitle' => null, 'backUrl' => null])

@php
if ($backUrl === true || $backUrl === 'dashboard') {
    $backUrl = route('dashboard');
}
@endphp

<div class="flex items-center justify-between gap-2">
    <div class="flex items-center gap-2.5">
        @if($backUrl)
        <a href="{{ $backUrl }}" class="w-8 h-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl flex items-center justify-center transition-all shadow-sm active:scale-95 flex-shrink-0" title="Kembali">
            <svg class="w-4 h-4 text-gray-700 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12l6-6m-6 6 6 6"/>
            </svg>
        </a>
        @endif
        <div>
            @if($preTitle)
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-0.5">{{ $preTitle }}</p>
            @endif
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">{{ $title }}</h1>
            @if($subtitle)
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @if(isset($action))
    <div class="flex items-center gap-2">
        {{ $action }}
    </div>
    @endif
</div>
