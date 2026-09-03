@extends('layouts.app')

@section('title', $record->name ?? 'Detail Rekaman')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="{{ $record->name ?? $record->external_id }}" subtitle="Detail rekaman data eksternal">
        <x-slot:actions>
            <x-button href="{{ route('crawler.records.index') }}" variant="ghost" size="sm">← Daftar</x-button>
        </x-slot:actions>
    </x-page-title>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden lg:col-span-1">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Identitas</div>
            <dl class="text-[12px] divide-y divide-gray-100">
                <div class="px-5 py-3 flex justify-between gap-4">
                    <dt class="text-gray-500">Sumber</dt>
                    <dd class="font-bold text-gray-800 uppercase">{{ $record->source?->slug ?? '-' }}</dd>
                </div>
                <div class="px-5 py-3 flex justify-between gap-4">
                    <dt class="text-gray-500">Tipe</dt>
                    <dd class="font-bold text-gray-800">{{ $record->record_type }}</dd>
                </div>
                <div class="px-5 py-3 flex justify-between gap-4">
                    <dt class="text-gray-500">External ID</dt>
                    <dd class="font-mono text-gray-800 break-all text-right">{{ $record->external_id }}</dd>
                </div>
                <div class="px-5 py-3 flex justify-between gap-4">
                    <dt class="text-gray-500">Kabupaten</dt>
                    <dd class="text-gray-800">{{ $record->kabupaten_name ?? $record->kabupaten_code ?? '-' }}</dd>
                </div>
                <div class="px-5 py-3 flex justify-between gap-4">
                    <dt class="text-gray-500">Kecamatan</dt>
                    <dd class="text-gray-800">{{ $record->kecamatan_name ?? '-' }}</dd>
                </div>
                <div class="px-5 py-3 flex justify-between gap-4">
                    <dt class="text-gray-500">Desa</dt>
                    <dd class="text-gray-800">{{ $record->desa_name ?? '-' }}</dd>
                </div>
                <div class="px-5 py-3 flex justify-between gap-4">
                    <dt class="text-gray-500">Koordinat</dt>
                    <dd>
                        @if ($record->latitude && $record->longitude)
                            <span class="font-mono text-gray-800">{{ $record->latitude }}, {{ $record->longitude }}</span>
                        @else
                            <span class="text-gray-400">Tidak tersedia</span>
                        @endif
                    </dd>
                </div>
                <div class="px-5 py-3 flex justify-between gap-4">
                    <dt class="text-gray-500">Terakhir terlihat</dt>
                    <dd class="text-gray-800">{{ $record->last_seen_at?->format('d M Y H:i') ?? '-' }}</dd>
                </div>
                @if ($record->source_url)
                    <div class="px-5 py-3 flex justify-between gap-4">
                        <dt class="text-gray-500">Sumber URL</dt>
                        <dd>
                            <a href="{{ $record->source_url }}" target="_blank" rel="noopener" class="text-emerald-600 font-bold hover:underline break-all text-right">Buka ↗</a>
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <span class="font-extrabold text-gray-900 text-[14px]">Data Mentah (JSON)</span>
                <span class="text-[11px] text-gray-400">content_hash: {{ substr((string) $record->content_hash, 0, 12) }}…</span>
            </div>
            <div class="p-4">
                <pre class="text-[11px] leading-relaxed bg-gray-900 text-emerald-100 rounded-xl p-4 overflow-x-auto max-h-[520px]">{{ json_encode($record->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    </div>
</div>
@endsection