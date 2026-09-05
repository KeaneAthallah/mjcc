@props([
    'severity' => 'info',
    'label' => null,
])

@php
    $styles = [
        'critical' => 'bg-red-100 text-red-700',
        'warning' => 'bg-amber-100 text-amber-700',
        'info' => 'bg-blue-100 text-blue-700',
    ];
    $icons = [
        'critical' => 'CRITICAL',
        'warning' => 'WARNING',
        'info' => 'INFO',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold tracking-wide ' . ($styles[$severity] ?? $styles['info'])]) }}>
    {{ $label ?? ($icons[$severity] ?? strtoupper($severity)) }}
</span>