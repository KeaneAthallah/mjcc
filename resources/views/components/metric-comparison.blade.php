@props([
    'label' => '',
    'value' => '0',
    'icon' => '📊',
    'color' => 'green',
    'iconBg' => null,
    'footer' => null,
    'current' => null,
    'previous' => null,
    'invert' => false,
])

@php
    $border = [
        'green' => 'border-l-emerald-500',
        'blue' => 'border-l-blue-500',
        'red' => 'border-l-red-500',
        'amber' => 'border-l-amber-500',
        'violet' => 'border-l-violet-500',
        'orange' => 'border-l-orange-500',
        'teal' => 'border-l-teal-500',
        'gray' => 'border-l-gray-400',
    ][$color] ?? 'border-l-emerald-500';

    $iconStyle = $iconBg
        ?? ($color === 'blue' ? 'bg-blue-100 text-blue-600'
            : ($color === 'violet' ? 'bg-violet-100 text-violet-600'
                : ($color === 'red' ? 'bg-red-100 text-red-600'
                    : ($color === 'amber' ? 'bg-amber-100 text-amber-600'
                        : 'bg-emerald-100 text-emerald-600'))));

    $delta = ($current !== null && $previous !== null) ? $current - $previous : null;
@endphp

<div class="bg-white rounded-2xl p-5 shadow-sm border-l-4 {{ $border }} hover:shadow-md transition relative overflow-hidden">
    <div class="w-11 h-11 rounded-xl {{ $iconStyle }} flex items-center justify-center text-xl mb-2.5">{{ $icon }}</div>
    <div class="text-[26px] font-extrabold text-gray-900 leading-none tabular-nums">{{ $value }}</div>
    <div class="text-[11px] text-gray-600 mt-1 uppercase tracking-wide">{{ $label }}</div>

    @if ($delta !== null)
        <div class="mt-2 flex items-center gap-2">
            <x-trend-indicator :delta="$delta" :invert="$invert"/>
            <span class="text-[11px] text-gray-400">dibanding periode sebelumnya</span>
        </div>
    @endif

    @if ($footer)
        <div class="mt-2 text-[11px] text-gray-400">{{ $footer }}</div>
    @endif
</div>