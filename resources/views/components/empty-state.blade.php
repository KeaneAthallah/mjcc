@props([
    'icon' => '📭',
    'title' => 'Tidak ada data',
    'description' => null,
])

<div class="text-center py-16 px-6">
    <div class="text-5xl mb-4">{{ $icon }}</div>
    <h3 class="text-[15px] font-extrabold text-gray-700">{{ $title }}</h3>
    @if ($description)
        <p class="text-[13px] text-gray-400 mt-1 max-w-sm mx-auto">{{ $description }}</p>
    @endif
</div>
