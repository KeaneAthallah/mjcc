{{-- IRBI / Disaster Risk --}}
@php
    $records = $records ?? collect();
    $hazardTypes = $hazardTypes ?? collect();
    $riskCounts = $riskCounts ?? ['Tinggi' => 0, 'Sedang' => 0, 'Rendah' => 0];
    $riskChoropleth = $riskChoropleth ?? ['year' => null, 'hazard' => null, 'regions' => [], 'regions_count' => 0, 'geojson_url' => null];
    $averageIndex = $averageIndex ?? null;
    $insights = $insights ?? [];
@endphp

<x-source-widget-header icon="🌋" title="Indeks Risiko Bencana (IRBI)" subtitle="Indeks Risiko Bencana Indonesia per wilayah dari BNPB" key="irbi" accent="red">

@include('partials.source-insights', ['insights' => $insights])

<div class="grid grid-cols-2 md:grid-cols-5 gap-3">
    <x-stat-card label="Total Data" :value="number_format($records->count())" icon="🗺️" color="blue"/>
    <x-stat-card label="Risiko Tinggi" :value="number_format($riskCounts['Tinggi'])" icon="🔴" color="red"/>
    <x-stat-card label="Risiko Sedang" :value="number_format($riskCounts['Sedang'])" icon="🟡" color="amber"/>
    <x-stat-card label="Risiko Rendah" :value="number_format($riskCounts['Rendah'])" icon="🟢" color="green"/>
    <x-stat-card label="Rata-rata Indeks" :value="$averageIndex !== null ? number_format($averageIndex, 2, ',', '.') : '—'" icon="📐" color="violet"/>
</div>

@if (count(array_filter($riskCounts)) > 0)
    <x-card title="Sebaran Level Risiko" subtitle="Distribusi tingkat risiko di seluruh wilayah" icon="🧮">
        <div class="h-64"><canvas id="chart-risk-levels"></canvas></div>
    </x-card>
@endif

<x-card title="Filter" subtitle="Saring menurut jenis bahaya" icon="🔍">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <select name="hazard" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200">
            <option value="">Semua Jenis Bahaya</option>
            @foreach ($hazardTypes as $h)
                <option value="{{ $h }}" @selected(request('hazard') === $h)>{{ $h }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 text-[12px] font-bold text-white bg-violet-600 hover:bg-violet-700 rounded-xl px-4 py-2.5 transition">Terapkan</button>
            <a href="{{ route('public-data.source', 'irbi') }}" class="text-[12px] font-bold text-gray-500 border border-gray-200 rounded-xl px-3 py-2.5 transition">Reset</a>
        </div>
    </form>
</x-card>

@if (!empty($riskChoropleth['regions']))
    <x-card title="Peta Risiko Bencana (IRBI {{ $riskChoropleth['year'] ?? '' }})"
            subtitle="Level {{ $riskChoropleth['hazard'] ?? 'Multi Bahaya' }} per kabupaten se-Sulawesi Tengah"
            icon="🗺️" :padding="false">
        <div id="map-irbi" class="h-[300px] sm:h-[440px]"></div>
        @if (($riskChoropleth['regions_count'] ?? 0) < 13)
            <p class="px-4 py-2 text-[11px] text-amber-600">Beberapa kabupaten belum tersinkron — wilayah tanpa data ditandai abu-abu.</p>
        @endif
    </x-card>
@endif

<x-card title="Data Indeks Risiko" subtitle="Indeks Risiko Bencana Indonesia per wilayah" icon="📋" :padding="false">
    @if ($records->isEmpty())
        <x-empty-state icon="🗺️" title="Belum ada data risiko" message="Data indeks risiko bencana belum berhasil diambil dari sumber."/>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-[12px]">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 uppercase tracking-wide text-[11px]">
                        <th class="px-4 py-3 font-bold">Wilayah</th>
                        <th class="px-4 py-3 font-bold">Jenis Bahaya</th>
                        <th class="px-4 py-3 font-bold">Indeks Risiko</th>
                        <th class="px-4 py-3 font-bold">Level</th>
                        <th class="px-4 py-3 font-bold">Kerentanan</th>
                        <th class="px-4 py-3 font-bold">Eksposur</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($records as $r)
                        <tr class="hover:bg-violet-50/40 transition">
                            <td class="px-4 py-2.5 text-gray-800 font-semibold">{{ $r->region_name }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $r->hazard_type ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-800 font-bold">{{ $r->risk_index !== null ? number_format($r->risk_index, 2, ',', '.') : '—' }}</td>
                            <td class="px-4 py-2.5">
                                @php $color = $r->riskLevelColor(); @endphp
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-{{ $color }}-100 text-{{ $color }}-700">{{ $r->risk_level ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $r->vulnerability_index !== null ? number_format($r->vulnerability_index, 2, ',', '.') : '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $r->exposure_index !== null ? number_format($r->exposure_index, 2, ',', '.') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-card>

</x-source-widget-header>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const riskCounts = @json(array_values($riskCounts));
    if (riskCounts.some(v => v > 0) && document.getElementById('chart-risk-levels')) {
        window.Mjcc.charts.makeDoughnut(document.getElementById('chart-risk-levels'), ['Tinggi', 'Sedang', 'Rendah'], riskCounts, ['#ef4444', '#f59e0b', '#10b981']);
    }

    const choropleth = @json($riskChoropleth);
    if (choropleth.regions && Object.keys(choropleth.regions).length > 0 && choropleth.geojson_url) {
        fetch(choropleth.geojson_url)
            .then((res) => res.ok ? res.json() : null)
            .then((geo) => {
                if (!geo) {
                    return;
                }
                window.Mjcc.maps.createRiskChoropleth('map-irbi', geo, choropleth.regions, {
                    resize: true,
                    hazard: choropleth.hazard,
                    center: [-1.8, 120.5],
                    zoom: 7,
                });
            });
    }
});
</script>
@endpush