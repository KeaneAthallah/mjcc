@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl shadow-sm border border-gray-100']) }}>
    @if ($title)
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                @if ($icon)
                    <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg">{{ $icon }}</span>
                @endif
                <div>
                    <h3 class="font-extrabold text-gray-900 text-[14px]">{{ $title }}</h3>
                    @if ($subtitle)
                        <p class="text-[11px] text-gray-400">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            @isset($header)
                <div>{{ $header }}</div>
            @endisset
        </div>
    @endif
    <div @class(['px-5 py-4' => $padding, 'p-0' => ! $padding])>
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="px-5 py-3 border-t border-gray-100">{{ $footer }}</div>
    @endisset
</div>
