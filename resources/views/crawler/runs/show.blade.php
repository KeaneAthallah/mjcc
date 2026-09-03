@extends('layouts.app')

@section('title', 'Detail Sinkronisasi · ' . ($run->source?->name ?? $run->id))

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Detail Sinkronisasi" subtitle="{{ $meta['source_label'] ?? $run->source?->name ?? $run->id }}">
        <x-slot:actions>
            <x-button href="{{ route('crawler.runs') }}" variant="ghost" size="sm">← Riwayat Sinkronisasi</x-button>
            <x-badge color="{{ match ($run->status) {
                'success' => 'green',
                'partial' => 'amber',
                'failed' => 'red',
                default => 'gray',
            } }}">{{ $run->status }}</x-badge>
        </x-slot:actions>
    </x-page-title>

    @php
        $log = $run->log ?? [];
        $errors = $run->errors()->get();
        $warnings = collect($log)->filter(fn ($entry) => is_array($entry) && ($entry['level'] ?? '') === 'warning');
    @endphp

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <x-stat-card label="Ditemukan" value="{{ $run->records_found }}" icon="🔍" color="blue"/>
        <x-stat-card label="Dibuat" value="{{ $run->records_created }}" icon="➕" color="green"/>
        <x-stat-card label="Diperbarui" value="{{ $run->records_updated }}" icon="🔄" color="amber"/>
        <x-stat-card label="Tidak Berubah" value="{{ $run->records_unchanged }}" icon="✅" color="gray"/>
        <x-stat-card label="Gagal" value="{{ $run->records_failed }}" icon="⚠️" color="red"/>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Eksekusi --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Info Eksekusi</div>
            <x-info-grid :rows="[
                ['Mulai', $run->started_at?->format('d M Y H:i:s') ?? '-'],
                ['Selesai', $run->finished_at?->format('d M Y H:i:s') ?? '-'],
                ['Durasi', $run->humanDuration() ?? '-'],
                ['Jumlah Error', $run->error_count],
                ['Sumber', $meta['source_label'] ?? $run->source?->name ?? '-'],
            ]"/>
        </div>

        {{-- Endpoint / retry --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Endpoint & Retry</div>
            <x-info-grid :rows="[
                ['Base URL', $meta['base_url'] ?? '-'],
                ['Source Slug', $run->source?->slug ?? '-'],
                ['Rekaman Terkait', $run->records()->count()],
            ]"/>
        </div>
    </div>

    {{-- Warnings / raw log --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Log Sinkronisasi</div>
        <div class="p-4">
            <pre class="text-[11px] leading-relaxed bg-gray-900 text-emerald-100 rounded-xl p-4 overflow-x-auto max-h-90">{{ json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
</div>
@endsection