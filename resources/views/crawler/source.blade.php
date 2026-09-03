@extends('layouts.app')

@section('title', $source->name)

@section('content')
<div class="page-transition space-y-5">

    <x-page-title title="{{ $source->name }}" subtitle="Detail sumber data eksternal · {{ $source->base_url }}">
        <x-slot:actions>
            <x-button href="{{ route('crawler.dashboard') }}" variant="ghost" size="sm">← Dashboard</x-button>
            @can('run', $source)
                <form method="POST" action="{{ route('crawler.sources.run', $source) }}" class="inline" onsubmit="return confirm('Jalankan sinkronisasi {{ $source->name }} sekarang?')">
                    @csrf
                    <x-button type="submit" variant="primary" size="sm">▶ Jalankan Sinkronisasi</x-button>
                </form>
            @endcan
        </x-slot:actions>
    </x-page-title>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Rekaman terbaru --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 flex items-center justify-between border-b border-gray-100">
                <h3 class="font-extrabold text-gray-900 text-[14px]">Rekaman Terbaru</h3>
                <a href="{{ route('crawler.records.index', ['source' => $source->id]) }}" class="text-[11px] text-emerald-600 font-bold hover:underline">Lihat semua →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Nama</th>
                            <th class="text-left px-2 py-3 font-bold">Wilayah</th>
                            <th class="text-left px-2 py-3 font-bold">Tipe</th>
                            <th class="text-left px-4 py-3 font-bold">Terakhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($records as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('crawler.records.show', $record) }}" class="font-bold text-gray-800 hover:text-emerald-600">{{ $record->name ?? $record->external_id }}</a>
                                </td>
                                <td class="px-2 py-3 text-gray-600">{{ $record->kabupaten_name ?? $record->kabupaten_code ?? '-' }}</td>
                                <td class="px-2 py-3"><x-badge color="green">{{ $record->record_type }}</x-badge></td>
                                <td class="px-4 py-3 text-gray-400">{{ $record->last_seen_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Belum ada rekaman sumber ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Riwayat run & errors --}}
        <div class="space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Riwayat Sinkronisasi</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-[12px]">
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($runs as $run)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2.5">
                                        <x-badge color="{{ match ($run->status) {
                                            'success' => 'green',
                                            'partial' => 'amber',
                                            'failed' => 'red',
                                            default => 'gray',
                                        } }}">{{ $run->status }}</x-badge>
                                    </td>
                                    <td class="px-2 py-2.5 text-gray-600">{{ $run->started_at?->diffForHumans() }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-500">
                                        +{{ $run->records_created }} · ≈{{ $run->records_unchanged }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="px-4 py-6 text-center text-gray-400">Belum ada run.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">⚠️ Error Terakhir</div>
                <div class="divide-y divide-gray-100">
                    @forelse ($errors as $error)
                        <div class="px-4 py-2.5 text-[12px]">
                            <div class="text-gray-700">{{ $error->message }}</div>
                            <div class="text-[11px] text-gray-400">{{ $error->occurred_at?->diffForHumans() }} · HTTP {{ $error->http_status ?? '-' }}</div>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-gray-400 text-[12px]">Tidak ada error. ✅</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection