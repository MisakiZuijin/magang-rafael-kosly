@props(['href' => null, 'variant' => 'primary', 'size' => 'md'])

@php
$base = 'inline-flex items-center justify-center font-semibold rounded-xl transition-all active:scale-[0.96] focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900 min-h-[48px]';
$variants = [
'primary' => 'bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500 focus:ring-emerald-500 shadow-lg shadow-emerald-600/20',
'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:hover:text-white focus:ring-gray-500',
'danger' => 'bg-red-600 text-white hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-500 focus:ring-red-500 shadow-lg shadow-red-600/20',
'outline' => 'border-2 border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white',
'ghost' => 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white',
];
$sizes = [
'sm' => 'px-4 py-2 text-sm',
'md' => 'px-5 py-2.5 text-sm w-full sm:w-auto',
'lg' => 'px-6 py-3 text-base w-full sm:w-auto',
];
$class = $base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
<button {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif