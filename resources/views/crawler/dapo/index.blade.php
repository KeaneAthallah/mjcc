@extends('layouts.app')

@section('title', 'DAPO Kemendikdasmen')

@section('content')
<div class="page-transition space-y-5">

    <x-page-title title="DAPO Kemendikdasmen" subtitle="Data Pokok Pendidikan · Sekolah di Morowali & Morowali Utara">
        <x-slot:actions>
            <x-button href="{{ route('crawler.dashboard') }}" variant="ghost" size="sm">← Overview</x-button>
            <x-badge color="violet">DATA EKSTERNAL</x-badge>
        </x-slot:actions>
    </x-page-title>

    {{-- Source badge --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex items-center gap-3">
            <span class="w-11 h-11 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center text-xl">{{ $meta['icon'] }}</span>
            <div>
                <div class="font-extrabold text-gray-900 text-[14px]">Sumber: {{ $meta['source_label'] }}</div>
                <div class="text-[11px] text-gray-500">Sinkronisasi terakhir: {{ $lastRun?->finished_at?->diffForHumans() ?? $lastRun?->started_at?->diffForHumans() ?? 'belum pernah' }}</div>
            </div>
        </div>
        <div class="sm:ml-auto">
            @if ($lastRun?->status === 'success' && $lastRun?->finished_at)
                <x-badge color="green">Sehat</x-badge>
            @elseif ($lastRun?->status === 'partial')
                <x-badge color="amber">Sebagian</x-badge>
            @elseif ($lastRun?->status === 'failed')
                <x-badge color="red">Gagal</x-badge>
            @else
                <x-badge color="gray">Belum Ada Data</x-badge>
            @endif
        </div>
    </div>

    {{-- KPI: total + per jenjang --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-stat-card label="Total Sekolah" value="{{ number_format($records->total()) }}" icon="🏫" color="violet"/>
        <x-stat-card label="Jenjang" value="{{ number_format(count($jenjangs)) }}" icon="🏷️" color="blue"/>
        <x-stat-card label="Kecamatan" value="{{ number_format(count($kecamatans)) }}" icon="🗺️" color="green"/>
        <x-stat-card label="Per Kabupaten" value="{{ number_format(count($regions)) }}" icon="📍" color="amber"/>
    </div>

    {{-- Jenjang breakdown cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @forelse (array_slice($byJenjang, 0, 8, true) as $jenjang => $count)
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="text-[22px] font-extrabold text-gray-900 leading-none">{{ number_format($count) }}</div>
                <div class="text-[11px] text-gray-600 mt-1 uppercase tracking-wide">{{ $jenjang ?: 'Tidak diketahui' }}</div>
            </div>
        @empty
            <div class="col-span-full"><x-empty-state title="Belum ada data" description="Sinkronisasi DAPO akan mengisi data sekolah."/></div>
        @endforelse
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <x-card title="Sekolah per Kecamatan" icon="📊">
            <div class="h-64">
                @if (! empty($distribution['labels']))
                    <canvas id="dapo-distribution"></canvas>
                @else
                    <x-empty-state title="Belum ada data" description="Data sekolah per kecamatan belum tersedia."/>
                @endif
            </div>
        </x-card>
        <x-card title="Sebaran Jenjang" icon="🏫">
            <div class="h-64">
                @if (count($byJenjang))
                    <canvas id="dapo-jenjang"></canvas>
                @else
                    <x-empty-state title="Belum ada data" description="Data jenjang belum tersedia."/>
                @endif
            </div>
        </x-card>
    </div>

    {{-- Filter bar --}}
    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari sekolah...">
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
        <select name="jenjang" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200 focus:border-violet-500">
            <option value="">Semua Jenjang</option>
            @foreach ($jenjangs as $jenjang)
                <option value="{{ $jenjang }}" @selected(request('jenjang') === $jenjang)>{{ $jenjang }}</option>
            @endforeach
        </select>
    </x-filter-bar>

    {{-- School table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($records->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Nama Sekolah</th>
                            <th class="text-left px-2 py-3 font-bold">Jenjang</th>
                            <th class="text-left px-2 py-3 font-bold">Kecamatan</th>
                            <th class="text-left px-2 py-3 font-bold">Status</th>
                            <th class="text-right px-4 py-3 font-bold">Terakhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($records as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('crawler.dapo.schools.show', $record) }}" class="font-bold text-gray-800 hover:text-violet-600">{{ $record->name ?? $record->external_id }}</a>
                                </td>
                                <td class="px-2 py-3"><x-badge color="indigo">{{ $record->data['jenjang'] ?? '-' }}</x-badge></td>
                                <td class="px-2 py-3 text-gray-600">{{ $record->kecamatan_name ?? '-' }}</td>
                                <td class="px-2 py-3">
                                    <x-badge color="{{ data_get($record->data, 'status', '') === 'aktif' ? 'green' : 'gray' }}">{{ $record->data['status'] ?? '-' }}</x-badge>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-400">{{ $record->last_seen_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$records" />
        @else
            <x-empty-state title="Belum ada sekolah" description="Sinkronisasi DAPO akan mengisi data sekolah."/>
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
        C.makeHorizontalBar(document.getElementById('dapo-distribution'),
            distribution.labels,
            [{ label: 'Sekolah', data: distribution.values, backgroundColor: '#7c3aed' }]);
    }
    const byJenjang = @json($byJenjang);
    if (byJenjang && Object.keys(byJenjang).length) {
        C.makeDoughnut(document.getElementById('dapo-jenjang'),
            Object.keys(byJenjang),
            Object.values(byJenjang),
            ['#7c3aed', '#10b981', '#2563eb', '#f59e0b', '#ef4444', '#6366f1'],
            { cutout: '60%' });
    }
});
</script>
@endpush