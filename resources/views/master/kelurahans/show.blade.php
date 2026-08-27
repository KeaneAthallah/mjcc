@extends('layouts.app')

@section('title', $kelurahan->name)

@section('content')
<div class="page-transition space-y-5">
    <x-page-title :title="$kelurahan->name" :subtitle="'Kecamatan ' . ($kelurahan->kecamatan?->name ?? '-')">
        <x-slot:actions>
            <x-button href="{{ route('master.kelurahans.index') }}" variant="outline" size="sm">← Daftar</x-button>
            @canwrite('kelurahan')
                <x-button href="{{ route('master.kelurahans.edit', $kelurahan) }}" variant="blue" size="sm">✏️ Ubah</x-button>
                <x-button href="{{ route('master.kelurahans.destroy', $kelurahan) }}" variant="red" size="sm"
                          data-confirm data-confirm-title="Hapus Kelurahan"
                          data-confirm-message="Hapus data kelurahan '{{ $kelurahan->name }}'?">🗑️ Hapus</x-button>
            @endcanwrite
        </x-slot:actions>
    </x-page-title>

    <x-card title="Informasi Kelurahan/Desa" icon="📋">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3.5 text-[13px]">
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kecamatan</dt><dd class="font-bold text-gray-800">{{ $kelurahan->kecamatan?->name ?? '-' }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kode</dt><dd class="font-bold text-gray-800">{{ $kelurahan->code ?: '-' }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Status</dt><dd class="font-bold text-gray-800">{{ $kelurahan->status ?: '-' }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Populasi</dt><dd class="font-bold text-gray-800">{{ number_format($kelurahan->population) }} jiwa</dd></div>
            <div class="flex flex-col sm:col-span-2"><dt class="text-gray-400 text-[11px]">Koordinat</dt><dd class="font-mono text-gray-800">{{ $kelurahan->latitude ? $kelurahan->latitude . ', ' . $kelurahan->longitude : '-' }}</dd></div>
        </dl>
    </x-card>
</div>
@endsection
