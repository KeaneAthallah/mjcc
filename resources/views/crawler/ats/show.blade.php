@extends('layouts.app')

@section('title', 'Detail ATS' . ($record->name ? ' · ' . $record->name : ''))

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="{{ $record->name ?? $record->external_id }}" subtitle="Detail data ATS">
        <x-slot:actions>
            <x-button href="{{ route('crawler.ats') }}" variant="ghost" size="sm">← Daftar ATS</x-button>
            <x-badge color="violet">DATA EKSTERNAL</x-badge>
        </x-slot:actions>
    </x-page-title>

    @php $data = $record->data ?? []; @endphp

    @if ($record->latitude && $record->longitude)
        <x-card title="Lokasi" icon="🗺️" :padding="false">
            <div id="detail-map" class="h-80"></div>
        </x-card>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Data Umum --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Data Umum</div>
            <dl class="text-[12px] divide-y divide-gray-100">
                <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">Nama</dt><dd class="font-bold text-gray-800 text-right">{{ $record->name ?? '-' }}</dd></div>
                <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">Tipe Rekaman</dt><dd class="text-gray-800">{{ $record->record_type }}</dd></div>
                <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">External ID</dt><dd class="font-mono text-gray-800 break-all text-right">{{ $record->external_id }}</dd></div>
                @foreach (['category' => 'Kategori'] as $k => $label)
                    @if (isset($data[$k]))
                        <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">{{ $label }}</dt><dd class="text-gray-800">{{ $data[$k] }}</dd></div>
                    @endif
                @endforeach
            </dl>
        </div>

        {{-- Wilayah --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Wilayah</div>
            <dl class="text-[12px] divide-y divide-gray-100">
                <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">Provinsi</dt><dd class="text-gray-800">Sulawesi Tengah</dd></div>
                <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">Kabupaten</dt><dd class="text-gray-800">{{ $record->kabupaten_name ?? $record->kabupaten_code ?? '-' }}</dd></div>
                <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">Kode Kabupaten</dt><dd class="font-mono text-gray-800">{{ $record->kabupaten_code ?? '-' }}</dd></div>
                <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">Kecamatan</dt><dd class="text-gray-800">{{ $record->kecamatan_name ?? '-' }}</dd></div>
                <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">Desa/Kelurahan</dt><dd class="text-gray-800">{{ $record->desa_name ?? '-' }}</dd></div>
            </dl>
        </div>

        {{-- Metadata --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Metadata</div>
            <dl class="text-[12px] divide-y divide-gray-100">
                <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">Pertama Dilihat</dt><dd class="text-gray-800">{{ $record->first_seen_at?->format('d M Y H:i') ?? '-' }}</dd></div>
                <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">Terakhir Dilihat</dt><dd class="text-gray-800">{{ $record->last_seen_at?->format('d M Y H:i') ?? '-' }}</dd></div>
                <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">Content Hash</dt><dd class="font-mono text-gray-400 text-right">{{ substr((string) $record->content_hash, 0, 16) }}…</dd></div>
                @if ($record->source_updated_at)
                    <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">Diperbarui Sumber</dt><dd class="text-gray-800">{{ $record->source_updated_at->format('d M Y H:i') }}</dd></div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Data Sumber + Raw --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Data Sumber</div>
            <dl class="text-[12px] divide-y divide-gray-100">
                <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">Sumber</dt><dd class="text-gray-800">{{ $meta['source_label'] }}</dd></div>
                <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">Koordinat</dt><dd>
                    @if ($record->latitude && $record->longitude)
                        <span class="font-mono text-gray-800">{{ $record->latitude }}, {{ $record->longitude }}</span>
                    @else
                        <span class="text-gray-400">Tidak tersedia</span>
                    @endif
                </dd></div>
                @if ($record->source_url)
                    <div class="px-5 py-3 flex justify-between gap-4"><dt class="text-gray-500">URL Sumber</dt><dd><a href="{{ $record->source_url }}" target="_blank" rel="noopener" class="text-violet-600 font-bold hover:underline break-all text-right">Buka ↗</a></dd></div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Raw Data (JSON)</div>
            <div class="p-4">
                <pre class="text-[11px] leading-relaxed bg-gray-900 text-emerald-100 rounded-xl p-4 overflow-x-auto max-h-90">{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    @if ($record->latitude && $record->longitude)
        window.Mjcc.maps.createMap('detail-map', [{
            name: @json($record->name ?? $record->external_id),
            latitude: {{ (float) $record->latitude }},
            longitude: {{ (float) $record->longitude }},
            sector: 'eksternal',
            category: 'ext-ats',
            details: { Sumber: 'ATS' },
        }], { cluster: false, resize: true });
    @endif
});
</script>
@endpush