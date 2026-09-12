@extends('layouts.app')

@section('title', 'Dashboard Pendidikan')

@section('content')
<div class="space-y-5 page-transition">

    <x-page-title title="Dashboard Pemantauan Pendidikan" subtitle="Monitoring sektor pendidikan Kabupaten Morowali">
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

    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        <x-stat-card label="SD" value="{{ number_format($stats['total_sd']) }}" icon="🏫" color="green"/>
        <x-stat-card label="SMP" value="{{ number_format($stats['total_smp']) }}" icon="🏬" color="blue"/>
        <x-stat-card label="Siswa Laki-laki" value="{{ number_format($stats['siswa_laki']) }}" icon="👦" color="green"/>
        <x-stat-card label="Siswa Perempuan" value="{{ number_format($stats['siswa_perempuan']) }}" icon="👧" color="blue"/>
        <x-stat-card label="Guru" value="{{ number_format($stats['guru']) }}" icon="👩‍🏫" color="amber"/>
        <x-stat-card label="Mata Pelajaran" value="{{ number_format($stats['mapel']) }}" icon="📚" color="red"/>
    </div>

    @include('partials.alert-section', ['alerts' => $alerts])

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <x-card title="Siswa per Kecamatan" icon="🎓">
            <div class="h-72"><canvas id="chart-students"></canvas></div>
        </x-card>
        <x-card title="Sebaran Guru per Kecamatan" icon="👩‍🏫">
            <div class="h-72"><canvas id="chart-teachers"></canvas></div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        {{-- Table --}}
        <x-card title="Rekapitulasi Pendidikan per Kecamatan" icon="📋" :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-bold">Kecamatan</th>
                            <th class="text-center px-2 py-2.5 font-bold">SD</th>
                            <th class="text-center px-2 py-2.5 font-bold">SMP</th>
                            <th class="text-center px-2 py-2.5 font-bold">Siswa</th>
                            <th class="text-center px-2 py-2.5 font-bold">Guru</th>
                            <th class="text-center px-2 py-2.5 font-bold">Kelas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($table as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 font-semibold text-gray-800">{{ $row->name }}</td>
                                <td class="text-center px-2 py-2.5 text-emerald-600 font-bold">{{ $row->sd_count }}</td>
                                <td class="text-center px-2 py-2.5 text-blue-600 font-bold">{{ $row->smp_count }}</td>
                                <td class="text-center px-2 py-2.5 text-gray-700">{{ number_format($row->siswa_l + $row->siswa_p) }}</td>
                                <td class="text-center px-2 py-2.5 text-gray-700">{{ number_format($row->guru) }}</td>
                                <td class="text-center px-2 py-2.5 text-gray-700">{{ number_format($row->kelas) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Facility progress --}}
        <x-card title="Kelengkapan Sarana (Rata-rata)" icon="🏗️">
            <div class="space-y-4 pt-1">
                @foreach ($facilities as $f)
                    <div>
                        <div class="flex justify-between text-[12px] font-semibold text-gray-700 mb-1">
                            <span>{{ $f['name'] }}</span>
                            <span class="text-emerald-600">{{ $f['pct'] }}%</span>
                        </div>
                        <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-emerald-500 to-blue-500 rounded-full transition-all" style="width: {{ $f['pct'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

    <x-card title="Peta Persebaran Sekolah" icon="🗺️" :padding="false">
        <div id="education-map" class="h-[460px]"></div>
    </x-card>

    @include('partials.ats-widgets', ['ats' => $ats])
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const C = window.Mjcc.charts;
    const M = window.Mjcc.maps;
    const data = @json($dashboardData);

    C.makeBar(document.getElementById('chart-students'), data.students.labels, data.students.datasets);

    C.makeDoughnut(document.getElementById('chart-teachers'), data.teacherRatio.labels, data.teacherRatio.data, [
        '#10b981', '#059669', '#047857', '#3b82f6', '#2563eb', '#6ee7b7', '#34d399', '#a7f3d0', '#60a5fa', '#1d4ed8',
    ]);

    const markers = @json($map);
    M.createMap('education-map', markers, {
        resize: true,
        cluster: true,
        center: [-3.25, 121.85],
        legendOnly: ['SD', 'SMP'],
        legend: true,
    });

    // Legend
    const legend = document.querySelector('#education-map').closest('.bg-white');
    const lg = document.createElement('div');
    lg.className = 'px-5 py-2 flex gap-4 flex-wrap';
    lg.innerHTML = `
        <span class="inline-flex items-center text-[12px] text-gray-700"><span class="w-3 h-3 rounded-full bg-emerald-500 mr-1.5"></span>SD</span>
        <span class="inline-flex items-center text-[12px] text-gray-700"><span class="w-3 h-3 rounded-full bg-blue-600 mr-1.5"></span>SMP</span>
    `;
    legend.insertBefore(lg, legend.querySelector('#education-map'));
});
</script>
@endpush
