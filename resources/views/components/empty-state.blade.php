@props([
    'icon' => '📭',
    'title' => 'Data belum tersedia',
    'message' => 'Sumber data belum berhasil disinkronkan.',
])

<div class="p-10 text-center">
    <div class="text-5xl mb-3">{{ $icon }}</div>
    <p class="text-[14px] font-extrabold text-gray-700">{{ $title }}</p>
    <p class="text-[12.5px] text-gray-500 mt-1">{{ $message }}</p>
</div>