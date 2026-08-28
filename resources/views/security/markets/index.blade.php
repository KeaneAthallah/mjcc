@extends('layouts.app')

@section('title', 'Data Pasar')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Data Pasar" subtitle="Kelola data pasar rakyat di Kabupaten Morowali">
        <x-slot:actions>
            <x-button href="{{ route('security.markets.trash') }}" variant="ghost" size="sm">🗑️ Sampah</x-button>
            <x-button href="{{ route('security.markets.create') }}" variant="primary" size="sm">+ Tambah Pasar</x-button>
        </x-slot:actions>
    </x-page-title>

    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari nama pasar...">
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
            <option value="Aktif" @selected(request('status') === 'Aktif')>Aktif</option>
            <option value="Tidak Aktif" @selected(request('status') === 'Tidak Aktif')>Tidak Aktif</option>
        </select>
    </x-filter-bar>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($markets->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Pasar</th>
                            <th class="text-left px-2 py-3 font-bold">Kecamatan</th>
                            <th class="text-center px-2 py-3 font-bold">Status</th>
                            <th class="text-right px-4 py-3 font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($markets as $market)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('security.markets.show', $market) }}" class="font-bold text-gray-800 hover:text-emerald-600">{{ $market->name }}</a>
                                    <div class="text-[11px] text-gray-400 truncate max-w-[280px]">{{ $market->address ?: '-' }}</div>
                                </td>
                                <td class="px-2 py-3 text-gray-600">{{ $market->kecamatan?->name ?? '-' }}</td>
                                <td class="text-center px-2 py-3">
                                    <x-badge color="{{ ($market->status ?? 'Aktif') === 'Aktif' ? 'green' : 'gray' }}">{{ $market->status ?? 'Aktif' }}</x-badge>
                                </td>
                                <td class="text-right px-4 py-3">
                                    <div class="inline-flex gap-1">
                                        <a href="{{ route('security.markets.show', $market) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500" title="Lihat">👁️</a>
                                        <a href="{{ route('security.markets.edit', $market) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-blue-600" title="Ubah">✏️</a>
                                        <a href="{{ route('security.markets.destroy', $market) }}"
                                           data-confirm data-confirm-title="Hapus Pasar"
                                           data-confirm-message="Hapus data pasar '{{ $market->name }}'?"
                                           class="p-1.5 rounded-lg hover:bg-red-50 text-red-600" title="Hapus">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$markets" />
        @else
            <x-empty-state title="Belum ada data pasar" description="Tambahkan pasar yang pertama." />
        @endif
    </div>
</div>
@endsection
