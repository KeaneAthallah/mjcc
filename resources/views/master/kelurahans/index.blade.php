@extends('layouts.app')

@section('title', 'Data Kelurahan/Desa')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Data Kelurahan/Desa" subtitle="Kelola kelurahan dan desa di Kabupaten Morowali">
        <x-slot:actions>
            @canwrite('kelurahan')
                <x-button href="{{ route('master.kelurahans.create') }}" variant="primary" size="sm">+ Tambah Kelurahan</x-button>
            @endcanwrite
        </x-slot:actions>
    </x-page-title>

    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari nama kelurahan...">
        <select name="kecamatan"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500 min-w-[160px]">
            <option value="">Semua Kecamatan</option>
            @foreach ($kecamatans as $k)
                <option value="{{ $k->id }}" @selected((string) request('kecamatan') === (string) $k->id)>{{ $k->name }}</option>
            @endforeach
        </select>
    </x-filter-bar>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($kelurahans->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Kelurahan/Desa</th>
                            <th class="text-left px-2 py-3 font-bold">Kecamatan</th>
                            <th class="text-center px-2 py-3 font-bold">Kode</th>
                            <th class="text-center px-2 py-3 font-bold">Populasi</th>
                            <th class="text-right px-4 py-3 font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($kelurahans as $kelurahan)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('master.kelurahans.show', $kelurahan) }}" class="font-bold text-gray-800 hover:text-emerald-600">{{ $kelurahan->name }}</a>
                                    <div class="text-[11px] text-gray-400">{{ $kelurahan->status ?: '-' }}</div>
                                </td>
                                <td class="px-2 py-3 text-gray-600">{{ $kelurahan->kecamatan?->name ?? '-' }}</td>
                                <td class="text-center px-2 py-3 text-gray-600">{{ $kelurahan->code ?: '-' }}</td>
                                <td class="text-center px-2 py-3 text-gray-700 font-semibold">{{ number_format($kelurahan->population) }}</td>
                                <td class="text-right px-4 py-3">
                                    <div class="inline-flex gap-1">
                                        <a href="{{ route('master.kelurahans.show', $kelurahan) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500" title="Lihat">👁️</a>
                                        @canwrite('kelurahan')
                                            <a href="{{ route('master.kelurahans.edit', $kelurahan) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-blue-600" title="Ubah">✏️</a>
                                            <a href="{{ route('master.kelurahans.destroy', $kelurahan) }}"
                                               data-confirm data-confirm-title="Hapus Kelurahan"
                                               data-confirm-message="Hapus data kelurahan '{{ $kelurahan->name }}'?"
                                               class="p-1.5 rounded-lg hover:bg-red-50 text-red-600" title="Hapus">🗑️</a>
                                        @endcanwrite
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$kelurahans" />
        @else
            <x-empty-state title="Belum ada data kelurahan" description="Tambahkan kelurahan/desa yang pertama." />
        @endif
    </div>
</div>
@endsection
