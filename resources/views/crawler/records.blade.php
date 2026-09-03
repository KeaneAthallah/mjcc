@extends('layouts.app')

@section('title', 'Rekaman Data Eksternal')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Rekaman Data Eksternal" subtitle="Data publik tersinkronisasi untuk Morowali & Morowali Utara">
        <x-slot:actions>
            <x-button href="{{ route('crawler.dashboard') }}" variant="ghost" size="sm">← Dashboard</x-button>
        </x-slot:actions>
    </x-page-title>

    <x-filter-bar search="{{ request('q') }}" search-placeholder="Cari nama...">
        <select name="source" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500 min-w-[150px]">
            <option value="">Semua Sumber</option>
            @foreach ($sources as $s)
                <option value="{{ $s->id }}" @selected((string) request('source') === (string) $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
        <select name="regional" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Wilayah</option>
            @foreach ($regions as $code => $name)
                <option value="{{ $code }}" @selected(request('regional') === $code)>{{ $name }}</option>
            @endforeach
        </select>
        <select name="type" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Tipe</option>
            @foreach ($types as $type)
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
            @endforeach
        </select>
        <select name="has_coords" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Koordinat</option>
            <option value="1" @selected(request('has_coords') === '1')>Ber-koordinat</option>
        </select>
    </x-filter-bar>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($records->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Nama</th>
                            <th class="text-left px-2 py-3 font-bold">Sumber</th>
                            <th class="text-left px-2 py-3 font-bold">Wilayah</th>
                            <th class="text-left px-2 py-3 font-bold">Tipe</th>
                            <th class="text-center px-2 py-3 font-bold">Peta</th>
                            <th class="text-left px-4 py-3 font-bold">Terakhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($records as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('crawler.records.show', $record) }}" class="font-bold text-gray-800 hover:text-emerald-600">{{ $record->name ?? $record->external_id }}</a>
                                </td>
                                <td class="px-2 py-3">
                                    <x-badge color="blue">{{ strtoupper($record->source?->slug ?? '-') }}</x-badge>
                                </td>
                                <td class="px-2 py-3 text-gray-600">{{ $record->kabupaten_name ?? $record->kabupaten_code ?? '-' }}</td>
                                <td class="px-2 py-3"><x-badge color="gray">{{ $record->record_type }}</x-badge></td>
                                <td class="px-2 py-3 text-center">
                                    @if ($record->latitude && $record->longitude)
                                        <span title="{{ $record->latitude }}, {{ $record->longitude }}">📍</span>
                                    @else
                                        <span class="text-gray-300">–</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-400">{{ $record->last_seen_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$records" />
        @else
            <x-empty-state title="Belum ada rekaman" description="Jalankan `php artisan crawler:sync` atau tunggu jadwal berjalan untuk mengumpulkan data."/>
        @endif
    </div>
</div>
@endsection