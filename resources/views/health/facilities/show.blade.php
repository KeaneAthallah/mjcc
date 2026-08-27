@extends('layouts.app')

@section('title', $facility->name)

@section('content')
<div class="page-transition space-y-5">
    <x-page-title :title="$facility->name" :subtitle="'Kecamatan ' . ($facility->kecamatan?->name ?? '-')">
        <x-slot:actions>
            <x-button href="{{ route('health.facilities.index') }}" variant="outline" size="sm">← Daftar</x-button>
            <x-button href="{{ route('health.facilities.edit', $facility) }}" variant="blue" size="sm">✏️ Ubah</x-button>
            <x-button href="{{ route('health.facilities.destroy', $facility) }}" variant="red" size="sm"
                      data-confirm data-confirm-title="Hapus Fasilitas"
                      data-confirm-message="Hapus data fasilitas '{{ $facility->name }}'?">🗑️ Hapus</x-button>
        </x-slot:actions>
    </x-page-title>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-stat-card label="Dokter" value="{{ number_format($facility->doctors) }}" icon="🩺" color="blue"/>
        <x-stat-card label="Perawat" value="{{ number_format($facility->nurses) }}" icon="👩‍⚕️" color="green"/>
        <x-stat-card label="Bidan" value="{{ number_format($facility->midwives) }}" icon="👶" color="amber"/>
        <x-stat-card label="Tempat Tidur" value="{{ number_format($facility->beds) }}" icon="🛏️" color="red"/>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <x-card title="Informasi Fasilitas" icon="📋">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3.5 text-[13px]">
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Nama</dt><dd class="font-bold text-gray-800">{{ $facility->name }}</dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Jenis</dt><dd><x-badge color="{{ match($facility->facility_type) { 'Puskesmas' => 'green', 'Rumah Sakit' => 'red', 'Pustu' => 'indigo', default => 'amber' } }}">{{ $facility->facility_type }}</x-badge></dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kecamatan</dt><dd class="font-bold text-gray-800">{{ $facility->kecamatan?->name ?? '-' }}</dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Status</dt><dd><x-badge color="{{ ($facility->status ?? 'Aktif') === 'Aktif' ? 'green' : 'gray' }}">{{ $facility->status ?? 'Aktif' }}</x-badge></dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Alamat</dt><dd class="text-gray-800">{{ $facility->address ?: '-' }}</dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Telepon</dt><dd class="font-mono text-gray-800">{{ $facility->phone ?: '-' }}</dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kondisi</dt><dd class="font-bold text-gray-800">{{ $facility->condition ?: '-' }}</dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Koordinat</dt><dd class="font-mono text-gray-800">{{ $facility->latitude ? $facility->latitude . ', ' . $facility->longitude : '-' }}</dd></div>
                <div class="flex flex-col sm:col-span-2"><dt class="text-gray-400 text-[11px]">Deskripsi</dt><dd class="text-gray-800">{{ $facility->description ?: '-' }}</dd></div>
            </dl>
        </x-card>

        @if ($facility->latitude && $facility->longitude)
            <x-card title="Lokasi di Peta" icon="🗺️" :padding="false">
                <div id="facility-map" class="h-[360px]"></div>
            </x-card>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@if ($facility->latitude && $facility->longitude)
<script>
document.addEventListener('DOMContentLoaded', () => {
    window.Mjcc.maps.createMap('facility-map', [{
        name: @json($facility->name),
        category: @json($facility->facility_type),
        latitude: @json($facility->latitude),
        longitude: @json($facility->longitude),
        kecamatan: @json($facility->kecamatan?->name),
        details: {
            'Dokter': @json($facility->doctors),
            'Perawat': @json($facility->nurses),
            'Bed': @json($facility->beds),
        },
    }], { cluster: false, zoom: 13, resize: true, fitBounds: false });
});
</script>
@endif
@endpush
