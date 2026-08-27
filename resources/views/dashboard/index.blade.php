@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<div class="space-y-5 page-transition">

    {{-- Welcome banner --}}
    <div class="bg-gradient-to-r from-emerald-700 via-emerald-600 to-blue-700 rounded-2xl p-6 text-white shadow-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold">Selamat Datang, {{ $currentUser->name }}! 👋</h1>
            <p class="text-emerald-100 text-[13px] mt-1">DASHBOARD PEMANTAUAN KABUPATEN MOROWALI</p>
            <p class="text-emerald-50/80 text-[12px] mt-1">Total penduduk: <strong class="text-white">{{ number_format($stats['population'], 0, ',', '.') }} jiwa</strong> · {{ $stats['kecamatan'] }} Kecamatan · {{ $stats['kelurahan'] }} Kelurahan/Desa</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('education.dashboard') }}" class="px-4 py-2 rounded-xl bg-white/15 hover:bg-white/25 text-white text-[12px] font-bold">🎓 Pendidikan</a>
            <a href="{{ route('security.dashboard') }}" class="px-4 py-2 rounded-xl bg-white/15 hover:bg-white/25 text-white text-[12px] font-bold">🛡️ Ketertiban</a>
            <a href="{{ route('health.dashboard') }}" class="px-4 py-2 rounded-xl bg-white/15 hover:bg-white/25 text-white text-[12px] font-bold">🏥 Kesehatan</a>
            <a href="{{ route('maps.index') }}" class="px-4 py-2 rounded-xl bg-white/15 hover:bg-white/25 text-white text-[12px] font-bold">🗺️ Peta</a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        <x-stat-card label="Sekolah (Total)" value="{{ number_format($stats['total_sekolah']) }}" icon="🏫" color="green"/>
        <x-stat-card label="Siswa" value="{{ number_format($stats['total_siswa']) }}" icon="🎓" color="blue" footer="{{ $stats['total_guru'] }} Guru"/>
        <x-stat-card label="Fasilitas Kesehatan" value="{{ number_format($stats['total_faskes']) }}" icon="🏥" color="green"/>
        <x-stat-card label="Tenaga Medis" value="{{ number_format($stats['total_dokter'] + $stats['total_perawat'] + $stats['total_bidan']) }}" icon="🩺" color="blue" footer="{{ $stats['total_dokter'] }} Dokter"/>
        <x-stat-card label="Tipkamtikmas" value="{{ number_format($stats['total_tipkamtikmas']) }}" icon="🪖" color="red"/>
        <x-stat-card label="Poskamling & Pasar" value="{{ number_format($stats['total_poskamling'] + $stats['total_pasar']) }}" icon="🏪" color="amber"/>
    </div>

    {{-- Charts row 1 --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <x-card title="Perbandingan Sektor per Kecamatan" icon="📊" class="lg:col-span-2">
            <div class="h-72">
                <canvas id="chart-comparison"></canvas>
            </div>
        </x-card>
        <x-card title="Komposisi Infrastruktur" icon="🧮">
            <div class="h-72">
                <canvas id="chart-infra"></canvas>
            </div>
        </x-card>
    </div>

    {{-- Charts row 2 --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <x-card title="Jumlah Siswa per Kecamatan" icon="🎓" class="lg:col-span-2">
            <div class="h-72">
                <canvas id="chart-students"></canvas>
            </div>
        </x-card>
        <x-card title="Tenaga Kesehatan" icon="🩺">
            <div class="h-72">
                <canvas id="chart-workforce"></canvas>
            </div>
        </x-card>
    </div>

    {{-- Rankings --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <x-card title="Top Kecamatan · Sekolah" icon="🏆">
            <ol class="space-y-2.5">
                @foreach ($topSekolah as $index => $row)
                    <li class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full {{ $index === 0 ? 'bg-amber-100 text-amber-600' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center text-[11px] font-bold">{{ $index + 1 }}</span>
                        <span class="text-[13px] font-semibold text-gray-700 flex-1">{{ $row->name }}</span>
                        <x-badge color="{{ $index === 0 ? 'amber' : 'green' }}">{{ $row->schools_count }}</x-badge>
                    </li>
                @endforeach
            </ol>
        </x-card>
        <x-card title="Top Kecamatan · Poskamling" icon="🛡️">
            <ol class="space-y-2.5">
                @foreach ($topPoskamling as $index => $row)
                    <li class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full {{ $index === 0 ? 'bg-amber-100 text-amber-600' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center text-[11px] font-bold">{{ $index + 1 }}</span>
                        <span class="text-[13px] font-semibold text-gray-700 flex-1">{{ $row->name }}</span>
                        <x-badge color="blue">{{ $row->active_poskamling }}</x-badge>
                    </li>
                @endforeach
            </ol>
        </x-card>
        <x-card title="Top Kecamatan · Tenaga Medis" icon="👨‍⚕️">
            <ol class="space-y-2.5">
                @foreach ($topKesehatan as $index => $row)
                    <li class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full {{ $index === 0 ? 'bg-amber-100 text-amber-600' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center text-[11px] font-bold">{{ $index + 1 }}</span>
                        <span class="text-[13px] font-semibold text-gray-700 flex-1">{{ $row->name }}</span>
                        <x-badge color="teal">{{ $row->workers }}</x-badge>
                    </li>
                @endforeach
            </ol>
        </x-card>
    </div>

    {{-- Map --}}
    <x-card title="Persebaran Aset Morowali" icon="🗺️" :padding="false">
        <div class="flex gap-1 p-3 border-b border-gray-100 flex-wrap">
            @php
                $tabs = [
                    'sekolah' => ['label' => '🎓 Sekolah', 'active' => true],
                    'ketertiban' => ['label' => '🛡️ Ketertiban', 'active' => false],
                    'kesehatan' => ['label' => '🏥 Kesehatan', 'active' => false],
                ];
            @endphp
            @foreach ($tabs as $key => $tab)
                <button data-map-tab="{{ $key }}"
                        class="px-4 py-2 rounded-xl text-[12px] font-bold {{ $tab['active'] ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>
        <div id="map-tab-sekolah" class="map-tab h-[440px]"></div>
        <div id="map-tab-ketertiban" class="map-tab h-[440px] hidden"></div>
        <div id="map-tab-kesehatan" class="map-tab h-[440px] hidden"></div>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const C = window.Mjcc.charts;
    const M = window.Mjcc.maps;
    const data = @json($dashboardData);

    C.makeBar(document.getElementById('chart-comparison'), data.comparison.labels, data.comparison.datasets);

    C.makePolar(document.getElementById('chart-infra'), data.infra.labels, data.infra.data, data.palette.slice(0, 3));

    C.makeStackedBar(document.getElementById('chart-students'), data.students.labels, data.students.datasets);

    C.makeDoughnut(document.getElementById('chart-workforce'), data.workforce.labels, data.workforce.data, ['#2563eb', '#10b981', '#a7f3d0']);

    // Map tabs
    const mapData = {
        sekolah: @json($schoolMap),
        ketertiban: @json($securityMap),
        kesehatan: @json($healthMap),
    };
    const maps = {};

    function initMap(key) {
        if (maps[key]) { maps[key].invalidateSize(); return; }
        maps[key] = M.createMap('map-tab-' + key, mapData[key], {
            resize: true,
            cluster: true,
            center: [-3.25, 121.85],
        });
    }

    document.querySelectorAll('[data-map-tab]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.mapTab;
            document.querySelectorAll('[data-map-tab]').forEach(b => {
                b.classList.toggle('bg-emerald-600', b === btn);
                b.classList.toggle('text-white', b === btn);
                b.classList.remove('text-gray-600');
            });
            document.querySelectorAll('.map-tab').forEach(el => el.classList.add('hidden'));
            document.getElementById('map-tab-' + key).classList.remove('hidden');
            initMap(key);
        });
    });

    initMap('sekolah');
});
</script>
@endpush
