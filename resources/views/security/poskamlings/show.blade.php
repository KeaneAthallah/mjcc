@extends('layouts.app')

@section('title', $poskamling->name)

@section('content')
<div class="page-transition space-y-5">
    <x-page-title :title="$poskamling->name" :subtitle="'Kecamatan ' . ($poskamling->kecamatan?->name ?? '-')">
        <x-slot:actions>
            <x-button href="{{ route('security.poskamlings.index') }}" variant="outline" size="sm">← Daftar</x-button>
            <x-button href="{{ route('security.poskamlings.edit', $poskamling) }}" variant="blue" size="sm">✏️ Ubah</x-button>
            <x-button href="{{ route('security.poskamlings.destroy', $poskamling) }}" variant="red" size="sm"
                      data-confirm data-confirm-title="Hapus Poskamling"
                      data-confirm-message="Hapus data '{{ $poskamling->name }}'?">🗑️ Hapus</x-button>
        </x-slot:actions>
    </x-page-title>

    <x-card title="Informasi Poskamling" icon="🛡️">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3.5 text-[13px]">
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Nama</dt><dd class="font-bold text-gray-800">{{ $poskamling->name }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Status</dt><dd><x-badge color="{{ $poskamling->is_active ? 'green' : 'gray' }}">{{ $poskamling->is_active ? 'Aktif' : 'Tidak Aktif' }}</x-badge></dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kecamatan</dt><dd class="font-bold text-gray-800">{{ $poskamling->kecamatan?->name ?? '-' }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kelurahan/Desa</dt><dd class="font-bold text-gray-800">{{ $poskamling->kelurahan?->name ?? '-' }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Koordinator / Status</dt><dd class="font-bold text-gray-800">{{ ($poskamling->status ?: ($poskamling->is_active ? 'Aktif' : 'Tidak Aktif')) }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Koordinat</dt><dd class="font-mono text-gray-800">{{ $poskamling->latitude ? $poskamling->latitude . ', ' . $poskamling->longitude : '-' }}</dd></div>
        </dl>
    </x-card>
</div>
@endsection
