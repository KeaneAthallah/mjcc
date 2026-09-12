{{-- Satu Data Morowali --}}
@php
    $records = $records ?? null;
    $chart = $chart ?? ['labels' => [], 'data' => []];
    $indicators = $indicators ?? collect();
    $years = $years ?? collect();
    $datasets = $datasets ?? collect();
    $locations = $locations ?? 0;
    $datasetDistribution = $datasetDistribution ?? ['labels' => [], 'data' => []];
    $insights = $insights ?? [];
@endphp

<x-source-widget-header icon="🗂️" title="Satu Data Morowali" subtitle="Portal data terbuka satu data daerah Morowali" key="satudata" accent="violet">

@include('partials.source-insights', ['insights' => $insights])

<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <x-stat-card label="Total Record" :value="number_format($records?->total() ?? 0)" icon="📦" color="blue"/>
    <x-stat-card label="Dataset" :value="number_format($datasets->count())" icon="📚" color="violet"/>
    <x-stat-card label="Wilayah" :value="number_format($locations)" icon="📍" color="emerald"/>
    <x-stat-card label="Tahun Tersedia" :value="$years->count()" icon="📅" color="amber"/>
</div>

@if (count($chart['data']) > 0 || count($datasetDistribution['data']) > 0)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @if (count($chart['data']) > 0)
            <x-card title="Nilai per Tahun" subtitle="Agregasi nilai berdasarkan tahun" icon="📈">
                <div class="h-64"><canvas id="chart-satudata-year"></canvas></div>
            </x-card>
        @endif
        @if (count($datasetDistribution['data']) > 0)
            <x-card title="Komposisi per Dataset" subtitle="Distribusi record antar dataset" icon="🧩">
                <div class="h-64"><canvas id="chart-satudata-dataset"></canvas></div>
            </x-card>
        @endif
    </div>
@endif

<x-card title="Filter" subtitle="Saring data indikator dan tahun" icon="🔍">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <select name="indicator" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200">
            <option value="">Semua Indikator</option>
            @foreach ($indicators as $ind)
                <option value="{{ $ind }}" @selected(request('indicator') === $ind)>{{ $ind }}</option>
            @endforeach
        </select>
        <select name="year" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200">
            <option value="">Semua Tahun</option>
            @foreach ($years as $y)
                <option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 text-[12px] font-bold text-white bg-violet-600 hover:bg-violet-700 rounded-xl px-4 py-2.5 transition">Terapkan</button>
            <a href="{{ route('public-data.source', 'satudata') }}" class="text-[12px] font-bold text-gray-500 border border-gray-200 rounded-xl px-3 py-2.5 transition">Reset</a>
        </div>
    </form>
</x-card>

<x-card title="Data Terbuka" subtitle="Baris data terperinci per indikator dan wilayah" icon="📋" :padding="false">
    @if ($records?->isEmpty())
        <x-empty-state icon="🗂️" title="Belum ada data Satu Data" message="Jalankan sinkronisasi untuk menarik data dari portal Satu Data Morowali."/>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-[12px]">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 uppercase tracking-wide text-[11px]">
                        <th class="px-4 py-3 font-bold">Indikator</th>
                        <th class="px-4 py-3 font-bold">Dataset</th>
                        <th class="px-4 py-3 font-bold">Wilayah</th>
                        <th class="px-4 py-3 font-bold">Tahun</th>
                        <th class="px-4 py-3 font-bold text-right">Nilai</th>
                        <th class="px-4 py-3 font-bold">Satuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records ?? [] as $row)
                        <tr class="hover:bg-violet-50/40 transition">
                            <td class="px-4 py-2.5 text-gray-800 font-semibold max-w-[280px] truncate" title="{{ $row->indicator }}">{{ $row->indicator }}</td>
                            <td class="px-4 py-2.5 text-gray-600 max-w-[200px] truncate">{{ $row->dataset ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $row->location ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $row->year ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-gray-800">{{ $row->value !== null ? number_format($row->value, 0, ',', '.') : '—' }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-500">{{ $row->unit ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-[13px] text-gray-500">Tidak ada data untuk filter yang dipilih.</td>
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
    const palette = ['#8b5cf6', '#3b82f6', '#f59e0b', '#10b981', '#ef4444', '#06b6d4'];

    const yearLabels = @json($chart['labels'] ?? []);
    const yearData = @json($chart['data'] ?? []);
    if (yearLabels.length > 0 && document.getElementById('chart-satudata-year')) {
        window.Mjcc.charts.makeBar(document.getElementById('chart-satudata-year'), yearLabels, [{
            data: yearData,
            label: 'Nilai',
            backgroundColor: 'rgba(139,92,246,0.8)',
            borderWidth: 1,
        }]);
    }

    const dsLabels = @json($datasetDistribution['labels'] ?? []);
    const dsData = @json($datasetDistribution['data'] ?? []);
    if (dsLabels.length > 0 && document.getElementById('chart-satudata-dataset')) {
        window.Mjcc.charts.makeDoughnut(document.getElementById('chart-satudata-dataset'), dsLabels, dsData, palette.slice(0, dsLabels.length));
    }
});
</script>
@endpush