@extends('layouts.app')

@section('title', 'ATS Kemendikdasmen')

@section('content')
<div class="page-transition space-y-5">

    <x-page-title title="ATS Kemendikdasmen" subtitle="Data Anak Tidak Sekolah · Kabupaten Morowali & Morowali Utara">
        <x-slot:actions>
            <x-button href="{{ route('crawler.dashboard') }}" variant="ghost" size="sm">← Overview</x-button>
            <x-badge color="violet">DATA EKSTERNAL</x-badge>
        </x-slot:actions>
    </x-page-title>

    {{-- Source badge + last sync --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex items-center gap-3">
            <span class="w-11 h-11 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center text-xl">{{ $meta['icon'] }}</span>
            <div>
                <div class="font-extrabold text-gray-900 text-[14px]">Sumber: {{ $meta['source_label'] }}</div>
                <div class="text-[11px] text-gray-500">Sinkronisasi terakhir: {{ $lastRun?->finished_at?->diffForHumans() ?? $lastRun?->started_at?->diffForHumans() ?? 'belum pernah' }}</div>
            </div>
        </div>
        <div class="sm:ml-auto">
            @if ($source?->is_active && $lastRun?->status === 'success')
                <x-badge color="green">Sehat</x-badge>
            @elseif ($lastRun?->status === 'partial')
                <x-badge color="amber">Sebagian</x-badge>
            @elseif ($lastRun && $lastRun->status === 'failed')
                <x-badge color="red">Gagal</x-badge>
            @else
                <x-badge color="gray">Belum Ada Data</x-badge>
            @endif
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-stat-card label="Total Rekaman" value="{{ number_format($records->total()) }}" icon="👥" color="violet"/>
        @php
            $totalKids = $byKabupaten['7206'] ?? 0;
            $totalPiut = $byKabupaten['7212'] ?? 0;
        @endphp
        <x-stat-card label="Morowali" value="{{ number_format($totalKids) }}" icon="📍" color="green"/>
        <x-stat-card label="Morowali Utara" value="{{ number_format($totalPiut) }}" icon="📍" color="green"/>
        <x-stat-card label="Kecamatan" value="{{ number_format(count($kecamatans)) }}" icon="🗺️" color="blue"/>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <x-card title="Distribusi ATS per Kecamatan" icon="📊">
            <div class="h-64">
                @if (! empty($distribution['labels']))
                    <canvas id="ats-distribution"></canvas>
                @else
                    <x-empty-state title="Belum ada data" description="Data ATS akan tampil setelah sinkronisasi pertama."/>
                @endif
            </div>
        </x-card>
        <x-card title="ATS per Kabupaten" icon="🗺️">
            <div class="h-64">
                @if (array_sum($byKabupaten) > 0)
                    <canvas id="ats-kabupaten"></canvas>
                @else
                    <x-empty-state title="Belum ada data" description="Data ATS akan tampil setelah sinkronisasi pertama."/>
                @endif
            </div>
        </x-card>
    </div>

    {{-- Filter bar --}}
    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari nama/rekaman...">
        <select name="kabupaten" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200 focus:border-violet-500">
            <option value="">Semua Kabupaten</option>
            @foreach ($regions as $code => $name)
                <option value="{{ $code }}" @selected(request('kabupaten') === $code)>{{ $name }}</option>
            @endforeach
        </select>
        <select name="kecamatan" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200 focus:border-violet-500">
            <option value="">Semua Kecamatan</option>
            @foreach ($kecamatans as $code => $name)
                <option value="{{ $code }}" @selected(request('kecamatan') === $code)>{{ $name }}</option>
            @endforeach
        </select>
        <select name="category" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200 focus:border-violet-500">
            <option value="">Semua Kategori</option>
            @foreach ($kategori as $cat)
                <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
            @endforeach
        </select>
    </x-filter-bar>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($records->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Nama</th>
                            <th class="text-left px-2 py-3 font-bold">Kabupaten</th>
                            <th class="text-left px-2 py-3 font-bold">Kecamatan</th>
                            <th class="text-left px-2 py-3 font-bold">Kategori</th>
                            <th class="text-center px-2 py-3 font-bold">Peta</th>
                            <th class="text-right px-4 py-3 font-bold">Terakhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($records as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('crawler.ats.show', $record) }}" class="font-bold text-gray-800 hover:text-violet-600">{{ $record->name ?? $record->external_id }}</a>
                                </td>
                                <td class="px-2 py-3 text-gray-600">{{ $record->kabupaten_name ?? $record->kabupaten_code ?? '-' }}</td>
                                <td class="px-2 py-3 text-gray-600">{{ $record->kecamatan_name ?? '-' }}</td>
                                <td class="px-2 py-3"><x-badge color="violet">{{ $record->data['category'] ?? $record->record_type }}</x-badge></td>
                                <td class="px-2 py-3 text-center">
                                    @if ($record->latitude && $record->longitude)
                                        <span title="{{ $record->latitude }}, {{ $record->longitude }}">📍</span>
                                    @else
                                        <span class="text-gray-300">–</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-gray-400">{{ $record->last_seen_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$records" />
        @else
            <x-empty-state title="Belum ada data ATS" description="Data akan tampil setelah sinkronisasi ATS berjalan."/>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const C = window.Mjcc.charts;
    const distribution = @json($distribution);
    if (distribution.labels.length) {
        C.makeHorizontalBar(document.getElementById('ats-distribution'),
            distribution.labels,
            [{
                label: 'Rekaman ATS',
                data: distribution.values,
                backgroundColor: '#8b5cf6',
            }]);
    }
    const kabupaten = @json($byKabupaten);
    if (kabupaten && Object.keys(kabupaten).length) {
        const labels = Object.keys(kabupaten).map((code) => code === '7206' ? 'Morowali' : 'Morowali Utara');
        const values = Object.values(kabupaten);
        C.makeDoughnut(document.getElementById('ats-kabupaten'), labels, values, ['#10b981', '#8b5cf6']);
    }
});
</script>
@endpush