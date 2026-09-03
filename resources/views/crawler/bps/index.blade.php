@extends('layouts.app')

@section('title', 'BPS - Statistik Wilayah')

@section('content')
<div class="page-transition space-y-5">

    <x-page-title title="Badan Pusat Statistik" subtitle="Indikator statistik wilayah Morowali & Morowali Utara (Web API BPS)">
        <x-slot:actions>
            <x-button href="{{ route('crawler.dashboard') }}" variant="ghost" size="sm">← Overview</x-button>
            <x-badge color="violet">DATA EKSTERNAL</x-badge>
        </x-slot:actions>
    </x-page-title>

    @php $tab = request('tab', 'indikator'); @endphp

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

    {{-- Category cards --}}
    <x-card title="Kategori Indikator" icon="🏷️">
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
            @forelse ($categories as $cat)
                <a href="{{ route('crawler.bps', ['category' => $cat['name']]) }}"
                   class="bg-gradient-to-br from-violet-50 to-white rounded-2xl p-4 border border-violet-100 hover:shadow-md hover:-translate-y-0.5 transition block">
                    <div class="font-extrabold text-gray-900 text-[14px]">{{ $cat['name'] }}</div>
                    <div class="text-[11px] text-gray-500 mt-1">{{ $cat['count'] }} indikator</div>
                    <div class="text-[11px] text-violet-600 font-bold mt-2">
                        Tahun terbaru: {{ $cat['latest_year'] ?? '-' }}
                    </div>
                </a>
            @empty
                <div class="col-span-full"><x-empty-state title="Belum ada indikator" description="Sinkronisasi BPS akan mengisi indikator statistik wilayah."/></div>
            @endforelse
        </div>
    </x-card>

    {{-- Comparison table --}}
    <x-card title="Perbandingan Indikator · Morowali vs Morowali Utara" icon="📊" :padding="false">
        @if (count($indicators))
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Indikator</th>
                            <th class="text-left px-2 py-3 font-bold">Kategori</th>
                            <th class="text-right px-2 py-3 font-bold">Morowali</th>
                            <th class="text-right px-2 py-3 font-bold">Morowali Utara</th>
                            <th class="text-center px-2 py-3 font-bold">Unit</th>
                            <th class="text-right px-4 py-3 font-bold">Periode</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($indicators as $ind)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('crawler.bps.show', $ind['id']) }}" class="font-bold text-gray-800 hover:text-violet-600">{{ $ind['label'] }}</a>
                                </td>
                                <td class="px-2 py-3"><x-badge color="indigo">{{ $ind['category'] }}</x-badge></td>
                                <td class="px-2 py-3 text-right font-semibold text-gray-800">{{ number_format((float) ($ind['morowali_value'] ?? 0), 2) }}</td>
                                <td class="px-2 py-3 text-right font-semibold text-gray-800">{{ number_format((float) ($ind['morowali_utara_value'] ?? 0), 2) }}</td>
                                <td class="px-2 py-3 text-center text-gray-500">{{ $ind['unit'] }}</td>
                                <td class="px-4 py-3 text-right text-gray-500">{{ $ind['period'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state title="Belum ada indikator" description="Sinkronisasi BPS akan mengisi indikator statistik wilayah."/>
        @endif
    </x-card>
</div>
@endsection