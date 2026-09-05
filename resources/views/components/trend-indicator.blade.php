@props([
    'delta' => 0,
    'invert' => false,
])

@php
    // invert: untuk metrik di mana nilai lebih rendah justru lebih baik.
    $direction = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
    if ($invert) {
        $direction = $direction === 'up' ? 'down' : ($direction === 'down' ? 'up' : 'flat');
    }
    $icon = match ($direction) {
        'up' => '↑',
        'down' => '↓',
        default => '—',
    };
    $color = match ($direction) {
        'up' => 'bg-emerald-100 text-emerald-700',
        'down' => 'bg-red-100 text-red-700',
        default => 'bg-gray-100 text-gray-500',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold ' . $color]) }}>
    <span>{{ $icon }}</span>
    @if ($delta !== 0)
        <span>{{ number_format($delta) }}</span>
    @endif
</span>