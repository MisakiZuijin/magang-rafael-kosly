@props(['type' => 'default'])

@php
$styles = [
'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
'danger' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
'info' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
'default' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center text-center px-2.5 py-1 text-[11px] uppercase tracking-wider font-bold rounded-lg ' . ($styles[$type] ?? $styles['default'])]) }}>
    {{ $slot }}
</span>