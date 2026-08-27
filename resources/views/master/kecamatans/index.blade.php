@extends('layouts.app')

@section('title', 'Data Kecamatan')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Data Kecamatan" subtitle="Kelola data kecamatan Kabupaten Morowali">
        <x-slot:actions>
            @canwrite('kecamatan')
                <x-button href="{{ route('master.kecamatans.create') }}" variant="primary" size="sm">+ Tambah Kecamatan</x-button>
            @endcanwrite
        </x-slot:actions>
    </x-page-title>

    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari nama kecamatan...">
        <select name="status"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Status</option>
            <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
        </select>
    </x-filter-bar>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($kecamatans->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Kecamatan</th>
                            <th class="text-center px-2 py-3 font-bold">Kode</th>
                            <th class="text-center px-2 py-3 font-bold">Status</th>
                            <th class="text-right px-4 py-3 font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($kecamatans as $kecamatan)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('master.kecamatans.show', $kecamatan) }}" class="font-bold text-gray-800 hover:text-emerald-600">{{ $kecamatan->name }}</a>
                                    <div class="text-[11px] text-gray-400 truncate max-w-[280px]">{{ $kecamatan->description ?: '-' }}</div>
                                </td>
                                <td class="text-center px-2 py-3 text-gray-600">{{ $kecamatan->code ?: '-' }}</td>
                                <td class="text-center px-2 py-3">
                                    <x-badge color="{{ $kecamatan->is_active ? 'green' : 'gray' }}">{{ $kecamatan->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                                </td>
                                <td class="text-right px-4 py-3">
                                    <div class="inline-flex gap-1">
                                        <a href="{{ route('master.kecamatans.show', $kecamatan) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500" title="Lihat">👁️</a>
                                        @canwrite('kecamatan')
                                            <a href="{{ route('master.kecamatans.edit', $kecamatan) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-blue-600" title="Ubah">✏️</a>
                                            <a href="{{ route('master.kecamatans.destroy', $kecamatan) }}"
                                               data-confirm data-confirm-title="Hapus Kecamatan"
                                               data-confirm-message="Hapus data kecamatan '{{ $kecamatan->name }}'?"
                                               class="p-1.5 rounded-lg hover:bg-red-50 text-red-600" title="Hapus">🗑️</a>
                                        @endcanwrite
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$kecamatans" />
        @else
            <x-empty-state title="Belum ada data kecamatan" description="Tambahkan kecamatan yang pertama." />
        @endif
    </div>
</div>
@endsection
