@extends('layouts.app')

@section('title', 'Harga Pangan PIHPS')

@section('content')
<div class="page-transition space-y-5">

    <x-page-title title="Harga Pangan PIHPS" subtitle="Harga harian komoditas pangan · skala provinsi Sulawesi Tengah">
        <x-slot:actions>
            <x-button href="{{ route('crawler.dashboard') }}" variant="ghost" size="sm">← Overview</x-button>
            <x-badge color="violet">DATA EKSTERNAL</x-badge>
        </x-slot:actions>
    </x-page-title>

    @php $tab = request('tab', 'pasar'); @endphp

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

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-stat-card label="Rekaman" value="{{ number_format($records->total()) }}" icon="🏪" color="violet"/>
        <x-stat-card label="Komoditas" value="{{ number_format(count($komoditas)) }}" icon="🛒" color="green"/>
        <x-stat-card label="Skala Harga" value="Provinsi" icon="📍" color="blue"/>
        <x-stat-card label="Cakupan" value="1" icon="🗺️" color="amber"/>
    </div>

    {{-- Commodity counts --}}
    <x-card title="Komoditas Paling Banyak Tercatat" icon="🛒">
        @if (count($commodityCounts))
            <div class="flex flex-wrap gap-2">
                @foreach (array_slice($commodityCounts, 0, 12, true) as $com => $cnt)
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-violet-50 border border-violet-200 text-[12px]">
                        <span class="font-bold text-gray-800">{{ $com ?: 'Tidak dikenal' }}</span>
                        <span class="text-violet-700 font-extrabold">{{ number_format($cnt) }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <x-empty-state title="Belum ada komoditas" description="Sinkronisasi PIHPS BI akan mengisi data harga komoditas."/>
        @endif
    </x-card>

    {{-- Filter bar --}}
    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari komoditas...">
        <select name="komoditas" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200 focus:border-violet-500">
            <option value="">Semua Komoditas</option>
            @foreach ($komoditas as $kom)
                <option value="{{ $kom }}" @selected(request('komoditas') === $kom)>{{ $kom }}</option>
            @endforeach
        </select>
    </x-filter-bar>

    {{-- Price table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($records->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Komoditas</th>
                            <th class="text-left px-2 py-3 font-bold">Jenis</th>
                            <th class="text-right px-2 py-3 font-bold">Harga</th>
                            <th class="text-left px-2 py-3 font-bold">Wilayah</th>
                            <th class="text-left px-2 py-3 font-bold">Tanggal</th>
                            <th class="text-right px-4 py-3 font-bold">Terakhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($records as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('crawler.sp2kp.markets.show', $record) }}" class="font-bold text-gray-800 hover:text-violet-600">{{ $record->name ?? $record->external_id }}</a>
                                </td>
                                <td class="px-2 py-3"><x-badge color="indigo">{{ $record->data['price_type'] ?? '-' }}</x-badge></td>
                                <td class="px-2 py-3 text-right font-bold text-gray-800">
                                    @if (isset($record->data['harga']))
                                        Rp {{ number_format((float) $record->data['harga'], 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-gray-600">{{ $record->data['skala'] ?? ($record->kabupaten_name ?? '-') }}</td>
                                <td class="px-2 py-3 text-gray-600">{{ $record->data['tanggal'] ?? $record->data['date'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-gray-400">{{ $record->last_seen_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$records" />
        @else
            <x-empty-state title="Belum ada data harga" description="Sinkronisasi PIHPS BI akan mengisi data harga komoditas."/>
        @endif
    </div>
</div>
@endsection