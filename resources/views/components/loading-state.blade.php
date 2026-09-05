@props([
    'text' => 'Memuat data...',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-12 px-6 text-center']) }}>
    <div class="w-9 h-9 rounded-full border-[3px] border-emerald-200 border-t-emerald-600 animate-spin mb-3"></div>
    <p class="text-[13px] text-gray-500">{{ $text }}</p>
</div>