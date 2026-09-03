@extends('layouts.app')

@section('title', 'Data Eksternal')

@section('content')
<div class="page-transition space-y-5">

    <x-page-title title="Data Eksternal Pemerintah" subtitle="Data tersinkronisasi dari ATS, DAPO, PIHPS BI & BPS untuk Morowali & Morowali Utara">
        <x-slot:actions>
            <x-button href="{{ route('crawler.records.index') }}" variant="ghost" size="sm">🗂️ Semua Rekaman</x-button>
            <div class="text-[11px] text-gray-400 pt-2">
                Terakhir jalan: {{ $summary['last_run_at']?->diffForHumans() ?? 'belum pernah' }}
            </div>
        </x-slot:actions>
    </x-page-title>

    {{-- Statistik ringkas --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-stat-card label="Sumber Data" value="{{ number_format($summary['sources']) }}" icon="📡" color="blue"/>
        <x-stat-card label="Rekaman Tersimpan" value="{{ number_format($summary['records']) }}" icon="🗃️" color="green"/>
        <x-stat-card label="Ber-Koordinat" value="{{ number_format($summary['with_coordinates']) }}" icon="📍" color="amber"/>
        <x-stat-card label="Error Tercatat" value="{{ number_format($summary['errors']) }}" icon="⚠️" color="{{ $summary['errors'] > 0 ? 'red' : 'green' }}"/>
    </div>

    {{-- Kartu per sumber --}}
    <div>
        <h3 class="font-extrabold text-gray-900 text-[14px] mb-3">Sumber Data</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
            @forelse ($sources as $row)
                @php
                    $slug = $row['model']->slug;
                    $cfg = config("crawler.sources.{$slug}", []);
                    $meta = [
                        'icon' => $cfg['icon'] ?? '📡',
                        'source_label' => $cfg['source_label'] ?? $row['model']->name,
                        'description' => $cfg['description'] ?? $row['model']->base_url,
                    ];
                    $pageRoute = match ($slug) {
                        'ats' => 'crawler.ats',
                        'dapo' => 'crawler.dapo',
                        'sp2kp' => 'crawler.sp2kp',
                        'bps' => 'crawler.bps',
                        default => null,
                    };
                    $cardHref = $pageRoute ? route($pageRoute) : route('crawler.sources.show', $row['model']);
                    $lastRun = $row['last_run'];
                    $health = $lastRun?->status === 'success' ? 'green'
                        : ($lastRun?->status === 'partial' ? 'amber'
                        : ($lastRun?->status === 'failed' ? 'red' : 'gray'));
                @endphp
                <a href="{{ $cardHref }}"
                   class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md hover:-translate-y-0.5 transition block">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="w-11 h-11 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center text-xl">{{ $meta['icon'] }}</span>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-extrabold text-gray-900 text-[14px] uppercase tracking-wide">{{ $meta['source_label'] }}</span>
                            </div>
                            @if ($health === 'gray' || ! $row['model']->is_active || ! $row['enabled_config'])
                                <x-badge color="gray">Belum Aktif</x-badge>
                            @else
                                <x-badge color="{{ $health }}">{{ $lastRun->status }}</x-badge>
                            @endif
                        </div>
                    </div>
                    <div class="text-[11px] text-gray-400 mb-3 break-all line-clamp-2">{{ $meta['description'] }}</div>
                    <div class="flex items-end justify-between border-t border-gray-50 pt-3">
                        <div>
                            <div class="text-[22px] font-extrabold text-gray-900 leading-none">{{ number_format($row['records']) }}</div>
                            <div class="text-[11px] text-gray-500 mt-1">rekaman</div>
                        </div>
                        <div class="text-right text-[11px] text-gray-500">
                            @if ($lastRun)
                                <div>{{ $lastRun->finished_at?->diffForHumans() }}</div>
                                <div class="text-[10px] text-gray-400">Buka →</div>
                            @else
                                <div class="font-semibold text-gray-300">Belum ada</div>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-full">
                    <x-empty-state title="Belum ada sumber" description="Jalankan perintah `php artisan crawler:sync` untuk mengisi data."/>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Error terbaru --}}
    <div class="bg-white rounded-2xl pt-1 shadow-sm border border-gray-100">
        <div class="px-5 pt-4 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-900 text-[14px]">⚠️ Masalah Terbaru</h3>
            <a href="{{ route('crawler.dashboard') }}" class="text-[11px] text-emerald-600 font-bold hover:underline">Lihat lebih lanjut</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-[12px]">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-5 py-3 font-bold">Sumber</th>
                        <th class="text-left px-2 py-3 font-bold">Pesan</th>
                        <th class="text-left px-4 py-3 font-bold">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recentErrors as $error)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-bold text-gray-700 uppercase text-[11px]">{{ $error->source?->slug ?? '-' }}</td>
                            <td class="px-2 py-3 text-gray-600 truncate max-w-[400px]">{{ $error->message }}</td>
                            <td class="px-4 py-3 text-gray-400">{{ $error->occurred_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-6 text-center text-gray-400">Tidak ada masalah tercatat. ✅</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection