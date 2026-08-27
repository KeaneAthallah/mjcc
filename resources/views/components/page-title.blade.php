@props([
    'title' => '',
    'subtitle' => '',
])

<div class="flex items-center justify-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-[12px] text-gray-500 mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
