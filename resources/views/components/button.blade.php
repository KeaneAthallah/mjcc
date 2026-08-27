@props([
    'variant' => 'primary',
    'type' => 'button',
    'size' => 'md',
    'href' => null,
    'disabled' => false,
])

@php
    $variants = [
        'primary' => 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm',
        'blue' => 'bg-blue-600 text-white hover:bg-blue-700 shadow-sm',
        'red' => 'bg-red-600 text-white hover:bg-red-700 shadow-sm',
        'outline' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50',
        'ghost' => 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200',
        'dark' => 'bg-gray-800 text-white hover:bg-gray-900 shadow-sm',
    ];
    $sizes = [
        'xs' => 'px-2.5 py-1 text-[11px]',
        'sm' => 'px-3 py-1.5 text-[12px]',
        'md' => 'px-4 py-2.5 text-[13px]',
        'lg' => 'px-5 py-3 text-[14px]',
    ];
    $base = 'inline-flex items-center justify-center gap-1.5 rounded-xl font-bold transition focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-emerald-400';
    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']) . ($disabled ? ' opacity-50 cursor-not-allowed' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
