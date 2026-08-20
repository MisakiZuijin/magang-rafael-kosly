@props(['title', 'subtitle' => null])

<div class="flex items-center justify-between gap-2">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">{{ $title }}</h1>
        @if($subtitle)
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($action))
    <div class="flex items-center gap-2">
        {{ $action }}
    </div>
    @endif
</div>
