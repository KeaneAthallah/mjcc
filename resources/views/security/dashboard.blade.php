@extends('layouts.app')

@section('title', 'Dashboard Ketertiban')

@section('content')
<div class="space-y-5 page-transition">

    <x-page-title title="Dashboard Pemantauan Ketertiban" subtitle="Monitoring keamanan dan ketertiban Kabupaten Morowali">
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

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <x-stat-card label="Kelurahan/Desa" value="{{ number_format($stats['kelurahan']) }}" icon="🏘️" color="green"/>
        <x-stat-card label="Polsek" value="{{ number_format($stats['polsek']) }}" icon="🚓" color="blue"/>
        <x-stat-card label="Tipkamtikmas" value="{{ number_format($stats['tipkamtikmas']) }}" icon="🪖" color="red"/>
        <x-stat-card label="Poskamling Aktif" value="{{ number_format($stats['poskamling']) }}" icon="🛡️" color="amber"/>
        <x-stat-card label="Pasar" value="{{ number_format($stats['pasar']) }}" icon="🏪" color="teal"/>
    </div>

    @include('partials.alert-section', ['alerts' => $alerts])

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <x-card title="Tipkamtikmas vs Poskamling per Kecamatan" icon="📊" class="lg:col-span-2">
            <div class="h-72"><canvas id="chart-compare"></canvas></div>
        </x-card>
        <x-card title="Sebaran Poskamling Aktif" icon="🛡️">
            <div class="h-72"><canvas id="chart-distribution"></canvas></div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <x-card title="Banyak Kelurahan per Kecamatan" icon="🏘️">
            <div class="h-72"><canvas id="chart-kelurahan"></canvas></div>
        </x-card>

        <x-card title="Daftar Polsek" icon="🚓" :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-bold">Polsek</th>
                            <th class="text-left px-2 py-2.5 font-bold">Kecamatan</th>
                            <th class="text-center px-2 py-2.5 font-bold">Personel</th>
                            <th class="text-center px-2 py-2.5 font-bold">Poskamling</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($polsekTable as $p)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 font-semibold text-gray-800">{{ $p->name }}</td>
                                <td class="px-2 py-2.5 text-gray-600">{{ $p->kecamatan?->name }}</td>
                                <td class="text-center px-2 py-2.5 text-blue-600 font-bold">{{ $p->personnel_count }}</td>
                                <td class="text-center px-2 py-2.5 text-emerald-600 font-bold">{{ $p->poskamling_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <x-card title="Peta Persebaran Ketertiban" icon="🗺️" :padding="false">
        <div id="security-map" class="h-[460px]"></div>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const C = window.Mjcc.charts;
    const M = window.Mjcc.maps;
    const data = @json($dashboardData);

    C.makeBar(document.getElementById('chart-compare'), data.compare.labels, data.compare.datasets);

    C.makePie(document.getElementById('chart-distribution'), data.distribution.labels, data.distribution.data, [
        '#10b981', '#059669', '#047857', '#3b82f6', '#2563eb', '#6ee7b7', '#34d399', '#a7f3d0', '#60a5fa', '#1d4ed8',
    ]);

    C.makeHorizontalBar(document.getElementById('chart-kelurahan'), data.kelurahanChart.labels, [{
        data: data.kelurahanChart.data,
        backgroundColor: 'rgba(16,185,129,0.8)',
        borderColor: 'rgba(16,185,129,1)',
        borderWidth: 1,
    }]);

    const mapData = @json($map);
    const markers = [
        ...mapData.polsek,
        ...mapData.kelurahan,
        ...mapData.pasar,
    ];

    M.createMap('security-map', markers, {
        resize: true,
        cluster: true,
        center: [-3.25, 121.85],
        legendOnly: ['polsek', 'kelurahan', 'pasar'],
        legend: true,
    });
});
</script>
@endpush
