@extends('layouts.app')

@section('title', 'Dataset — '.$dataset)

@section('content')
<div class="page-transition space-y-4">

    <x-page-title :title="$dataset" :subtitle="$topic ?: 'Dataset '.$sectorLabel.' dari portal Satu Data Morowali'">
        <x-slot:actions>
            <a href="{{ route('public-data.show', $sector) }}"
               class="inline-flex items-center gap-1.5 text-[12px] font-bold text-gray-600 border border-gray-200 hover:border-gray-300 rounded-xl px-3 py-2 transition">
                ← Kembali ke {{ $sectorLabel }}
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
                Sektor ini belum pernah disinkronkan. Detail mengikuti dari data yang tersimpan.
            </div>
        </div>
    @endif

    {{-- Metadata dataset --}}
    <x-card title="Informasi Dataset" subtitle="Metadata yang diambil dari halaman sumber" icon="📇">
        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3 text-[12.5px]">
            @forelse ($metadata as $key => $value)
                <div>
                    <dt class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ $key }}</dt>
                    <dd class="mt-0.5 font-semibold text-gray-700">{{ $value }}</dd>
                </div>
            @empty
                <div class="text-gray-500">Metadata tidak tersedia untuk dataset ini.</div>
            @endforelse
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Produsen</dt>
                <dd class="mt-0.5 font-semibold text-gray-700">{{ $metadata['producer'] ?? '—' }}</dd>
            </div>
        </dl>

        <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ $sourceUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 text-[12px] font-bold text-white bg-gray-800 hover:bg-gray-700 rounded-xl px-4 py-2 transition">
                Buka Halaman Sumber di Portal ↑
            </a>
            <a href="{{ route('public-data.show', $sector) }}"
               class="text-[12px] font-bold text-violet-600 hover:text-violet-800">
                ← Kembali ke daftar {{ $sectorLabel }}
            </a>
        </div>
    </x-card>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-stat-card label="Record" :value="number_format($summary['records'])" icon="🗃️" color="violet"/>
        <x-stat-card label="Lokasi" :value="number_format($summary['locations']->count())" icon="📍" color="blue"/>
        <x-stat-card label="Indikator" :value="number_format($summary['indicators']->count())" icon="📐" color="emerald"/>
        <x-stat-card label="Tahun" :value="number_format($summary['years']->count())" icon="📅" color="amber"/>
    </div>

    {{-- Struktur tabel sumber --}}
    @if (count($headers) > 0)
        <x-card title="Struktur Data Sumber" subtitle="Kolom pada tabel asli portal" icon="🧱" :padding="false">
            <div class="flex flex-wrap gap-1.5 px-4 py-3">
                @foreach ($headers as $header)
                    <span class="text-[11.5px] font-semibold text-gray-600 bg-gray-100 border border-gray-200 rounded-lg px-2.5 py-1">{{ $header }}</span>
                @endforeach
            </div>
        </x-card>
    @endif

    {{-- Grafik --}}
    @if (count($chart['labels']) > 0)
        <x-chart-card title="Tren {{ $sectorLabel }}" subtitle="Total nilai dataset ini sesuai tahun/lokasi" icon="📊"
                      class="lg:col-span-1">
            <div class="h-72"><canvas id="chart-public-dataset"></canvas></div>
        </x-chart-card>
    @endif

    {{-- Tabel record dataset --}}
    <x-card :title="'Record '.$sectorLabel" subtitle="Hasil normalisasi untuk dataset ini" icon="📋" :padding="false">
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
                @forelse ($records as $record)
                    <tr class="hover:bg-violet-50/40 transition">
                        <td class="px-4 py-2.5 text-gray-600">{{ $record->year ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ $record->location }}</td>
                        <td class="px-4 py-2.5 text-gray-600 max-w-[300px] truncate" title="{{ $record->indicator }}">{{ $record->indicator }}</td>
                        <td class="px-4 py-2.5 text-right font-bold text-gray-800">
                            {{ number_format((float) $record->value, str_contains($record->value, '.') ? 2 : 0, ',', '.') }}
                            @if ($record->unit)
                                <span class="font-normal text-[11px] text-gray-400">{{ $record->unit }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center">
                            <div class="text-4xl mb-2">🔎</div>
                            <p class="text-[13px] font-semibold text-gray-500">Tidak ada record tersimpan untuk dataset ini.</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Contoh baris sumber --}}
    @if (count($sampleRow) > 0)
        <x-card title="Contoh Baris dari Sumber" subtitle="Baris pertama pada tabel asli portal" icon="📄" :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-[12px]">
                    <thead>
                    <tr class="bg-gray-50 text-gray-500 uppercase tracking-wide text-[11px]">
                        <th class="px-4 py-3 font-bold">Kolom</th>
                        <th class="px-4 py-3 font-bold">Nilai</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @foreach ($sampleRow as $key => $value)
                        <tr class="hover:bg-violet-50/40 transition">
                            <td class="px-4 py-2.5 text-gray-600 max-w-[240px] truncate" title="{{ $key }}">{{ $key }}</td>
                            <td class="px-4 py-2.5 font-semibold text-gray-800">{{ $value ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
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
        window.Mjcc.charts.makeBar(document.getElementById('chart-public-dataset'), chartData.labels, [{
            data: chartData.data,
            label: 'Nilai',
            backgroundColor: 'rgba(16,185,129,0.8)',
            borderColor: 'rgba(16,185,129,1)',
            borderWidth: 1,
        }]);
    }
});
</script>
@endpush