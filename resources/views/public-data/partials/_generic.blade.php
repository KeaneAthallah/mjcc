{{-- Generic source display for ATS, Dapodik, SatuData --}}
@php
    $records = $records ?? null;
    $chart = $chart ?? ['labels' => [], 'data' => []];
    $indicators = $indicators ?? collect();
    $years = $years ?? collect();
@endphp

<div class="grid grid-cols-2 md:grid-cols-3 gap-3">
    <x-stat-card label="Total Record" :value="number_format($records?->total() ?? 0)" icon="🗃️" color="violet"/>
    <x-stat-card label="Indikator" :value="number_format($indicators->count())" icon="📐" color="emerald"/>
    <x-stat-card label="Tahun" :value="number_format($years->count())" icon="📅" color="amber"/>
</div>

@if ($indicators->isNotEmpty() || $years->isNotEmpty())
    <x-card title="Filter" subtitle="Saring data berdasarkan indikator atau tahun" icon="🔍">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @if ($indicators->isNotEmpty())
                <select name="indicator" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200">
                    <option value="">Semua Indikator</option>
                    @foreach ($indicators as $i)
                        <option value="{{ $i }}" @selected(request('indicator') === $i)>{{ $i }}</option>
                    @endforeach
                </select>
            @endif
            @if ($years->isNotEmpty())
                <select name="year" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200">
                    <option value="">Semua Tahun</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            @endif
            <div class="flex gap-2">
                <button type="submit" class="flex-1 text-[12px] font-bold text-white bg-violet-600 hover:bg-violet-700 rounded-xl px-4 py-2.5 transition">Terapkan</button>
                <a href="{{ route('public-data.source', request()->route('sourceKey')) }}" class="text-[12px] font-bold text-gray-500 border border-gray-200 rounded-xl px-3 py-2.5 transition">Reset</a>
            </div>
        </form>
    </x-card>
@endif

@if (! empty($chart['labels']))
    <x-card title="Grafik" subtitle="Visualisasi data" icon="📊">
        <div class="h-72"><canvas id="chart-generic"></canvas></div>
    </x-card>
@endif

<x-card title="Data" subtitle="Daftar record dari sumber" icon="📋" :padding="false">
    @if ($records?->isEmpty())
        <x-empty-state icon="📭" title="Belum ada data" message="Data dari sumber ini belum berhasil diambil."/>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-[12px]">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 uppercase tracking-wide text-[11px]">
                        <th class="px-4 py-3 font-bold">Tahun</th>
                        <th class="px-4 py-3 font-bold">Lokasi</th>
                        <th class="px-4 py-3 font-bold">Indikator</th>
                        <th class="px-4 py-3 font-bold text-right">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records ?? [] as $record)
                        <tr class="hover:bg-violet-50/40 transition">
                            <td class="px-4 py-2.5 text-gray-600">{{ $record->year ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $record->location }}</td>
                            <td class="px-4 py-2.5 text-gray-600 max-w-[300px] truncate" title="{{ $record->indicator }}">{{ $record->indicator }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-gray-800">
                                {{ $record->value !== null ? number_format((float) $record->value, 2, ',', '.') : '—' }}
                                @if ($record->unit)
                                    <span class="font-normal text-[11px] text-gray-400">{{ $record->unit }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-[13px] text-gray-500">Tidak ada data untuk filter yang dipilih.</td>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const chartData = @json($chart);
    if (chartData.labels && chartData.labels.length > 0) {
        window.Mjcc.charts.makeBar(document.getElementById('chart-generic'), chartData.labels, [{
            data: chartData.data,
            backgroundColor: 'rgba(139,92,246,0.8)',
            borderColor: 'rgba(139,92,246,1)',
            borderWidth: 1,
        }]);
    }
});
</script>
@endpush