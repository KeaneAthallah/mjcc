@props([
    'sourceUrl' => '#',
    'sourceName' => 'Sumber Data',
    'syncedAt' => null,
])

<div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 text-[11.5px]">
    <div class="flex items-center gap-2">
        <span class="text-gray-400">Sumber Data:</span>
        <a href="{{ $sourceUrl }}" target="_blank" rel="noopener" class="font-bold text-emerald-700 hover:text-emerald-900 hover:underline">{{ $sourceName }}</a>
    </div>
    <div class="flex items-center gap-2 text-gray-500">
        @if ($syncedAt)
            <span>Data diambil oleh MJCC · {{ \Illuminate\Support\Carbon::parse($syncedAt)->translatedFormat('d M Y H:i') }}</span>
        @else
            <span>Data diambil oleh MJCC</span>
        @endif
    </div>
</div>