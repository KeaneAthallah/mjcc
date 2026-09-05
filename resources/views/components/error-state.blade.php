@props([
    'title' => 'Terjadi kesalahan',
    'message' => 'Gagal memuat data. Silakan muat ulang halaman.',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-red-200 bg-red-50/60 p-5 flex items-start gap-3']) }}>
    <span class="text-xl leading-none">⚠️</span>
    <div class="flex-1">
        <h4 class="text-[13px] font-extrabold text-red-800">{{ $title }}</h4>
        @if ($message)
            <p class="text-[12px] text-red-600 mt-0.5">{{ $message }}</p>
        @endif
        {{ $slot }}
    </div>
</div>