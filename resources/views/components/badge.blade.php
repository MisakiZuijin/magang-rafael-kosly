@props(['type' => 'default', 'size' => 'md'])

@php
$styles = [
'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
'danger' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
'info' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
'default' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
];

$sizes = [
'xs' => 'px-1.5 py-0.5 text-[8px] rounded-md',
'sm' => 'px-2 py-0.5 text-[10px] rounded-md',
'md' => 'px-2.5 py-1 text-[11px] rounded-lg',
];
$sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center text-center uppercase tracking-wider font-bold ' . $sizeClass . ' ' . ($styles[$type] ?? $styles['default'])]) }}>
    {{ $slot }}
</span>