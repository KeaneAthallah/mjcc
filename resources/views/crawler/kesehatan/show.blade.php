@extends('layouts.app')

@section('title', 'Detail Fasilitas · ' . $facility->name)

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="{{ $facility->name }}" subtitle="Detail fasilitas kesehatan dari crawling data">
        <x-slot:actions>
            <x-button href="{{ route('crawler.kesehatan') }}" variant="ghost" size="sm">← Kembali</x-button>
            @if ($facility->kecamatan)
                <x-button href="{{ route('health.facilities.show', $facility) }}" variant="ghost" size="sm">Lihat di Faskes →</x-button>
            @endif
        </x-slot:actions>
    </x-page-title>

    {{-- Status Indicator --}}
    @if ($facility->isCrawled())
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl px-5 py-3 flex items-center gap-3">
            <span class="text-emerald-600 text-lg">🔄</span>
            <div>
                <span class="text-[13px] font-bold text-emerald-800">Data ini berasal dari crawling</span>
                <span class="text-[11px] text-emerald-600 ml-2">Sumber: {{ $facility->source_name }} · Terakhir: {{ $facility->last_crawled_at?->format('d M Y H:i') ?? '-' }}</span>
            </div>
        </div>
    @endif

    {{-- Statistics --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-stat-card label="Dokter" value="{{ $facility->doctors }}" icon="👨‍⚕️" color="blue"/>
        <x-stat-card label="Perawat" value="{{ $facility->nurses }}" icon="👩‍⚕️" color="emerald"/>
        <x-stat-card label="Bidan" value="{{ $facility->midwives }}" icon="👩‍⚕️" color="violet"/>
        <x-stat-card label="Tempat Tidur" value="{{ $facility->beds }}" icon="🛏️" color="amber"/>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Informasi Umum --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Informasi Umum</div>
            <x-info-grid :rows="[
                ['Nama', $facility->name],
                ['Jenis', $facility->facility_type],
                ['Kecamatan', $facility->kecamatan?->name ?? '-'],
                ['Alamat', $facility->address ?? '-'],
                ['Telepon', $facility->phone ?? '-'],
                ['Status', $facility->status],
                ['Kondisi', $facility->condition],
            ]"/>
        </div>

        {{-- Koordinat & Peta --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Lokasi</div>
            @if ($facility->latitude && $facility->longitude)
                <div id="facility-map" class="h-[250px]"></div>
                <div class="px-5 py-3">
                    <x-info-grid :rows="[
                        ['Lintang', $facility->latitude],
                        ['Bujur', $facility->longitude],
                    ]"/>
                </div>
            @else
                <x-empty-state title="Tidak ada koordinat" description="Fasilitas ini belum memiliki data lokasi."/>
            @endif
        </div>
    </div>

    {{-- Keterangan --}}
    @if ($facility->description)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Keterangan</div>
            <div class="p-5 text-[13px] text-gray-700 leading-relaxed">{{ $facility->description }}</div>
        </div>
    @endif

    {{-- Source Attribution --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Sumber Data</div>
        <x-info-grid :rows="[
            ['Nama Sumber', $facility->source_name ?? '-'],
            ['ID Sumber', $facility->source_id ?? '-'],
            ['URL Sumber', $facility->source_url ? '<a href="' . e($facility->source_url) . '" target="_blank" class="text-blue-600 hover:underline">' . e($facility->source_url) . '</a>' : '-'],
            ['Update Sumber', $facility->source_updated_at?->format('d M Y H:i') ?? '-'],
            ['Terakhir Crawled', $facility->last_crawled_at?->format('d M Y H:i') ?? '-'],
            ['Dibuat', $facility->created_at?->format('d M Y H:i') ?? '-'],
            ['Diperbarui', $facility->updated_at?->format('d M Y H:i') ?? '-'],
        ]"/>
    </div>

    {{-- Crawl Record Detail --}}
    @if ($crawlRecord)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Data Crawling</div>
            <x-info-grid :rows="[
                ['Crawl Record ID', $crawlRecord->id],
                ['External ID', $crawlRecord->external_id],
                ['Record Type', $crawlRecord->record_type],
                ['Content Hash', $crawlRecord->content_hash ? substr($crawlRecord->content_hash, 0, 16).'...' : '-'],
                ['Pertama Ditemukan', $crawlRecord->first_seen_at?->format('d M Y H:i') ?? '-'],
                ['Terakhir Dilihat', $crawlRecord->last_seen_at?->format('d M Y H:i') ?? '-'],
            ]"/>
        </div>
    @endif

    {{-- Raw Data (Collapsible) --}}
    @if ($crawlRecord?->data)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ open: false }">
            <button @click="open = !open" class="w-full px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px] flex items-center justify-between">
                <span>Data Mentah (Debug)</span>
                <svg class="w-4 h-4 text-gray-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak class="p-4">
                <pre class="text-[11px] leading-relaxed bg-gray-900 text-emerald-100 rounded-xl p-4 overflow-x-auto max-h-90">{{ json_encode($crawlRecord->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    @endif
</div>

@if ($facility->latitude && $facility->longitude)
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.Mjcc !== 'undefined' && window.Mjcc.maps) {
            var map = window.Mjcc.maps.createMap('facility-map', {
                center: [{{ $facility->latitude }}, {{ $facility->longitude }}],
                zoom: 14,
            });
            L.marker([{{ $facility->latitude }}, {{ $facility->longitude }}])
                .addTo(map)
                .bindPopup('<b>{{ addslashes($facility->name) }}</b><br>{{ $facility->facility_type }}');
        }
    });
</script>
@endpush
@endif
@endsection
