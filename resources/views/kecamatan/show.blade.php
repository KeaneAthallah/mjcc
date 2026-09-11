@extends('layouts.app')

@section('title', 'Profil Kecamatan '.$row['kecamatan']['name'])

@section('content')
<div class="page-transition space-y-4">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('kecamatan.overview') }}" class="text-[12px] font-bold text-gray-500 hover:text-emerald-700">← Semua Kecamatan</a>
            <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 mt-1">{{ $row['kecamatan']['name'] }}</h1>
            <p class="text-[12px] text-gray-500">
                {{ $row['kecamatan']['kelurahan'] }} kelurahan/desa · {{ number_format($row['kecamatan']['population']) }} jiwa
            </p>
        </div>
        <div class="flex items-center gap-2">
            <x-status-indicator
                :status="$row['status']['status']"
                :score="$row['status']['score']"
                label="{{ $row['status']['label'] }}"
            />
            <a href="{{ route('kecamatan.show', $row['kecamatan']['id']).'?refresh=1' }}"
               class="px-4 py-2 rounded-xl bg-emerald-100 text-emerald-800 text-[12px] font-bold hover:bg-emerald-200">🔄 Muat Ulang</a>
            @canwrite('kecamatan')
                <a href="{{ route('master.kecamatans.show', $row['kecamatan']['id']) }}"
                   class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 text-[12px] font-bold hover:bg-gray-200">🗂️ Kelola Data</a>
            @endcanwrite
        </div>
    </div>

    {{-- Status per sektor --}}
    <x-dashboard-section title="Status Sektor" subtitle="Skor per sektor dari aturan data nyata" icon="🛰️" :pad="false">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-5">
            @foreach ($row['status']['sector_details'] as $sector)
                <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[12px] font-bold text-gray-700">{{ $sector['label'] }}</span>
                        <x-status-indicator :status="$sector['status']" :score="$sector['score']" compact
                                            label="{{ $sector['score'] !== null ? number_format($sector['score']) : '—' }}"/>
                    </div>
                    <div class="space-y-1.5">
                        @forelse ($sector['rules'] as $rule)
                            <div class="flex justify-between text-[10px] text-gray-500">
                                <span class="truncate pr-2">{{ $rule['label'] }}</span>
                                <span>{{ $rule['score'] !== null ? number_format($rule['score']).'%' : 'tdk ada data' }}</span>
                            </div>
                            <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden mt-0.5">
                                <div @class([
                                    'h-full',
                                    'bg-emerald-500' => $rule['score'] !== null && $rule['score'] >= 85,
                                    'bg-amber-500' => $rule['score'] !== null && $rule['score'] >= 70 && $rule['score'] < 85,
                                    'bg-orange-500' => $rule['score'] !== null && $rule['score'] >= 50 && $rule['score'] < 70,
                                    'bg-red-500' => $rule['score'] !== null && $rule['score'] < 50,
                                ]) style="width: {{ min($rule['score'] ?? 0, 100) }}%"></div>
                            </div>
                        @empty
                            <p class="text-[11px] text-gray-400">Tidak ada data sektor.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </x-dashboard-section>

    {{-- KPI operasional --}}
    <x-kpi-grid cols="3" label="Kondisi Operasional">
        <x-metric-comparison label="Sekolah" value="{{ $row['counts']['sekolah'] }}" icon="🏫" color="green" footer="Baik {{ $row['counts']['sekolah_baik'] }} · {{ number_format($row['counts']['siswa']) }} siswa · {{ $row['counts']['guru'] }} guru"/>
        <x-metric-comparison label="Fasilitas Kesehatan" value="{{ $row['counts']['faskes'] }}" icon="🏥" color="green" footer="Aktif {{ $row['counts']['faskes_aktif'] }} · {{ $row['counts']['dokter'] }} dokter · {{ $row['counts']['perawat'] }} perawat · {{ $row['counts']['bidan'] }} bidan"/>
        <x-metric-comparison label="Poskamling" value="{{ $row['counts']['poskamling'] }}" icon="🛡️" color="{{ $row['counts']['poskamling_aktif'] < $row['counts']['poskamling'] ? 'red' : 'green' }}" footer="Aktif {{ $row['counts']['poskamling_aktif'] }} · ⚠ Nonaktif {{ $row['counts']['poskamling'] - $row['counts']['poskamling_aktif'] }}"/>
        <x-metric-comparison label="Tipkamtikmas" value="{{ $row['counts']['tipkamtikmas'] }}" icon="🪖" color="blue" footer="Aktif {{ $row['counts']['tipkamtikmas_aktif'] }}"/>
        <x-metric-comparison label="Pasar" value="{{ $row['counts']['pasar'] }}" icon="🏪" color="amber" footer="Aktif {{ $row['counts']['pasar_aktif'] }}"/>
        <x-metric-comparison label="Polsek" value="{{ $row['counts']['polsek'] }}" icon="🚓" color="teal" footer="Kantor/pos di kecamatan"/>
    </x-kpi-grid>

    @php
        $kecOpenAlertCounts = [
            'critical' => $row['open_alerts']->where('severity', 'critical')->count(),
            'warning' => $row['open_alerts']->where('severity', 'warning')->count(),
            'info' => $row['open_alerts']->where('severity', 'info')->count(),
            'total' => $row['open_alerts']->count(),
        ];
    @endphp

    {{-- Alert terbuka --}}
    <x-dashboard-section title="Alert Terbuka" subtitle="Masalah berjalan yang masih dalam penanganan" icon="🚨"
                         :pad="false">
        <x-slot:actions>
            <x-alert-summary-badges :counts="$kecOpenAlertCounts" :link="route('alerts.index', ['kecamatan' => $row['kecamatan']['id']])"/>
        </x-slot:actions>
        @if ($row['open_alerts']->isNotEmpty())
            @php
                $kecVisibleAlerts = $row['open_alerts']->take(6);
            @endphp
            <div class="divide-y divide-gray-50">
                @foreach ($kecVisibleAlerts as $alert)
                    <div class="p-3">
                        <x-command-alert :alert="$alert" :transition="true"/>
                    </div>
                @endforeach
            </div>
            @if ($row['open_alerts']->count() > count($kecVisibleAlerts))
                <div class="px-5 py-3 border-t border-gray-100 text-right">
                    <a href="{{ route('alerts.index', ['kecamatan' => $row['kecamatan']['id']]) }}"
                       class="text-[12px] font-bold text-emerald-700 hover:text-emerald-900 hover:underline">
                        Lihat semua ({{ $row['open_alerts']->count() }}) →
                    </a>
                </div>
            @endif
        @else
            <div class="p-5 text-center">
                <div class="text-4xl mb-2">✅</div>
                <h4 class="text-[14px] font-extrabold text-gray-700">Tidak ada masalah berjalan</h4>
                <p class="text-[12px] text-gray-400 mt-1">Semua indikator di kecamatan ini dalam kondisi baik.</p>
            </div>
        @endif
    </x-dashboard-section>
</div>
@endsection