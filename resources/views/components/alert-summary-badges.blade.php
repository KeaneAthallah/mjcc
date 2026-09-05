@props([
    'counts' => ['critical' => 0, 'warning' => 0, 'info' => 0, 'total' => 0],
    'link' => null,
])

@php
    $href = $link ?? route('alerts.index');
@endphp

<div class="flex items-center gap-1.5">
    <span class="px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-[11px] font-bold">{{ $counts['critical'] }} kritis</span>
    <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-[11px] font-bold">{{ $counts['warning'] }} warning</span>
    <span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-700 text-[11px] font-bold">{{ $counts['info'] }} info</span>
    <a href="{{ $href }}" class="ml-1 px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-[11px] font-bold hover:bg-emerald-700">Lihat Semua →</a>
</div>