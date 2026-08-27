@extends('layouts.app')

@section('title', 'Data Fasilitas Kesehatan')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Data Fasilitas Kesehatan" subtitle="Kelola fasilitas dan tenaga kesehatan di Kabupaten Morowali">
        <x-slot:actions>
            <x-button href="{{ route('health.facilities.create') }}" variant="primary" size="sm">+ Tambah Faskes</x-button>
        </x-slot:actions>
    </x-page-title>

    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari nama fasilitas...">
        <select name="kecamatan"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500 min-w-[160px]">
            <option value="">Semua Kecamatan</option>
            @foreach ($kecamatans as $k)
                <option value="{{ $k->id }}" @selected((string) request('kecamatan') === (string) $k->id)>{{ $k->name }}</option>
            @endforeach
        </select>
        <select name="type"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Jenis</option>
            @foreach ($types as $t)
                <option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>
            @endforeach
        </select>
        <select name="status"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Status</option>
            <option value="Aktif" @selected(request('status') === 'Aktif')>Aktif</option>
            <option value="Tidak Aktif" @selected(request('status') === 'Tidak Aktif')>Tidak Aktif</option>
        </select>
    </x-filter-bar>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($facilities->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Fasilitas</th>
                            <th class="text-left px-2 py-3 font-bold">Kecamatan</th>
                            <th class="text-center px-2 py-3 font-bold">Jenis</th>
                            <th class="text-center px-2 py-3 font-bold">Dokter</th>
                            <th class="text-center px-2 py-3 font-bold">Perawat</th>
                            <th class="text-center px-2 py-3 font-bold">Bed</th>
                            <th class="text-right px-4 py-3 font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($facilities as $facility)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('health.facilities.show', $facility) }}" class="font-bold text-gray-800 hover:text-emerald-600">{{ $facility->name }}</a>
                                </td>
                                <td class="px-2 py-3 text-gray-600">{{ $facility->kecamatan?->name ?? '-' }}</td>
                                <td class="text-center px-2 py-3">
                                    <x-badge color="{{ match($facility->facility_type) { 'Puskesmas' => 'green', 'Rumah Sakit' => 'red', 'Pustu' => 'indigo', default => 'amber' } }}">{{ $facility->facility_type }}</x-badge>
                                </td>
                                <td class="text-center px-2 py-3 text-gray-700 font-semibold">{{ $facility->doctors }}</td>
                                <td class="text-center px-2 py-3 text-gray-700">{{ $facility->nurses }}</td>
                                <td class="text-center px-2 py-3 text-blue-600 font-bold">{{ $facility->beds }}</td>
                                <td class="text-right px-4 py-3">
                                    <div class="inline-flex gap-1">
                                        <a href="{{ route('health.facilities.show', $facility) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500" title="Lihat">👁️</a>
                                        <a href="{{ route('health.facilities.edit', $facility) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-blue-600" title="Ubah">✏️</a>
                                        <a href="{{ route('health.facilities.destroy', $facility) }}"
                                           data-confirm data-confirm-title="Hapus Fasilitas"
                                           data-confirm-message="Hapus data fasilitas '{{ $facility->name }}'?"
                                           class="p-1.5 rounded-lg hover:bg-red-50 text-red-600" title="Hapus">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$facilities" />
        @else
            <x-empty-state title="Belum ada data fasilitas" description="Tambahkan fasilitas kesehatan yang pertama." />
        @endif
    </div>
</div>
@endsection
