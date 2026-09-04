@extends('layouts.app')

@section('title', $record->name ?? 'Detail Harga PIHPS')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="{{ $record->name ?? $record->external_id }}" subtitle="Detail harga komoditas · PIHPS Bank Indonesia">
        <x-slot:actions>
            <x-button href="{{ route('crawler.sp2kp') }}" variant="ghost" size="sm">← Daftar Harga</x-button>
            <x-badge color="violet">DATA EKSTERNAL</x-badge>
        </x-slot:actions>
    </x-page-title>

    @php
        $data = $record->data ?? [];
        $sourceLink = $record->source_url
            ? new \Illuminate\Support\HtmlString('<a class="text-violet-600 font-bold hover:underline" target="_blank" href="'.e($record->source_url).'" rel="noopener">Buka ↗</a>')
            : '-';
    @endphp

    @if ($record->latitude && $record->longitude)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">🗺️ Lokasi</div>
            <div id="market-map" class="h-80"></div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Detail Komoditas</div>
            <x-info-grid :rows="[
                ['Komoditas', $record->name ?? '-'],
                ['Jenis Pasar', data_get($data, 'price_type') ?? '-'],
                ['Harga', isset($data['harga']) ? 'Rp '.number_format((float) $data['harga'], 0, ',', '.') : '-'],
                ['Tanggal', data_get($data, 'tanggal') ?? data_get($data, 'date') ?? '-'],
                ['Satuan', data_get($data, 'satuan') ?? data_get($data, 'unit') ?? '-'],
                ['Skala', data_get($data, 'skala') ?? $record->kabupaten_name ?? '-'],
                ['Provinsi', $record->province_code ? 'Sulawesi Tengah ('.$record->province_code.')' : '-'],
            ]"/>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Data Sumber</div>
            <x-info-grid :rows="[
                ['Sumber', $meta['source_label']],
                ['URL Sumber', $sourceLink],
                ['Pertama Dilihat', $record->first_seen_at?->format('d M Y H:i') ?? '-'],
                ['Terakhir Dilihat', $record->last_seen_at?->format('d M Y H:i') ?? '-'],
                ['Content Hash', substr((string) $record->content_hash, 0, 16).'…'],
            ]" />
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Raw Data (JSON)</div>
        <div class="p-4">
            <pre class="text-[11px] leading-relaxed bg-gray-900 text-emerald-100 rounded-xl p-4 overflow-x-auto max-h-90">{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    @if ($record->latitude && $record->longitude)
        window.Mjcc.maps.createMap('market-map', [{
            name: @json($record->name ?? $record->external_id),
            latitude: {{ (float) $record->latitude }},
            longitude: {{ (float) $record->longitude }},
            sector: 'eksternal',
            category: 'ext-sp2kp',
            details: { Sumber: 'PIHPS BI' },
        }], { cluster: false, resize: true });
    @endif
});
</script>
@endpush