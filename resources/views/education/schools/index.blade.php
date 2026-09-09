@extends('layouts.app')

@section('title', 'Data Sekolah')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Data Sekolah" subtitle="Kelola data sekolah di Kabupaten Morowali">
        <x-slot:actions>
            <x-button href="{{ route('education.schools.trash') }}" variant="ghost" size="sm">🗑️ Sampah</x-button>
            <x-button href="{{ route('education.schools.create') }}" variant="primary" size="sm">+ Tambah Sekolah</x-button>
        </x-slot:actions>
    </x-page-title>

    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari nama / NPSN sekolah...">
        <select name="kecamatan"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500 min-w-[160px]">
            <option value="">Semua Kecamatan</option>
            @foreach ($kecamatans as $k)
                <option value="{{ $k->id }}" @selected((string) request('kecamatan') === (string) $k->id)>{{ $k->name }}</option>
            @endforeach
        </select>
        <select name="type"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Jenjang</option>
            @foreach (\App\Models\School::SCHOOL_TYPES as $t)
                <option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>
            @endforeach
        </select>
        <select name="status"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Status</option>
            <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
        </select>
    </x-filter-bar>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($schools->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Sekolah</th>
                            <th class="text-left px-2 py-3 font-bold">Kecamatan</th>
                            <th class="text-center px-2 py-3 font-bold">Jenjang</th>
                            <th class="text-center px-2 py-3 font-bold">Siswa</th>
                            <th class="text-center px-2 py-3 font-bold">Guru</th>
                            <th class="text-center px-2 py-3 font-bold">Status</th>
                            <th class="text-right px-4 py-3 font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($schools as $school)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('education.schools.show', $school) }}" class="font-bold text-gray-800 hover:text-emerald-600">{{ $school->name }}</a>
                                    <div class="text-[11px] text-gray-400">NPSN: {{ $school->npsn ?: '-' }}</div>
                                </td>
                                <td class="px-2 py-3 text-gray-600">{{ $school->kecamatan?->name ?? '-' }}</td>
                                <td class="text-center px-2 py-3">
                                    <x-badge color="{{ $school->school_type === 'SD' ? 'green' : 'blue' }}">{{ $school->school_type }}</x-badge>
                                </td>
                                <td class="text-center px-2 py-3 text-gray-700 font-semibold">{{ number_format($school->students_male + $school->students_female) }}</td>
                                <td class="text-center px-2 py-3 text-gray-700">{{ number_format($school->teachers) }}</td>
                                <td class="text-center px-2 py-3">
                                    <x-badge color="{{ $school->is_active ? 'green' : 'gray' }}">{{ $school->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                                </td>
                                <td class="text-right px-4 py-3">
                                    <div class="inline-flex gap-1">
                                        <a href="{{ route('education.schools.show', $school) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500" title="Lihat">👁️</a>
                                        <a href="{{ route('education.schools.edit', $school) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-blue-600" title="Ubah">✏️</a>
                                        <a href="{{ route('education.schools.destroy', $school) }}"
                                           data-confirm data-confirm-title="Hapus Sekolah"
                                           data-confirm-message="Hapus data sekolah '{{ $school->name }}'? Tindakan ini tidak dapat dibatalkan."
                                           class="p-1.5 rounded-lg hover:bg-red-50 text-red-600" title="Hapus">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$schools" />
        @else
            <x-empty-state title="Belum ada data sekolah"
                           description="Tambahkan data sekolah pertama Anda menggunakan tombol 'Tambah Sekolah'." />
        @endif
    </div>
</div>
@endsection
