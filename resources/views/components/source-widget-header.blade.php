@props([
    'icon' => '📊',
    'title' => '',
    'subtitle' => '',
    'key' => null,
    'asOf' => null,
    'hideLink' => false,
    'accent' => 'violet',
])

@php
    $accents = [
        'emerald' => ['ring' => 'border-emerald-100', 'band' => 'border-emerald-50 bg-emerald-50/40', 'iconBg' => 'bg-emerald-100', 'iconText' => 'text-emerald-700', 'chipText' => 'text-emerald-600', 'chipBorder' => 'border-emerald-100'],
        'blue' => ['ring' => 'border-blue-100', 'band' => 'border-blue-50 bg-blue-50/40', 'iconBg' => 'bg-blue-100', 'iconText' => 'text-blue-700', 'chipText' => 'text-blue-600', 'chipBorder' => 'border-blue-100'],
        'violet' => ['ring' => 'border-violet-100', 'band' => 'border-violet-50 bg-violet-50/40', 'iconBg' => 'bg-violet-100', 'iconText' => 'text-violet-700', 'chipText' => 'text-violet-600', 'chipBorder' => 'border-violet-100'],
        'red' => ['ring' => 'border-red-100', 'band' => 'border-red-50 bg-red-50/40', 'iconBg' => 'bg-red-100', 'iconText' => 'text-red-700', 'chipText' => 'text-red-600', 'chipBorder' => 'border-red-100'],
        'amber' => ['ring' => 'border-amber-100', 'band' => 'border-amber-50 bg-amber-50/40', 'iconBg' => 'bg-amber-100', 'iconText' => 'text-amber-700', 'chipText' => 'text-amber-600', 'chipBorder' => 'border-amber-100'],
    ][$accent] ?? $accents['violet'];
@endphp

<div class="rounded-2xl border {{ $accents['ring'] }} bg-white overflow-hidden shadow-sm {{ $attributes->get('class') }}">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b {{ $accents['band'] }} px-5 py-4">
        <div class="flex items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-xl {{ $accents['iconBg'] }} {{ $accents['iconText'] }} text-[22px]">{{ $icon }}</span>
            <div>
                <h3 class="text-[16px] font-extrabold text-gray-900 leading-tight">{{ $title }}</h3>
                <p class="text-[12px] text-gray-500">{{ $subtitle }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-[11.5px]">
            @if ($asOf)
                <span class="rounded-full bg-white border {{ $accents['chipBorder'] }} px-2.5 py-1 {{ $accents['chipText'] }}">🔄 Sinkron {{ \Illuminate\Support\Carbon::parse($asOf)->translatedFormat('d M Y H:i') }}</span>
            @endif
            @if ($key && ! $hideLink)
                <a href="{{ route('public-data.source', $key) }}" target="_blank" class="rounded-full bg-white border border-gray-200 px-2.5 py-1 text-gray-600 hover:border-gray-300 transition">Sumber ↗</a>
            @endif
        </div>
    </div>
    <div class="space-y-5 p-5">
        {{ $slot }}
    </div>
</div>