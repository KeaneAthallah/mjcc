{{-- Sitaba / Disaster Events --}}
@php
    $records = $records ?? null;
    $mapData = $mapData ?? collect();
    $types = $types ?? collect();
    $districts = $districts ?? collect();
    $recentCount = $recentCount ?? 0;
    $byType = $byType ?? ['labels' => [], 'data' => []];
    $byDistrict = $byDistrict ?? ['labels' => [], 'data' => []];
    $impactTotal = $impactTotal ?? 0;
    $insights = $insights ?? [];
    $disasterMapData = $mapData->map(fn ($e) => [
        'name' => $e->disaster_type,
        'latitude' => (float) $e->latitude,
        'longitude' => (float) $e->longitude,
        'category' => 'sitaba',
        'kecamatan' => $e->district,
        'details' => ['Tanggal' => $e->event_date?->format('d M Y') ?? '—', 'Dampak' => $e->impact ?? '—'],
    ])->values()->all();
@endphp

<x-source-widget-header icon="🚨" title="Bencana Terkini" subtitle="Kejadian bencana terkini dari SITABA PUPR" key="sitaba" accent="amber">

@include('partials.source-insights', ['insights' => $insights])

<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <x-stat-card label="Total Bencana" :value="number_format($records?->total() ?? 0)" icon="⚠️" color="red"/>
    <x-stat-card label="30 Hari Terakhir" :value="number_format($recentCount)" icon="🕐" color="amber"/>
    <x-stat-card label="Jenis Bencana" :value="number_format($types->count())" icon="📋" color="blue"/>
    <x-stat-card label="Penduduk Terdampak" :value="$impactTotal > 0 ? number_format($impactTotal) : '—'" icon="👥" color="violet"/>
</div>

@if (count($byType['data']) > 0 || count($byDistrict['data']) > 0)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @if (count($byType['data']) > 0)
            <x-card title="Kejadian per Jenis" subtitle="Distribusi jenis bencana" icon="🏷️">
                <div class="h-64"><canvas id="chart-sitaba-type"></canvas></div>
            </x-card>
        @endif
        @if (count($byDistrict['data']) > 0)
            <x-card title="Kejadian per Lokasi" subtitle="Distribusi kejadian per kecamatan" icon="📌">
                <div class="h-64"><canvas id="chart-sitaba-district"></canvas></div>
            </x-card>
        @endif
    </div>
@endif

<x-card title="Filter" subtitle="Saring menurut jenis dan lokasi" icon="🔍">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <select name="type" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200">
            <option value="">Semua Jenis</option>
            @foreach ($types as $t)
                <option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>
            @endforeach
        </select>
        <select name="district" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200">
            <option value="">Semua Lokasi</option>
            @foreach ($districts as $d)
                <option value="{{ $d }}" @selected(request('district') === $d)>{{ $d }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 text-[12px] font-bold text-white bg-violet-600 hover:bg-violet-700 rounded-xl px-4 py-2.5 transition">Terapkan</button>
            <a href="{{ route('public-data.source', 'sitaba') }}" class="text-[12px] font-bold text-gray-500 border border-gray-200 rounded-xl px-3 py-2.5 transition">Reset</a>
        </div>
    </form>
</x-card>

@if ($mapData->isNotEmpty())
    <x-card title="Peta Bencana Terkini" subtitle="Lokasi kejadian bencana" icon="🗺️" :padding="false">
        <div id="map-sitaba" class="h-[300px] sm:h-[440px]"></div>
    </x-card>
@endif

<x-card title="Kejadian Bencana" subtitle="Daftar bencana terkini dari Sitaba PUPR" icon="📋" :padding="false">
    @if ($records?->isEmpty())
        <x-empty-state icon="⚠️" title="Belum ada data bencana" message="Data bencana terkini belum berhasil diambil dari sumber."/>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-[12px]">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 uppercase tracking-wide text-[11px]">
                        <th class="px-4 py-3 font-bold">Jenis</th>
                        <th class="px-4 py-3 font-bold">Tanggal</th>
                        <th class="px-4 py-3 font-bold">Lokasi</th>
                        <th class="px-4 py-3 font-bold">Dampak</th>
                        <th class="px-4 py-3 font-bold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records ?? [] as $event)
                        <tr class="hover:bg-violet-50/40 transition">
                            <td class="px-4 py-2.5 text-gray-800 font-semibold">{{ $event->disaster_type }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $event->event_date?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $event->district ?? $event->affected_area ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-600 max-w-[250px] truncate" title="{{ $event->impact }}">{{ $event->impact ?? '—' }}</td>
                            <td class="px-4 py-2.5">
                                <x-badge color="{{ $event->status === 'aktif' ? 'red' : 'gray' }}">{{ ucfirst($event->status) }}</x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-[13px] text-gray-500">Tidak ada data untuk filter yang dipilih.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records)
            <x-pagination :rows="$records"/>
        @endif
    @endif
</x-card>

</x-source-widget-header>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const palette = ['#f59e0b', '#ef4444', '#3b82f6', '#10b981', '#8b5cf6', '#06b6d4'];

    const typeLabels = @json($byType['labels'] ?? []);
    const typeData = @json($byType['data'] ?? []);
    if (typeLabels.length > 0 && document.getElementById('chart-sitaba-type')) {
        window.Mjcc.charts.makePie(document.getElementById('chart-sitaba-type'), typeLabels, typeData, palette.slice(0, typeLabels.length));
    }

    const distLabels = @json($byDistrict['labels'] ?? []);
    const distData = @json($byDistrict['data'] ?? []);
    if (distLabels.length > 0 && document.getElementById('chart-sitaba-district')) {
        window.Mjcc.charts.makeBar(document.getElementById('chart-sitaba-district'), distLabels, [{
            data: distData,
            label: 'Kejadian',
            backgroundColor: palette.slice(0, distLabels.length),
            borderWidth: 1,
        }]);
    }

    const disasterData = @json($disasterMapData);
    if (disasterData.length > 0) {
        window.Mjcc.maps.createMap('map-sitaba', disasterData, { resize: true, cluster: true, center: [-3.25, 121.85], zoom: 9 });
    }
});
</script>
@endpush