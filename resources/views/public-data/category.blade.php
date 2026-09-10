@extends('layouts.app')

@section('title', $categoryLabel . ' — Data Publik')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title :title="$categoryIcon . ' ' . $categoryLabel" :subtitle="$categoryDescription">
        <x-slot:actions>
            <a href="{{ route('public-data.dashboard') }}" class="inline-flex items-center gap-1.5 text-[12px] font-bold text-gray-600 border border-gray-200 hover:border-gray-300 rounded-xl px-3 py-2 transition">
                ← Kembali
            </a>
        </x-slot:actions>
    </x-page-title>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($sources as $source)
            <x-source-status-card :source="[
                'key' => $source->key,
                'name' => $source->name,
                'status' => $source->status,
                'status_label' => $source->statusLabel(),
                'record_count' => $source->record_count,
                'url' => route('public-data.source', $source->key),
                'freshness' => $source->freshnessLabel(),
            ]"/>
        @endforeach
    </div>

    @if ($sources->isEmpty())
        <x-empty-state title="Belum ada sumber data" message="Sumber data untuk kategori ini belum terdaftar."/>
    @endif

</div>
@endsection