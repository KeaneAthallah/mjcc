@extends('layouts.app')

@section('title', $market->name)

@section('content')
<div class="page-transition space-y-5">
    <x-page-title :title="$market->name" :subtitle="'Kecamatan ' . ($market->kecamatan?->name ?? '-')">
        <x-slot:actions>
            <x-button href="{{ route('security.markets.index') }}" variant="outline" size="sm">← Daftar</x-button>
            <x-button href="{{ route('security.markets.edit', $market) }}" variant="blue" size="sm">✏️ Ubah</x-button>
            <x-button href="{{ route('security.markets.destroy', $market) }}" variant="red" size="sm"
                      data-confirm data-confirm-title="Hapus Pasar"
                      data-confirm-message="Hapus data pasar '{{ $market->name }}'?">🗑️ Hapus</x-button>
        </x-slot:actions>
    </x-page-title>

    <x-card title="Informasi Pasar" icon="🏪">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3.5 text-[13px]">
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Nama</dt><dd class="font-bold text-gray-800">{{ $market->name }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kecamatan</dt><dd class="font-bold text-gray-800">{{ $market->kecamatan?->name ?? '-' }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Status</dt><dd><x-badge color="{{ ($market->status ?? 'Aktif') === 'Aktif' ? 'green' : 'gray' }}">{{ $market->status ?? 'Aktif' }}</x-badge></dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Koordinat</dt><dd class="font-mono text-gray-800">{{ $market->latitude ? $market->latitude . ', ' . $market->longitude : '-' }}</dd></div>
            <div class="flex flex-col sm:col-span-2"><dt class="text-gray-400 text-[11px]">Alamat</dt><dd class="text-gray-800">{{ $market->address ?: '-' }}</dd></div>
        </dl>
    </x-card>
</div>
@endsection
