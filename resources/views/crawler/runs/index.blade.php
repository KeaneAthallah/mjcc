@extends('layouts.app')

@section('title', 'Riwayat Sinkronisasi')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Riwayat Sinkronisasi" subtitle="Seluruh proses sinkronisasi data eksternal">
        <x-slot:actions>
            <x-button href="{{ route('crawler.dashboard') }}" variant="ghost" size="sm">← Overview</x-button>
        </x-slot:actions>
    </x-page-title>

    <x-filter-bar search="{{ request('source') }}" search-placeholder="Filter...">
        <select name="source" onchange="this.form.submit()"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200 focus:border-violet-500">
            <option value="">Semua Sumber</option>
            @foreach ($sources as $s)
                <option value="{{ $s->slug }}" @selected(request('source') === $s->slug)>{{ $s->name }}</option>
            @endforeach
        </select>
    </x-filter-bar>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($runs->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Sumber</th>
                            <th class="text-left px-2 py-3 font-bold">Mulai</th>
                            <th class="text-left px-2 py-3 font-bold">Selesai</th>
                            <th class="text-right px-2 py-3 font-bold">Durasi</th>
                            <th class="text-right px-2 py-3 font-bold">Ditemukan</th>
                            <th class="text-right px-2 py-3 font-bold">Dibuat</th>
                            <th class="text-right px-2 py-3 font-bold">Diperbarui</th>
                            <th class="text-right px-2 py-3 font-bold">Tidak Berubah</th>
                            <th class="text-right px-2 py-3 font-bold">Gagal</th>
                            <th class="text-center px-4 py-3 font-bold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($runs as $run)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('crawler.runs.show', $run) }}'">
                                <td class="px-4 py-3 font-bold uppercase text-gray-800">
                                    <a href="{{ route('crawler.runs.show', $run) }}" class="hover:text-violet-600">{{ $run->source?->slug ?? '-' }}</a>
                                </td>
                                <td class="px-2 py-3 text-gray-600">{{ $run->started_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td class="px-2 py-3 text-gray-600">{{ $run->finished_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td class="px-2 py-3 text-right font-mono text-gray-600">{{ $run->humanDuration() ?? '-' }}</td>
                                <td class="px-2 py-3 text-right text-gray-600">{{ $run->records_found }}</td>
                                <td class="px-2 py-3 text-right text-emerald-600 font-bold">{{ $run->records_created }}</td>
                                <td class="px-2 py-3 text-right text-blue-600 font-bold">{{ $run->records_updated }}</td>
                                <td class="px-2 py-3 text-right text-gray-500">{{ $run->records_unchanged }}</td>
                                <td class="px-2 py-3 text-right {{ $run->records_failed > 0 ? 'text-red-600 font-bold' : 'text-gray-500' }}">{{ $run->records_failed }}</td>
                                <td class="px-4 py-3 text-center"><x-badge color="{{ match ($run->status) {
                                    'success' => 'green',
                                    'partial' => 'amber',
                                    'failed' => 'red',
                                    default => 'gray',
                                } }}">{{ $run->status }}</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$runs" />
        @else
            <x-empty-state title="Belum ada riwayat" description="Riwayat sinkronisasi akan tampil setelah proses berjalan."/>
        @endif
    </div>
</div>
@endsection