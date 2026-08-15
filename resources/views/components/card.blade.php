@props(['title' => null, 'subtitle' => null, 'class' => ''])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4 shadow-sm overflow-hidden ' . $class]) }}>
    @if($title || $subtitle)
    <div class="mb-4">
        @if($title)
        <h3 class="font-bold text-base">{{ $title }}</h3>
        @endif
        @if($subtitle)
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @endif
    {{ $slot }}
</div>