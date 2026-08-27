@extends('layouts.app')

@section('title', 'Data Poskamling')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Data Poskamling" subtitle="Kelola data Pos Kamling (Pos Keamanan Lingkungan)">
        <x-slot:actions>
            <x-button href="{{ route('security.poskamlings.create') }}" variant="primary" size="sm">+ Tambah Poskamling</x-button>
        </x-slot:actions>
    </x-page-title>

    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari nama poskamling...">
        <select name="kecamatan"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500 min-w-[160px]">
            <option value="">Semua Kecamatan</option>
            @foreach ($kecamatans as $k)
                <option value="{{ $k->id }}" @selected((string) request('kecamatan') === (string) $k->id)>{{ $k->name }}</option>
            @endforeach
        </select>
        <select name="status"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Status</option>
            <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected(request('status') === 'nonaktif')>Tidak Aktif</option>
        </select>
    </x-filter-bar>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($poskamlings->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Poskamling</th>
                            <th class="text-left px-2 py-3 font-bold">Kecamatan</th>
                            <th class="text-left px-2 py-3 font-bold">Kelurahan</th>
                            <th class="text-center px-2 py-3 font-bold">Status</th>
                            <th class="text-right px-4 py-3 font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($poskamlings as $poskamling)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('security.poskamlings.show', $poskamling) }}" class="font-bold text-gray-800 hover:text-emerald-600">{{ $poskamling->name }}</a>
                                    <div class="text-[11px] text-gray-400">{{ $poskamling->status ?: '-' }}</div>
                                </td>
                                <td class="px-2 py-3 text-gray-600">{{ $poskamling->kecamatan?->name ?? '-' }}</td>
                                <td class="px-2 py-3 text-gray-600">{{ $poskamling->kelurahan?->name ?? '-' }}</td>
                                <td class="text-center px-2 py-3">
                                    <x-badge color="{{ $poskamling->is_active ? 'green' : 'gray' }}">{{ $poskamling->is_active ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
                                </td>
                                <td class="text-right px-4 py-3">
                                    <div class="inline-flex gap-1">
                                        <a href="{{ route('security.poskamlings.show', $poskamling) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500" title="Lihat">👁️</a>
                                        <a href="{{ route('security.poskamlings.edit', $poskamling) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-blue-600" title="Ubah">✏️</a>
                                        <a href="{{ route('security.poskamlings.destroy', $poskamling) }}"
                                           data-confirm data-confirm-title="Hapus Poskamling"
                                           data-confirm-message="Hapus data '{{ $poskamling->name }}'?"
                                           class="p-1.5 rounded-lg hover:bg-red-50 text-red-600" title="Hapus">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$poskamlings" />
        @else
            <x-empty-state title="Belum ada data poskamling" description="Tambahkan poskamling yang pertama." />
        @endif
    </div>
</div>
@endsection
