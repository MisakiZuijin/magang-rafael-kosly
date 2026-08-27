@props(['items' => []])

<div class="grid grid-cols-2 gap-3">
    @foreach($items as $item)
    @php
    $isFullSpan = (isset($item['colspan']) && $item['colspan'] == 2) || (isset($item['span']) && $item['span'] == 2) || !empty($item['full']);
    $spanClass = $isFullSpan ? 'col-span-2' : '';
    $customClass = $item['class'] ?? '';
    @endphp
    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3.5 sm:p-4 border border-gray-100 dark:border-gray-800 {{ $spanClass }} {{ $customClass }}">
        <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold mb-1">{{ $item['label'] }}</p>
        <p class="font-bold text-sm sm:text-base text-gray-900 dark:text-gray-100">{{ $item['value'] }}</p>
    </div>
    @endforeach
</div>