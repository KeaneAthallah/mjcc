@extends('layouts.app')

@section('title', $polsek->name)

@section('content')
<div class="page-transition space-y-5">
    <x-page-title :title="$polsek->name" :subtitle="'Kecamatan ' . ($polsek->kecamatan?->name ?? '-')">
        <x-slot:actions>
            <x-button href="{{ route('security.polseks.index') }}" variant="outline" size="sm">← Daftar</x-button>
            <x-button href="{{ route('security.polseks.edit', $polsek) }}" variant="blue" size="sm">✏️ Ubah</x-button>
            <x-button href="{{ route('security.polseks.destroy', $polsek) }}" variant="red" size="sm"
                      data-confirm data-confirm-title="Hapus Polsek"
                      data-confirm-message="Hapus data polsek '{{ $polsek->name }}'?">🗑️ Hapus</x-button>
        </x-slot:actions>
    </x-page-title>

    <div class="grid grid-cols-2 md:grid-cols-2 gap-3">
        <x-stat-card label="Personel" value="{{ number_format($polsek->personnel_count) }}" icon="👮" color="blue"/>
        <x-stat-card label="Poskamling" value="{{ number_format($polsek->poskamling_count) }}" icon="🛡️" color="green"/>
    </div>

    <x-card title="Informasi Polsek" icon="📋">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3.5 text-[13px]">
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Nama</dt><dd class="font-bold text-gray-800">{{ $polsek->name }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kecamatan</dt><dd class="font-bold text-gray-800">{{ $polsek->kecamatan?->name ?? '-' }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Status</dt><dd><x-badge color="{{ ($polsek->status ?? 'Aktif') === 'Aktif' ? 'green' : 'gray' }}">{{ $polsek->status ?? 'Aktif' }}</x-badge></dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Koordinat</dt><dd class="font-mono text-gray-800">{{ $polsek->latitude ? $polsek->latitude . ', ' . $polsek->longitude : '-' }}</dd></div>
            <div class="flex flex-col sm:col-span-2"><dt class="text-gray-400 text-[11px]">Alamat</dt><dd class="text-gray-800">{{ $polsek->address ?: '-' }}</dd></div>
        </dl>
    </x-card>
</div>
@endsection
