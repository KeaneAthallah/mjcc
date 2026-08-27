@extends('layouts.app')

@section('title', $tipkamtikma->title)

@section('content')
<div class="page-transition space-y-5">
    <x-page-title :title="$tipkamtikma->title" :subtitle="'Kecamatan ' . ($tipkamtikma->kecamatan?->name ?? '-')">
        <x-slot:actions>
            <x-button href="{{ route('security.tipkamtikmas.index') }}" variant="outline" size="sm">← Daftar</x-button>
            <x-button href="{{ route('security.tipkamtikmas.edit', $tipkamtikma) }}" variant="blue" size="sm">✏️ Ubah</x-button>
            <x-button href="{{ route('security.tipkamtikmas.destroy', $tipkamtikma) }}" variant="red" size="sm"
                      data-confirm data-confirm-title="Hapus Tipkamtikmas"
                      data-confirm-message="Hapus data '{{ $tipkamtikma->title }}'?">🗑️ Hapus</x-button>
        </x-slot:actions>
    </x-page-title>

    <x-card title="Informasi Tipkamtikmas" icon="🪖">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3.5 text-[13px]">
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Nama / Judul</dt><dd class="font-bold text-gray-800">{{ $tipkamtikma->title }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Status</dt><dd><x-badge color="{{ ($tipkamtikma->status ?? 'aktif') === 'aktif' ? 'green' : 'gray' }}">{{ ucfirst($tipkamtikma->status ?? 'aktif') }}</x-badge></dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kecamatan</dt><dd class="font-bold text-gray-800">{{ $tipkamtikma->kecamatan?->name ?? '-' }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kelurahan/Desa</dt><dd class="font-bold text-gray-800">{{ $tipkamtikma->kelurahan?->name ?? '-' }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Koordinat</dt><dd class="font-mono text-gray-800">{{ $tipkamtikma->latitude ? $tipkamtikma->latitude . ', ' . $tipkamtikma->longitude : '-' }}</dd></div>
            <div class="flex flex-col sm:col-span-2"><dt class="text-gray-400 text-[11px]">Deskripsi</dt><dd class="text-gray-800">{{ $tipkamtikma->description ?: '-' }}</dd></div>
        </dl>
    </x-card>
</div>
@endsection
