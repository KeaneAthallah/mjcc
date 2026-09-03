@props([
    'rows' => [],
])

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
    @foreach ($rows as [$label, $value])
        <div>
            <div class="text-[11px] text-gray-500 uppercase tracking-wide">{{ $label }}</div>
            <div class="text-[13px] font-semibold text-gray-800 mt-0.5">
                @if ($value instanceof \Illuminate\Contracts\Support\Htmlable)
                    {!! $value->toHtml() !!}
                @else
                    {{ $value }}
                @endif
            </div>
        </div>
    @endforeach
</div>