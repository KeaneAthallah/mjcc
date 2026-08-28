@php
    $sectorAlerts = collect($alerts ?? [])->values();
    $count = $sectorAlerts->count();
@endphp
<div class="rounded-2xl border {{ $count > 0 ? 'border-amber-200 bg-amber-50/50' : 'border-emerald-200 bg-emerald-50/50' }} p-5 space-y-3">
    <div class="flex items-center justify-between">
        <h3 class="font-extrabold text-gray-900 text-[14px] flex items-center gap-2">
            <span class="text-lg">{{ $count > 0 ? '⚠️' : '✅' }}</span> Perlu Perhatian
        </h3>
        <x-badge color="{{ $count > 0 ? 'amber' : 'green' }}">{{ $count > 0 ? $count.' isu' : 'Semua aman' }}</x-badge>
    </div>
    @if ($count > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach ($sectorAlerts as $alert)
                <div class="flex items-start gap-3 rounded-xl bg-white border border-gray-100 p-3">
                    <span class="mt-0.5 text-base">{{ $alert['severity'] === 'critical' ? '🔴' : '🟡' }}</span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[12px] font-bold text-gray-800">{{ $alert['title'] }}</span>
                            <x-badge color="{{ $alert['severity'] === 'critical' ? 'red' : 'amber' }}">{{ $alert['sector'] }}</x-badge>
                        </div>
                        <p class="text-[12px] text-gray-500 mt-0.5">{{ $alert['detail'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-[13px] text-gray-500">Tidak ada isu yang memerlukan perhatian pada sektor ini.</p>
    @endif
</div>
