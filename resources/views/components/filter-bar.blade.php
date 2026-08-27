@props([
    'searchPlaceholder' => 'Cari...',
    'search' => null,
    'onReset' => null,
    'action' => null,
])

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-4 p-3">
    <form method="GET" action="{{ $action ?? url()->current() }}" class="flex flex-wrap items-center gap-3">
        <div class="relative min-w-[200px] flex-1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="{{ $searchPlaceholder }}"
                class="w-full rounded-xl border border-gray-300 text-[13px] pl-9 pr-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500"
            >
        </div>
        {{ $slot }}
        <x-button type="submit" variant="primary" size="sm">Cari</x-button>
        @if ($search)
            <a href="{{ $onReset ?? url()->current() }}" class="text-[12px] font-bold text-gray-500 hover:text-red-600 px-2 py-2">✕ Reset</a>
        @endif
    </form>
</div>
