@extends('layouts.app')

@section('title', $school->name)

@section('content')
<div class="page-transition space-y-5">

    <x-page-title :title="$school->name" :subtitle="'Sekolah ' . $school->school_type . ' · Kecamatan ' . ($school->kecamatan?->name ?? '-')">
        <x-slot:actions>
            <x-button href="{{ route('education.schools.index') }}" variant="outline" size="sm">← Daftar</x-button>
            <x-button href="{{ route('education.schools.edit', $school) }}" variant="blue" size="sm">✏️ Ubah</x-button>
            <x-button href="{{ route('education.schools.destroy', $school) }}" variant="red" size="sm"
                      data-confirm data-confirm-title="Hapus Sekolah"
                      data-confirm-message="Hapus data sekolah '{{ $school->name }}'?">🗑️ Hapus</x-button>
        </x-slot:actions>
    </x-page-title>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-stat-card label="Siswa" value="{{ number_format($school->students_male + $school->students_female) }}" icon="🎓" color="green" footer="{{ $school->students_male }} L · {{ $school->students_female }} P"/>
        <x-stat-card label="Guru" value="{{ number_format($school->teachers) }}" icon="👩‍🏫" color="blue"/>
        <x-stat-card label="Kelas" value="{{ number_format($school->classes) }}" icon="🏫" color="amber"/>
        <x-stat-card label="Kapasitas" value="{{ number_format($school->capacity) }}" icon="🧱" color="red"/>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <x-card title="Informasi Umum" icon="📋">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3.5 text-[13px]">
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Nama Sekolah</dt><dd class="font-bold text-gray-800">{{ $school->name }}</dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">NPSN</dt><dd class="font-bold text-gray-800">{{ $school->npsn ?: '-' }}</dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Jenjang</dt><dd><x-badge color="{{ $school->school_type === 'SD' ? 'green' : 'blue' }}">{{ $school->school_type }}</x-badge></dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Status</dt><dd><x-badge color="{{ $school->is_active ? 'green' : 'gray' }}">{{ $school->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kecamatan</dt><dd class="font-bold text-gray-800">{{ $school->kecamatan?->name ?? '-' }}</dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kelurahan/Desa</dt><dd class="font-bold text-gray-800">{{ $school->kelurahan?->name ?? '-' }}</dd></div>
                <div class="flex flex-col sm:col-span-2"><dt class="text-gray-400 text-[11px]">Alamat</dt><dd class="text-gray-800">{{ $school->address ?: '-' }}</dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kondisi</dt><dd class="font-bold text-gray-800">{{ $school->condition ?: '-' }}</dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Koordinat</dt><dd class="font-mono text-gray-800">{{ $school->latitude ? $school->latitude . ', ' . $school->longitude : '-' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="Kelengkapan Sarana" icon="🏗️">
            @php
                $facs = [
                    'library_percentage' => 'Perpustakaan',
                    'science_lab_percentage' => 'Lab IPA',
                    'computer_lab_percentage' => 'Lab Komputer',
                    'teacher_room_percentage' => 'Ruang Guru',
                    'toilet_percentage' => 'WC / Toilet',
                    'worship_room_percentage' => 'Ruang Ibadah',
                ];
            @endphp
            <div class="space-y-3.5">
                @foreach ($facs as $field => $label)
                    @php $pct = (int) $school->$field; @endphp
                    <div>
                        <div class="flex justify-between text-[12px] font-semibold text-gray-700 mb-1">
                            <span>{{ $label }}</span><span class="text-emerald-600">{{ $pct }}%</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
                <div class="pt-2 flex justify-between text-[12px] text-gray-600">
                    <span class="font-bold">Mata Pelajaran:</span>
                    <span class="text-gray-800 font-semibold">{{ $school->subjects->count() }} mapel</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @forelse ($school->subjects as $sub)
                        <x-badge color="indigo">{{ $sub->name }}</x-badge>
                    @empty
                        <span class="text-[12px] text-gray-400">Belum ada</span>
                    @endforelse
                </div>
            </div>
        </x-card>
    </div>

    @if ($school->latitude && $school->longitude)
        <x-card title="Lokasi di Peta" icon="🗺️" :padding="false">
            <div id="school-map" class="h-[360px]"></div>
        </x-card>
    @endif
</div>
@endsection

@push('scripts')
@if ($school->latitude && $school->longitude)
<script>
document.addEventListener('DOMContentLoaded', () => {
    window.Mjcc.maps.createMap('school-map', [{
        name: @json($school->name),
        category: @json($school->school_type),
        latitude: @json($school->latitude),
        longitude: @json($school->longitude),
        kecamatan: @json($school->kecamatan?->name),
        details: {
            'Siswa': @json($school->students_male + $school->students_female),
            'Guru': @json($school->teachers),
            'Kondisi': @json($school->condition),
        },
    }], { cluster: false, zoom: 13, resize: true, fitBounds: false });
});
</script>
@endif
@endpush
