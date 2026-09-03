@props([
    'color' => 'green',
])

@php
    $colors = [
        'green' => 'bg-emerald-100 text-emerald-700',
        'blue' => 'bg-blue-100 text-blue-700',
        'red' => 'bg-red-100 text-red-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'gray' => 'bg-gray-100 text-gray-700',
        'indigo' => 'bg-indigo-100 text-indigo-700',
        'teal' => 'bg-teal-100 text-teal-700',
        'violet' => 'bg-violet-100 text-violet-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold ' . ($colors[$color] ?? $colors['gray'])]) }}>
    {{ $slot }}
</span>
