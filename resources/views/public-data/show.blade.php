@extends('layouts.app')

@section('title', $sectorLabel)

@section('content')
<div class="page-transition space-y-4">

    <x-page-title :title="$sectorLabel" subtitle="Data statistik sektor dari portal Satu Data Morowali">
        <x-slot:actions>
            <a href="{{ route('public-data.index') }}"
               class="inline-flex items-center gap-1.5 text-[12px] font-bold text-gray-600 border border-gray-200 hover:border-gray-300 rounded-xl px-3 py-2 transition">
                ← Kembali
            </a>
        </x-slot:actions>
    </x-page-title>

    {{-- Status sinkronisasi --}}
    @if ($sync['status'] === 'berhasil')
        <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[12.5px] text-emerald-800">
            <span class="text-lg leading-none">✅</span>
            <div>
                Sinkronisasi terakhir <strong>{{ $sync['last_success_at']?->translatedFormat('d M Y H:i') }}</strong>,
                menghimpun <strong>{{ number_format($sync['record_count']) }}</strong> record.
            </div>
        </div>
    @elseif ($sync['status'] === 'gagal')
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[12.5px] text-amber-800">
            <span class="text-lg leading-none">⚠️</span>
            <div>
                Sumber data belum berhasil diperbarui. Menampilkan data terakhir yang berhasil diperoleh.
                @if ($sync['last_error'])
                    <span class="block mt-1 text-[11.5px] text-amber-700">Alasan: {{ $sync['last_error'] }}</span>
                @endif
            </div>
        </div>
    @else
        <div class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-[12.5px] text-gray-600">
            <span class="text-lg leading-none">🕘</span>
            <div>
                Sektor ini belum pernah disinkronkan. Data akan muncul setelah sinkronisasi harian pertama berjalan.
            </div>
        </div>
    @endif

    {{-- Statistik --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-stat-card label="Record" :value="number_format($summary['records'])" icon="🗃️" color="violet"/>
        <x-stat-card label="Dataset" :value="number_format($summary['datasets'])" icon="📦" color="emerald"/>
        <x-stat-card label="Lokasi" :value="number_format($summary['locations'])" icon="📍" color="blue"/>
        <x-stat-card label="Tahun" :value="number_format($summary['years'])" icon="📅" color="amber"/>
    </div>

    {{-- Filter --}}
    @if ($summary['records'] > 0)
        <x-card title="Filter & Pencarian" subtitle="Saring dataset menurut tahun, wilayah, atau indikator" icon="🔍">
            <form method="GET" action="{{ route('public-data.show', $sector) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <select name="dataset"
                        class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200 focus:border-violet-500">
                    <option value="">Semua Dataset</option>
                    @foreach ($filters['datasets'] as $d)
                        <option value="{{ $d }}" @selected($active['dataset'] === $d)>{{ $d }}</option>
                    @endforeach
                </select>
                <select name="year"
                        class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200 focus:border-violet-500">
                    <option value="">Semua Tahun</option>
                    @foreach ($filters['years'] as $y)
                        <option value="{{ $y }}" @selected((int) $active['year'] === (int) $y)>{{ $y }}</option>
                    @endforeach
                </select>
                <select name="location"
                        class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200 focus:border-violet-500">
                    <option value="">Semua Lokasi</option>
                    @foreach ($filters['locations'] as $l)
                        <option value="{{ $l }}" @selected($active['location'] === $l)>{{ $l }}</option>
                    @endforeach
                </select>
                <select name="indicator"
                        class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200 focus:border-violet-500">
                    <option value="">Semua Indikator</option>
                    @foreach ($filters['indicators'] as $ind)
                        <option value="{{ $ind }}" @selected($active['indicator'] === $ind)>{{ $ind }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 text-[12px] font-bold text-white bg-violet-600 hover:bg-violet-700 rounded-xl px-4 py-2.5 transition">Terapkan</button>
                    <a href="{{ route('public-data.show', $sector) }}"
                       class="text-[12px] font-bold text-gray-500 hover:text-gray-700 border border-gray-200 rounded-xl px-3 py-2.5 transition">Reset</a>
                </div>
            </form>
        </x-card>

        {{-- Grafik --}}
        @if (count($chart['labels']) > 0)
            <x-chart-card title="Tren {{ $sectorLabel }}" subtitle="Total nilai terhimpun sesuai filter aktif" icon="📊"
                          class="lg:col-span-1">
                <div class="h-72"><canvas id="chart-public-data"></canvas></div>
            </x-chart-card>
        @endif

        {{-- Tabel data --}}
        <x-card :title="'Dataset '.$sectorLabel" subtitle="Record hasil normalisasi dari portal" icon="📋" :padding="false">
            @if ($records->isEmpty())
                <div class="p-10 text-center">
                    <div class="text-4xl mb-2">🔎</div>
                    <p class="text-[13px] font-semibold text-gray-500">Tidak ada data yang cocok dengan filter.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-[12px]">
                        <thead>
                        <tr class="bg-gray-50 text-gray-500 uppercase tracking-wide text-[11px]">
                            <th class="px-4 py-3 font-bold">Dataset</th>
                            <th class="px-4 py-3 font-bold">Tahun</th>
                            <th class="px-4 py-3 font-bold">Lokasi</th>
                            <th class="px-4 py-3 font-bold">Indikator</th>
                            <th class="px-4 py-3 font-bold text-right">Nilai</th>
                            <th class="px-4 py-3 font-bold text-right">Sumber</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @foreach ($records as $record)
                            <tr class="hover:bg-violet-50/40 transition">
                                <td class="px-4 py-2.5 text-gray-800">
                                    <a href="{{ route('public-data.dataset', ['sector' => $sector, 'dataset' => $record->dataset]) }}"
                                       class="font-semibold max-w-[260px] truncate block hover:text-violet-700 hover:underline" title="{{ $record->dataset }}">
                                        {{ $record->dataset }}
                                    </a>
                                    <div class="text-[11px] text-gray-400">{{ $record->topic }}</div>
                                </td>
                                <td class="px-4 py-2.5 text-gray-600">{{ $record->year ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-gray-600">{{ $record->location }}</td>
                                <td class="px-4 py-2.5 text-gray-600 max-w-[200px] truncate" title="{{ $record->indicator }}">{{ $record->indicator }}</td>
                                <td class="px-4 py-2.5 text-right font-bold text-gray-800">
                                    {{ number_format((float) $record->value, str_contains($record->value, '.') ? 2 : 0, ',', '.') }}
                                    @if ($record->unit)
                                        <span class="font-normal text-[11px] text-gray-400">{{ $record->unit }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <a href="{{ $record->source_url }}" target="_blank" rel="noopener"
                                       class="text-[11px] font-bold text-violet-600 hover:text-violet-800">Portal ↑</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <x-pagination :rows="$records"/>
            @endif
        </x-card>
    @else
        <x-card title="Belum Ada Data" subtitle="Menunggu sinkronisasi pertama" icon="🗃️">
            <div class="p-6 text-center">
                <div class="text-5xl mb-3">🕸️</div>
                <p class="text-[13px] text-gray-500">
                    Belum ada data terhimpun untuk sektor ini. Sinkronisasi harian akan mengisinya dari portal Satu Data Morowali.
                </p>
            </div>
        </x-card>
    @endif

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const chartData = @json($chart);

    if (chartData.labels.length > 0) {
        window.Mjcc.charts.makeBar(document.getElementById('chart-public-data'), chartData.labels, [{
            data: chartData.data,
            label: 'Nilai',
            backgroundColor: 'rgba(139,92,246,0.8)',
            borderColor: 'rgba(139,92,246,1)',
            borderWidth: 1,
        }]);
    }
});
</script>
@endpush