@extends('layouts.app')

@section('title', 'Data Tipkamtikmas')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Data Tipkamtikmas" subtitle="Kelola data Tipkamtikmas (Tim Pemeliharaan Keamanan dan Ketertiban Masyarakat)">
        <x-slot:actions>
            <x-button href="{{ route('security.tipkamtikmas.create') }}" variant="primary" size="sm">+ Tambah Tipkamtikmas</x-button>
        </x-slot:actions>
    </x-page-title>

    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari nama Tipkamtikmas...">
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
            <option value="tidak aktif" @selected(request('status') === 'tidak aktif')>Tidak Aktif</option>
        </select>
    </x-filter-bar>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($tipkamtikmas->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Tipkamtikmas</th>
                            <th class="text-left px-2 py-3 font-bold">Kecamatan</th>
                            <th class="text-left px-2 py-3 font-bold">Kelurahan</th>
                            <th class="text-center px-2 py-3 font-bold">Status</th>
                            <th class="text-right px-4 py-3 font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($tipkamtikmas as $tipkamtikma)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('security.tipkamtikmas.show', $tipkamtikma) }}" class="font-bold text-gray-800 hover:text-emerald-600">{{ $tipkamtikma->title }}</a>
                                    <div class="text-[11px] text-gray-400 truncate max-w-[260px]">{{ $tipkamtikma->description ?: '-' }}</div>
                                </td>
                                <td class="px-2 py-3 text-gray-600">{{ $tipkamtikma->kecamatan?->name ?? '-' }}</td>
                                <td class="px-2 py-3 text-gray-600">{{ $tipkamtikma->kelurahan?->name ?? '-' }}</td>
                                <td class="text-center px-2 py-3">
                                    <x-badge color="{{ ($tipkamtikma->status ?? 'aktif') === 'aktif' ? 'green' : 'gray' }}">{{ ucfirst($tipkamtikma->status ?? 'aktif') }}</x-badge>
                                </td>
                                <td class="text-right px-4 py-3">
                                    <div class="inline-flex gap-1">
                                        <a href="{{ route('security.tipkamtikmas.show', $tipkamtikma) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500" title="Lihat">👁️</a>
                                        <a href="{{ route('security.tipkamtikmas.edit', $tipkamtikma) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-blue-600" title="Ubah">✏️</a>
                                        <a href="{{ route('security.tipkamtikmas.destroy', $tipkamtikma) }}"
                                           data-confirm data-confirm-title="Hapus Tipkamtikmas"
                                           data-confirm-message="Hapus data '{{ $tipkamtikma->title }}'?"
                                           class="p-1.5 rounded-lg hover:bg-red-50 text-red-600" title="Hapus">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$tipkamtikmas" />
        @else
            <x-empty-state title="Belum ada data Tipkamtikmas" description="Tambahkan data yang pertama." />
        @endif
    </div>
</div>
@endsection
