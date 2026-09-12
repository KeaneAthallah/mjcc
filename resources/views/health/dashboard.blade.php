@extends('layouts.app')

@section('title', 'Dashboard Kesehatan')

@section('content')
<div class="space-y-5 page-transition">

    <x-page-title title="Dashboard Pemantauan Kesehatan" subtitle="Monitoring fasilitas dan tenaga kesehatan Kabupaten Morowali">
        <x-slot:actions>
            <form method="GET" class="flex items-center gap-2">
                <select name="kecamatan" onchange="this.form.submit()"
                        class="rounded-xl border border-gray-300 text-[13px] px-3 py-2 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
                    <option value="">Semua Kecamatan</option>
                    @foreach ($kecamatans as $k)
                        <option value="{{ $k->id }}" @selected((int) $kecamatanId === (int) $k->id)>{{ $k->name }}</option>
                    @endforeach
                </select>
            </form>
        </x-slot:actions>
    </x-page-title>

    <div class="grid grid-cols-3 md:grid-cols-6 gap-3">
        <x-stat-card label="Puskesmas" value="{{ number_format($stats['puskesmas']) }}" icon="🏥" color="green"/>
        <x-stat-card label="Pustu" value="{{ number_format($stats['pustu']) }}" icon="🏬" color="blue"/>
        <x-stat-card label="Rumah Sakit" value="{{ number_format($stats['rs']) }}" icon="🏨" color="red"/>
        <x-stat-card label="Posyandu" value="{{ number_format($stats['posyandu']) }}" icon="👶" color="amber"/>
        <x-stat-card label="Dokter" value="{{ number_format($stats['dokter']) }}" icon="🩺" color="green"/>
        <x-stat-card label="Tempat Tidur" value="{{ number_format($stats['bed']) }}" icon="🛏️" color="blue"/>
    </div>

    @include('partials.alert-section', ['alerts' => $alerts])

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <x-card title="Kapasitas Tempat Tidur per Kecamatan" icon="🛏️">
            <div class="h-72"><canvas id="chart-capacity"></canvas></div>
        </x-card>
        <x-card title="Komposisi Fasilitas" icon="🏥">
            <div class="h-72"><canvas id="chart-proportion"></canvas></div>
        </x-card>
    </div>

    <x-card title="Tenaga Kesehatan per Kecamatan" icon="🩺">
        <div class="h-80"><canvas id="chart-workforce"></canvas></div>
    </x-card>

    <x-card title="Rekapitulasi Kesehatan per Kecamatan" icon="📋" :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-[12px]">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-2.5 font-bold">Kecamatan</th>
                        <th class="text-center px-2 py-2.5 font-bold">Puskesmas</th>
                        <th class="text-center px-2 py-2.5 font-bold">Pustu</th>
                        <th class="text-center px-2 py-2.5 font-bold">RS</th>
                        <th class="text-center px-2 py-2.5 font-bold">Posyandu</th>
                        <th class="text-center px-2 py-2.5 font-bold">Dokter</th>
                        <th class="text-center px-2 py-2.5 font-bold">Perawat</th>
                        <th class="text-center px-2 py-2.5 font-bold">Bidan</th>
                        <th class="text-center px-2 py-2.5 font-bold">Bed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($table as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5 font-semibold text-gray-800">{{ $row->name }}</td>
                            <td class="text-center px-2 py-2.5 text-emerald-600 font-bold">{{ $row->puskesmas_count }}</td>
                            <td class="text-center px-2 py-2.5 text-indigo-600 font-bold">{{ $row->pustu_count }}</td>
                            <td class="text-center px-2 py-2.5 text-red-600 font-bold">{{ $row->rs_count }}</td>
                            <td class="text-center px-2 py-2.5 text-amber-600 font-bold">{{ $row->posyandu_count }}</td>
                            <td class="text-center px-2 py-2.5 text-gray-700">{{ number_format($row->dok) }}</td>
                            <td class="text-center px-2 py-2.5 text-gray-700">{{ number_format($row->per) }}</td>
                            <td class="text-center px-2 py-2.5 text-gray-700">{{ number_format($row->bid) }}</td>
                            <td class="text-center px-2 py-2.5 text-blue-600 font-bold">{{ number_format($row->bed) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card title="Peta Persebaran Fasilitas Kesehatan" icon="🗺️" :padding="false">
        <div id="health-map" class="h-[300px] sm:h-[460px]"></div>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const C = window.Mjcc.charts;
    const M = window.Mjcc.maps;
    const data = @json($dashboardData);

    C.makeHorizontalBar(document.getElementById('chart-capacity'), data.capacity.labels, [{
        data: data.capacity.data,
        backgroundColor: 'rgba(59,130,246,0.8)',
        borderColor: 'rgba(59,130,246,1)',
        borderWidth: 1,
    }]);

    C.makeDoughnut(document.getElementById('chart-proportion'), data.proportion.labels, data.proportion.data, [
        '#10b981', '#6366f1', '#dc2626', '#f59e0b',
    ]);

    C.makeBar(document.getElementById('chart-workforce'), data.workforce.labels, data.workforce.datasets);

    const markers = @json($map);
    M.createMap('health-map', markers, {
        resize: true,
        cluster: true,
        center: [-3.25, 121.85],
        legendOnly: ['Puskesmas', 'Pustu', 'Rumah Sakit', 'Posyandu'],
        legend: true,
    });
});
</script>
@endpush
