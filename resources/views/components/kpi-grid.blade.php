@props([
    'cols' => 6,
    'label' => null,
])

@php
    $grid = [
        2 => 'grid-cols-2 md:grid-cols-2',
        3 => 'grid-cols-2 md:grid-cols-3',
        4 => 'grid-cols-2 md:grid-cols-4',
        5 => 'grid-cols-2 md:grid-cols-3 xl:grid-cols-5',
        6 => 'grid-cols-2 md:grid-cols-3 xl:grid-cols-6',
        8 => 'grid-cols-2 md:grid-cols-4 xl:grid-cols-8',
    ][$cols] ?? 'grid-cols-2 md:grid-cols-3 xl:grid-cols-6';
@endphp

@if ($label)
    <div class="text-[11px] uppercase tracking-widest text-gray-500 font-bold">{{ $label }}</div>
@endif

<div {{ $attributes->merge(['class' => 'grid gap-3 ' . $grid]) }}>
    {{ $slot }}
</div>