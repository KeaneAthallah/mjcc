@extends('layouts.app')

@section('title', 'Crawling Data Fasyankes')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Crawling Data Fasyankes" subtitle="Sinkronisasi data fasilitas kesehatan dari Kementerian Kesehatan RI">
        <x-slot:actions>
            @if ($source)
                @can('run', $source)
                    <form method="POST" action="{{ route('crawler.kesehatan.run') }}" class="inline"
                          data-confirm data-confirm-title="Jalankan Sinkronisasi"
                          data-confirm-message="Jalankan sinkronisasi data fasyankes dari Kemenkes?">
                        @csrf
                        <x-button type="submit" variant="primary" size="sm">🔄 Jalankan Sinkronisasi</x-button>
                    </form>
                @endcan
            @endif
            <x-button href="{{ route('crawler.dashboard') }}" variant="ghost" size="sm">← Overview</x-button>
        </x-slot:actions>
    </x-page-title>

    {{-- Source Info --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
            <span class="text-2xl">{{ $meta['icon'] ?? '🏥' }}</span>
            <div>
                <div class="font-extrabold text-gray-900 text-[14px]">{{ $meta['name'] ?? 'Fasilitas Kesehatan' }}</div>
                <div class="text-[11px] text-gray-500">{{ $meta['description'] ?? '' }}</div>
            </div>
            @if ($source?->is_active)
                <x-badge color="green">Aktif</x-badge>
            @else
                <x-badge color="red">Nonaktif</x-badge>
            @endif
        </div>
        <div class="p-4">
            <x-info-grid :rows="[
                ['Sumber', $meta['source_label'] ?? 'Kemenkes Fasyankes'],
                ['URL Sumber', $meta['base_url'] ?? '-'],
                ['Terakhir Sinkron', $lastRun?->started_at?->format('d M Y H:i') ?? 'Belum pernah'],
                ['Status Terakhir', $lastRun?->status ? ucwords($lastRun->status) : '-'],
            ]"/>
        </div>
    </div>

    {{-- Facility Statistics --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-stat-card label="Total Fasilitas" value="{{ $facilityStats['total'] }}" icon="🏥" color="emerald"/>
        <x-stat-card label="Puskesmas" value="{{ $facilityStats['puskesmas'] }}" icon="🏨" color="blue"/>
        <x-stat-card label="Rumah Sakit" value="{{ $facilityStats['rs'] }}" icon="🏥" color="red"/>
        <x-stat-card label="Posyandu" value="{{ $facilityStats['posyandu'] }}" icon="🏠" color="amber"/>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        <x-stat-card label="Pustu" value="{{ $facilityStats['pustu'] }}" icon="🏢" color="indigo"/>
        <x-stat-card label="Data Crawled" value="{{ $facilityStats['crawled'] }}" icon="🔄" color="violet"/>
        @if ($lastRun)
            <x-stat-card label="Durasi Terakhir" value="{{ $lastRun->humanDuration() ?? '-' }}" icon="⏱️" color="gray"/>
        @endif
    </div>

    {{-- Crawl History --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Riwayat Sinkronisasi</div>
        @if ($runs->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Mulai</th>
                            <th class="text-left px-2 py-3 font-bold">Selesai</th>
                            <th class="text-right px-2 py-3 font-bold">Durasi</th>
                            <th class="text-right px-2 py-3 font-bold">Ditemukan</th>
                            <th class="text-right px-2 py-3 font-bold">Dibuat</th>
                            <th class="text-right px-2 py-3 font-bold">Diperbarui</th>
                            <th class="text-right px-2 py-3 font-bold">Gagal</th>
                            <th class="text-center px-4 py-3 font-bold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($runs as $run)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('crawler.runs.show', $run) }}'">
                                <td class="px-4 py-3 text-gray-600">{{ $run->started_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td class="px-2 py-3 text-gray-600">{{ $run->finished_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td class="px-2 py-3 text-right font-mono text-gray-600">{{ $run->humanDuration() ?? '-' }}</td>
                                <td class="px-2 py-3 text-right text-gray-600">{{ $run->records_found }}</td>
                                <td class="px-2 py-3 text-right text-emerald-600 font-bold">{{ $run->records_created }}</td>
                                <td class="px-2 py-3 text-right text-blue-600 font-bold">{{ $run->records_updated }}</td>
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
            <x-empty-state title="Belum ada riwayat" description="Jalankan sinkronisasi untuk melihat riwayat proses."/>
        @endif
    </div>

    {{-- Recent Imported Facilities --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Data Terakhir Diimpor</div>
        @if ($recentImports->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Nama Fasilitas</th>
                            <th class="text-center px-2 py-3 font-bold">Jenis</th>
                            <th class="text-left px-2 py-3 font-bold">Kecamatan</th>
                            <th class="text-center px-2 py-3 font-bold">Status</th>
                            <th class="text-left px-4 py-3 font-bold">Terakhir Crawled</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($recentImports as $facility)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('crawler.kesehatan.show', $facility) }}" class="font-bold text-gray-800 hover:text-emerald-600">{{ $facility->name }}</a>
                                </td>
                                <td class="text-center px-2 py-3">
                                    <x-badge color="{{ match($facility->facility_type) { 'Puskesmas' => 'green', 'Rumah Sakit' => 'red', 'Pustu' => 'indigo', default => 'amber' } }}">{{ $facility->facility_type }}</x-badge>
                                </td>
                                <td class="px-2 py-3 text-gray-600">{{ $facility->kecamatan?->name ?? '-' }}</td>
                                <td class="text-center px-2 py-3">
                                    <x-badge color="{{ $facility->status === 'aktif' ? 'green' : 'red' }}">{{ $facility->status }}</x-badge>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $facility->last_crawled_at?->format('d M Y H:i') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state title="Belum ada data diimpor" description="Data fasilitas akan muncul setelah sinkronisasi pertama."/>
        @endif
    </div>

    {{-- Errors --}}
    @if ($recentErrors->count())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Error Terakhir</div>
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Waktu</th>
                            <th class="text-left px-2 py-3 font-bold">Pesan</th>
                            <th class="text-center px-2 py-3 font-bold">HTTP</th>
                            <th class="text-left px-4 py-3 font-bold">URL</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($recentErrors as $error)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-600">{{ $error->occurred_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td class="px-2 py-3 text-red-600">{{ $error->message }}</td>
                                <td class="text-center px-2 py-3"><x-badge color="{{ $error->http_status && $error->http_status >= 500 ? 'red' : 'amber' }}">{{ $error->http_status ?? '-' }}</x-badge></td>
                                <td class="px-4 py-3 text-gray-500 truncate max-w-[300px]">{{ $error->url ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
