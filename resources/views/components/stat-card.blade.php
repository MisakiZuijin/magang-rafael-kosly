@props(['label', 'value', 'color' => 'emerald', 'unit' => null])

@php
$colorStyles = [
'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400',
'blue' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400',
'amber' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400',
'purple' => 'bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400',
'red' => 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400',
];
$borderStyles = [
'emerald' => 'border-emerald-200 dark:border-emerald-900',
'blue' => 'border-blue-200 dark:border-blue-900',
'amber' => 'border-amber-200 dark:border-amber-900',
'purple' => 'border-purple-200 dark:border-purple-900',
'red' => 'border-red-200 dark:border-red-900',
];
$iconStyle = $colorStyles[$color] ?? $colorStyles['emerald'];
$borderClass = $borderStyles[$color] ?? $borderStyles['emerald'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-900 rounded-2xl p-3 border border-l-4 ' . $borderClass . ' shadow-sm flex items-center gap-2.5 min-w-0']) }}>
    @if(isset($icon))
    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 {{ $iconStyle }}">
        {{ $icon }}
    </div>
    @endif
    <div class="min-w-0 flex-1">
        <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider truncate">{{ $label }}</p>
        <p class="text-base font-bold text-gray-900 dark:text-white truncate">
            {{ $value }}
            @if($unit)
            <span class="text-[10px] font-normal text-gray-500">{{ $unit }}</span>
            @endif
        </p>
    </div>
</div>