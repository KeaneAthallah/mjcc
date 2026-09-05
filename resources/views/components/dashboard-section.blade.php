@props([
    'title' => '',
    'subtitle' => null,
    'icon' => null,
    'actions' => null,
    'pad' => true,
])

<section {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div class="flex items-center gap-2">
            @if ($icon)
                <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-base">{{ $icon }}</span>
            @endif
            <div>
                <h2 class="font-extrabold text-gray-900 text-[14px] uppercase tracking-wide">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="text-[11px] text-gray-400">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @if ($actions)
            <div class="flex items-center gap-2">{{ $actions }}</div>
        @endif
    </div>

    <div @class(['rounded-2xl bg-white shadow-sm border border-gray-100 p-5' => $pad])>
        {{ $slot }}
    </div>
</section>