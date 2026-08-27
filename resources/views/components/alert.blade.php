@props([
    'type' => 'success',
    'title' => null,
])

@php
    $styles = [
        'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'error' => 'bg-red-50 text-red-800 border-red-200',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-200',
        'info' => 'bg-blue-50 text-blue-800 border-blue-200',
    ];
    $icons = [
        'success' => '✓',
        'error' => '✕',
        'warning' => '!',
        'info' => 'i',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'border-l-4 px-4 py-3 rounded-lg text-[13px] font-medium ' . ($styles[$type] ?? $styles['success'])]) }}>
    @if ($title)
        <strong class="block mb-0.5">{{ $title }}</strong>
    @endif
    {{ $slot }}
</div>
