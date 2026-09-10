{{-- Commodity Prices / SP2KP --}}
@php
    $totalCommodities = $totalCommodities ?? 0;
    $latestPrices = $latestPrices ?? collect();
    $topIncreases = $topIncreases ?? collect();
    $topDecreases = $topDecreases ?? collect();
    $averageChange = $averageChange ?? null;
    $commodities = $commodities ?? collect();
    $markets = $markets ?? collect();
    $records = $records ?? null;
@endphp

{{-- KPI Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <x-stat-card label="Total Komoditas" :value="number_format($totalCommodities)" icon="🛒" color="emerald"/>
    <x-stat-card label="Harga Terekam" :value="number_format($records?->total() ?? 0)" icon="💰" color="blue"/>
    <x-stat-card label="Rata-rata Perubahan" :value="$averageChange !== null ? number_format($averageChange, 1, ',', '.') . '%' : '—'" icon="📊" color="{{ ($averageChange ?? 0) > 0 ? 'red' : 'green' }}"/>
    <x-stat-card label="Data Per" :value="$latestDate ? \Illuminate\Support\Carbon::parse($latestDate)->translatedFormat('d M Y') : '—'" icon="📅" color="violet"/>
</div>

{{-- Top Increases / Decreases --}}
@if ($topIncreases->isNotEmpty() || $topDecreases->isNotEmpty())
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @if ($topIncreases->isNotEmpty())
            <x-card title="Kenaikan Harga Tertinggi" subtitle="Komoditas dengan kenaikan persentase tertinggi" icon="📈">
                <div class="space-y-2">
                    @foreach ($topIncreases as $item)
                        <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
                            <span class="text-[12px] text-gray-700 font-semibold">{{ $item->commodity }}</span>
                            <span class="text-[12px] font-bold text-red-600">▲ {{ $item->formattedPercentageChange() }}</span>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif

        @if ($topDecreases->isNotEmpty())
            <x-card title="Penurunan Harga Tertinggi" subtitle="Komoditas dengan penurunan persentase tertinggi" icon="📉">
                <div class="space-y-2">
                    @foreach ($topDecreases as $item)
                        <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
                            <span class="text-[12px] text-gray-700 font-semibold">{{ $item->commodity }}</span>
                            <span class="text-[12px] font-bold text-emerald-600">▼ {{ $item->formattedPercentageChange() }}</span>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>
@endif

{{-- Filter --}}
<x-card title="Filter & Pencarian" subtitle="Saring data harga komoditas" icon="🔍">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <select name="commodity" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200">
            <option value="">Semua Komoditas</option>
            @foreach ($commodities as $c)
                <option value="{{ $c }}" @selected(request('commodity') === $c)>{{ $c }}</option>
            @endforeach
        </select>
        <select name="market" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200">
            <option value="">Semua Pasar</option>
            @foreach ($markets as $m)
                <option value="{{ $m }}" @selected(request('market') === $m)>{{ $m }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 text-[12px] font-bold text-white bg-violet-600 hover:bg-violet-700 rounded-xl px-4 py-2.5 transition">Terapkan</button>
            <a href="{{ route('public-data.source', 'sp2kp') }}" class="text-[12px] font-bold text-gray-500 border border-gray-200 rounded-xl px-3 py-2.5 transition">Reset</a>
        </div>
    </form>
</x-card>

{{-- Chart --}}
@if ($latestPrices->isNotEmpty())
    <x-card title="Perbandingan Harga" subtitle="Harga komoditas terkini" icon="📊">
        <div class="h-72"><canvas id="chart-commodity"></canvas></div>
    </x-card>
@endif

{{-- Tabel --}}
<x-card title="Daftar Harga" subtitle="Harga komoditas terkini" icon="📋" :padding="false">
    @if ($latestPrices->isEmpty())
        <x-empty-state icon="🛒" title="Belum ada data harga" message="Data harga komoditas belum berhasil diambil dari sumber."/>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-[12px]">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 uppercase tracking-wide text-[11px]">
                        <th class="px-4 py-3 font-bold">Komoditas</th>
                        <th class="px-4 py-3 font-bold">Harga</th>
                        <th class="px-4 py-3 font-bold">Sebelumnya</th>
                        <th class="px-4 py-3 font-bold">Perubahan</th>
                        <th class="px-4 py-3 font-bold">Status</th>
                        <th class="px-4 py-3 font-bold">Pasar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($latestPrices as $item)
                        <tr class="hover:bg-violet-50/40 transition">
                            <td class="px-4 py-2.5 text-gray-800 font-semibold">{{ $item->commodity }}</td>
                            <td class="px-4 py-2.5 text-gray-800 font-bold">Rp {{ number_format($item->current_price ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-2.5 text-gray-500">{{ $item->previous_price ? 'Rp ' . number_format($item->previous_price, 0, ',', '.') : '—' }}</td>
                            <td class="px-4 py-2.5 font-bold {{ ($item->percentage_change ?? 0) > 0 ? 'text-red-600' : (($item->percentage_change ?? 0) < 0 ? 'text-emerald-600' : 'text-gray-500') }}">
                                {{ $item->formattedPercentageChange() }}
                            </td>
                            <td class="px-4 py-2.5">
                                @if (($item->percentage_change ?? 0) > 0)
                                    <span class="text-[11px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full">▲ Naik</span>
                                @elseif (($item->percentage_change ?? 0) < 0)
                                    <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">▼ Turun</span>
                                @else
                                    <span class="text-[11px] font-bold text-gray-500 bg-gray-50 px-2 py-0.5 rounded-full">— Stabil</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-gray-500">{{ $item->market ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($records)
            <x-pagination :rows="$records"/>
        @endif
    @endif
</x-card>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const prices = @json($latestPrices->take(15));
    if (prices.length > 0) {
        const labels = prices.map(p => p.commodity);
        const data = prices.map(p => Number(p.current_price || 0));
        const colors = prices.map(p => (p.percentage_change || 0) > 0 ? 'rgba(239,68,68,0.8)' : ((p.percentage_change || 0) < 0 ? 'rgba(16,185,129,0.8)' : 'rgba(156,163,175,0.8)'));
        window.Mjcc.charts.makeBar(document.getElementById('chart-commodity'), labels, [{ data, backgroundColor: colors, borderWidth: 1 }]);
    }
});
</script>
@endpush