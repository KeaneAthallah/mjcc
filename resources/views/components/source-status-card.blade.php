@props([
    'source' => [],
])

@php
    $statusColor = match($source['status'] ?? 'belum') {
        'berhasil' => 'green',
        'gagal' => 'red',
        'belum' => 'gray',
        'tidak_tersedia' => 'orange',
        default => 'gray',
    };
@endphp

<a href="{{ $source['url'] ?? '#' }}" class="group block rounded-2xl border border-gray-100 bg-white p-4 shadow-sm hover:shadow-md transition">
    <div class="flex items-center justify-between mb-2">
        <span class="text-[13px] font-bold text-gray-800 group-hover:text-emerald-700">{{ $source['name'] ?? '' }}</span>
        <x-badge :color="$statusColor">{{ $source['status_label'] ?? '—' }}</x-badge>
    </div>
    <div class="text-[24px] font-extrabold text-gray-900 leading-none tabular-nums">{{ number_format($source['record_count'] ?? 0) }}</div>
    <div class="text-[11px] text-gray-400 mt-1">record tersimpan</div>
    <div class="mt-3 pt-2 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-500">
        <span>{{ $source['freshness'] ?? 'Belum pernah' }}</span>
        <span class="font-bold text-emerald-600 group-hover:text-emerald-700">Lihat →</span>
    </div>
</a>