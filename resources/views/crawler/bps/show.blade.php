@extends('layouts.app')

@section('title', $record->data['label'] ?? 'Detail Indikator BPS')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="{{ $record->data['label'] ?? $record->name ?? 'Detail Indikator' }}" subtitle="Indikator statistik BPS · Morowali & Morowali Utara">
        <x-slot:actions>
            <x-button href="{{ route('crawler.bps') }}" variant="ghost" size="sm">← Indikator BPS</x-button>
            <x-badge color="violet">DATA EKSTERNAL</x-badge>
        </x-slot:actions>
    </x-page-title>

    @php $data = $record->data ?? []; @endphp

    {{-- Region comparison cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @php
            $morowali = $pairs->firstWhere('kabupaten_code', '7206');
            $morowaliUtara = $pairs->firstWhere('kabupaten_code', '7212');
        @endphp
        <div class="bg-white rounded-2xl p-5 shadow-sm border-l-4 border-l-emerald-500 border border-gray-100">
            <div class="flex items-center justify-between mb-1">
                <span class="font-extrabold text-gray-900 text-[14px]">Kabupaten Morowali</span>
            </div>
            @if ($morowali)
                @php
                    $morowaliValues = $morowali->data['values'] ?? [];
                    $val = reset($morowaliValues);
                @endphp
                <div class="text-[26px] font-extrabold text-gray-900 leading-none">{{ $val !== null && $val !== false ? number_format((float) $val, 2) : '-' }}</div>
                <div class="text-[11px] text-gray-600 mt-1">Unit: {{ $morowali->data['unit'] ?? '-' }} · Periode: {{ $morowali->data['period'] ?? '-' }}</div>
            @else
                <div class="text-[26px] font-extrabold text-gray-300 leading-none">-</div>
                <div class="text-[11px] text-gray-500 mt-1">Data tidak tersedia</div>
            @endif
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border-l-4 border-l-violet-500 border border-gray-100">
            <div class="flex items-center justify-between mb-1">
                <span class="font-extrabold text-gray-900 text-[14px]">Kabupaten Morowali Utara</span>
            </div>
            @if ($morowaliUtara)
                @php
                    $morowaliUtaraValues = $morowaliUtara->data['values'] ?? [];
                    $val = reset($morowaliUtaraValues);
                @endphp
                <div class="text-[26px] font-extrabold text-gray-900 leading-none">{{ $val !== null && $val !== false ? number_format((float) $val, 2) : '-' }}</div>
                <div class="text-[11px] text-gray-600 mt-1">Unit: {{ $morowaliUtara->data['unit'] ?? '-' }} · Periode: {{ $morowaliUtara->data['period'] ?? '-' }}</div>
            @else
                <div class="text-[26px] font-extrabold text-gray-300 leading-none">-</div>
                <div class="text-[11px] text-gray-500 mt-1">Data tidak tersedia</div>
            @endif
        </div>
    </div>

    {{-- Metadata + Raw --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Detail Indikator</div>
            <x-info-grid :rows="[
                ['Label', $data['label'] ?? '-'],
                ['Unit', $data['unit'] ?? '-'],
                ['Var ID (BPS)', $data['indicator'] ?? '-'],
                ['Periode', $data['period'] ?? '-'],
                ['Rezim BPS', $data['domain_bps'] ?? '-'],
                ['Sumber', $record->source?->slug ?? $meta['source_label']],
            ]"/>
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