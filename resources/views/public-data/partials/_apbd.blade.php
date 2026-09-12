{{-- APBD --}}
@php
    $records = $records ?? collect();
    $year = $year ?? (int) date('Y');
    $years = $years ?? collect();
    $totalPendapatan = $totalPendapatan ?? 0;
    $totalBelanja = $totalBelanja ?? 0;
    $categories = $categories ?? collect();
    $realizationRate = $realizationRate ?? 0;
    $fiskal = $fiskal ?? 0;
    $insights = $insights ?? [];
@endphp

<x-source-widget-header icon="💰" title="Monitoring APBD" subtitle="Anggaran Pendapatan dan Belanja Daerah dari DJPK Kemenkeu" key="apbd" accent="violet">

@include('partials.source-insights', ['insights' => $insights])

<div class="grid grid-cols-2 md:grid-cols-5 gap-3">
    <x-stat-card label="Total APBD" :value="'Rp ' . number_format($totalPendapatan / 1000000000, 1, ',', '.') . ' M'" icon="💰" color="emerald"/>
    <x-stat-card label="Pendapatan" :value="'Rp ' . number_format($totalPendapatan / 1000000000, 1, ',', '.') . ' M'" icon="📈" color="blue"/>
    <x-stat-card label="Belanja" :value="'Rp ' . number_format($totalBelanja / 1000000000, 1, ',', '.') . ' M'" icon="📉" color="red"/>
    <x-stat-card label="Realisasi" :value="$realizationRate . '%'" icon="🎯" color="amber"/>
    <x-stat-card label="Tahun" :value="(string) $year" icon="📅" color="violet"/>
</div>

{{-- Tahun Filter --}}
<x-card title="Filter Tahun" subtitle="Pilih tahun anggaran" icon="🔍">
    <div class="flex flex-wrap gap-2">
        @foreach ($years as $y)
            <a href="{{ route('public-data.source', ['sourceKey' => 'apbd', 'year' => $y]) }}"
               class="px-4 py-2 rounded-xl text-[12px] font-bold transition {{ (int) $y === $year ? 'bg-violet-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $y }}
            </a>
        @endforeach
    </div>
</x-card>

{{-- Grafik --}}
@if ($records->isNotEmpty())
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <x-card title="Pendapatan vs Belanja" subtitle="Perbandingan total anggaran" icon="📊">
            <div class="h-72"><canvas id="chart-apbd-comparison"></canvas></div>
        </x-card>
        <x-card title="Komposisi per Kategori" subtitle="Distribusi anggaran" icon="🧮">
            <div class="h-72"><canvas id="chart-apbd-composition"></canvas></div>
        </x-card>
    </div>
@endif

{{-- Tabel --}}
<x-card title="Data APBD" subtitle="Anggaran Pendapatan dan Belanja Daerah Tahun {{ $year }}" icon="📋" :padding="false">
    @if ($records->isEmpty())
        <x-empty-state icon="💰" title="Belum ada data APBD" message="Data APBD belum berhasil diambil dari sumber DJPK Kemenkeu."/>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-[12px]">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 uppercase tracking-wide text-[11px]">
                        <th class="px-4 py-3 font-bold">Indikator</th>
                        <th class="px-4 py-3 font-bold">Kategori</th>
                        <th class="px-4 py-3 font-bold text-right">Target</th>
                        <th class="px-4 py-3 font-bold text-right">Realisasi</th>
                        <th class="px-4 py-3 font-bold text-right">%</th>
                        <th class="px-4 py-3 font-bold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($records as $r)
                        <tr class="hover:bg-violet-50/40 transition">
                            <td class="px-4 py-2.5 text-gray-800 font-semibold max-w-[300px] truncate" title="{{ $r->indicator }}">{{ $r->indicator }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $r->category ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-800">{{ $r->target_value ? 'Rp ' . number_format($r->target_value, 0, ',', '.') : '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-gray-800">{{ $r->realization_value ? 'Rp ' . number_format($r->realization_value, 0, ',', '.') : '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-bold {{ ($r->percentage ?? 0) >= 80 ? 'text-emerald-600' : (($r->percentage ?? 0) >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                                {{ $r->formattedPercentage() }}
                            </td>
                            <td class="px-4 py-2.5">
                                @php $status = $r->realizationStatus(); @endphp
                                <x-badge color="{{ $status === 'Terealisasi' ? 'green' : ($status === 'Hampir Terealisasi' ? 'amber' : 'red') }}">{{ $status }}</x-badge>
                            </td>
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
    const records = @json($records);
    if (records.length > 0) {
        const categories = {};
        records.forEach(r => {
            const cat = r.category || 'Lainnya';
            categories[cat] = (categories[cat] || 0) + (Number(r.realization_value) || 0);
        });

        const catLabels = Object.keys(categories);
        const catData = Object.values(categories);
        const palette = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];

        if (document.getElementById('chart-apbd-comparison')) {
            window.Mjcc.charts.makeBar(document.getElementById('chart-apbd-comparison'), catLabels, [{
                data: catData,
                label: 'Realisasi',
                backgroundColor: palette.slice(0, catLabels.length),
                borderWidth: 1,
            }]);
        }

        if (document.getElementById('chart-apbd-composition')) {
            window.Mjcc.charts.makeDoughnut(document.getElementById('chart-apbd-composition'), catLabels, catData, palette.slice(0, catLabels.length));
        }
    }
});
</script>
@endpush