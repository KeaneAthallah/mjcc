@props([
    'title' => '',
    'subtitle' => null,
    'icon' => null,
    'height' => 'h-72',
    'header' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl shadow-sm border border-gray-100']) }}>
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-2">
            @if ($icon)
                <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-base">{{ $icon }}</span>
            @endif
            <div>
                <h3 class="font-extrabold text-gray-900 text-[13px]">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="text-[11px] text-gray-400">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @if ($header)
            <div>{{ $header }}</div>
        @endif
    </div>
    <div class="p-4">
        {{ $slot }}
    </div>
</div>