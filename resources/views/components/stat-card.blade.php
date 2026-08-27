@props([
    'label' => '',
    'value' => '0',
    'icon' => '📊',
    'color' => 'green',
    'iconBg' => null,
])

@php
    $border = [
        'green' => 'border-l-emerald-500',
        'blue' => 'border-l-blue-500',
        'red' => 'border-l-red-500',
        'amber' => 'border-l-amber-500',
    ][$color] ?? 'border-l-emerald-500';

    $iconStyle = $iconBg ?? ($color === 'blue' ? 'bg-blue-100 text-blue-600' : 'bg-emerald-100 text-emerald-600');
@endphp

<div class="bg-white rounded-2xl p-5 shadow-sm border-l-4 {{ $border }} hover:shadow-md hover:-translate-y-0.5 transition relative overflow-hidden">
    <div class="w-11 h-11 rounded-xl {{ $iconStyle }} flex items-center justify-center text-xl mb-2.5">{{ $icon }}</div>
    <div class="text-[26px] font-extrabold text-gray-900 leading-none">{{ $value }}</div>
    <div class="text-[11px] text-gray-600 mt-1 uppercase tracking-wide">{{ $label }}</div>
    @if (isset($footer))
        <div class="mt-2 text-[11px] text-gray-400">{{ $footer }}</div>
    @endif
</div>
