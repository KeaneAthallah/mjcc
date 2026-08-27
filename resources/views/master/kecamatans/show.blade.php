@extends('layouts.app')

@section('title', $kecamatan->name)

@section('content')
<div class="page-transition space-y-5">
    <x-page-title :title="$kecamatan->name" subtitle="Kecamatan · Kabupaten Morowali">
        <x-slot:actions>
            <x-button href="{{ route('master.kecamatans.index') }}" variant="outline" size="sm">← Daftar</x-button>
            @canwrite('kecamatan')
                <x-button href="{{ route('master.kecamatans.edit', $kecamatan) }}" variant="blue" size="sm">✏️ Ubah</x-button>
                <x-button href="{{ route('master.kecamatans.destroy', $kecamatan) }}" variant="red" size="sm"
                          data-confirm data-confirm-title="Hapus Kecamatan"
                          data-confirm-message="Hapus data kecamatan '{{ $kecamatan->name }}'?">🗑️ Hapus</x-button>
            @endcanwrite
        </x-slot:actions>
    </x-page-title>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-stat-card label="Kelurahan/Desa" value="{{ number_format($kecamatan->kelurahans_count) }}" icon="🏘️" color="green"/>
        <x-stat-card label="Sekolah" value="{{ number_format($kecamatan->schools_count) }}" icon="🏫" color="blue"/>
        <x-stat-card label="Polsek" value="{{ number_format($kecamatan->polseks_count) }}" icon="🚓" color="red"/>
        <x-stat-card label="Fasilitas Kesehatan" value="{{ number_format($kecamatan->healthFacilities_count) }}" icon="🏥" color="amber"/>
    </div>

    <x-card title="Informasi Kecamatan" icon="📋">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3.5 text-[13px]">
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kode</dt><dd class="font-bold text-gray-800">{{ $kecamatan->code ?: '-' }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Status</dt><dd><x-badge color="{{ $kecamatan->is_active ? 'green' : 'gray' }}">{{ $kecamatan->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Koordinat</dt><dd class="font-mono text-gray-800">{{ $kecamatan->latitude ? $kecamatan->latitude . ', ' . $kecamatan->longitude : '-' }}</dd></div>
            <div class="flex flex-col sm:col-span-2"><dt class="text-gray-400 text-[11px]">Deskripsi</dt><dd class="text-gray-800">{{ $kecamatan->description ?: '-' }}</dd></div>
        </dl>
    </x-card>
</div>
@endsection
