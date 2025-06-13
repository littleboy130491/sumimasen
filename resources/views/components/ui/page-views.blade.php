@props([
    'count' => 0,
    'showIcon' => true,
    'format' => 'short', // 'short', 'long', 'number'
    'class' => '',
])

@php
    $formattedCount = match ($format) {
        'number' => number_format($count),
        default => $count >= 1000 ? round($count / 1000, 1) . 'k' : $count,
    };
@endphp

<span {{ $attributes->merge(['class' => 'page-views ' . $class]) }}>
    @if ($showIcon)
        <svg class="page-views-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" />
        </svg>
    @endif
    <span class="page-views-count">{{ $formattedCount }}</span>
</span>
