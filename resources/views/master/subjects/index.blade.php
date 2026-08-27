@extends('layouts.app')

@section('title', 'Data Mata Pelajaran')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Data Mata Pelajaran" subtitle="Kelola mata pelajaran sekolah">
        <x-slot:actions>
            <x-button href="{{ route('master.subjects.create') }}" variant="primary" size="sm">+ Tambah Mapel</x-button>
        </x-slot:actions>
    </x-page-title>

    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari nama mata pelajaran...">
        <select name="status"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Status</option>
            <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
        </select>
    </x-filter-bar>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($subjects->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Mata Pelajaran</th>
                            <th class="text-center px-2 py-3 font-bold">Kode</th>
                            <th class="text-center px-2 py-3 font-bold">Status</th>
                            <th class="text-right px-4 py-3 font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($subjects as $subject)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('master.subjects.show', $subject) }}" class="font-bold text-gray-800 hover:text-emerald-600">{{ $subject->name }}</a>
                                    <div class="text-[11px] text-gray-400 truncate max-w-[300px]">{{ $subject->description ?: '-' }}</div>
                                </td>
                                <td class="text-center px-2 py-3 text-gray-600">{{ $subject->code ?: '-' }}</td>
                                <td class="text-center px-2 py-3">
                                    <x-badge color="{{ $subject->is_active ? 'green' : 'gray' }}">{{ $subject->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                                </td>
                                <td class="text-right px-4 py-3">
                                    <div class="inline-flex gap-1">
                                        <a href="{{ route('master.subjects.show', $subject) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500" title="Lihat">👁️</a>
                                        <a href="{{ route('master.subjects.edit', $subject) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-blue-600" title="Ubah">✏️</a>
                                        <a href="{{ route('master.subjects.destroy', $subject) }}"
                                           data-confirm data-confirm-title="Hapus Mata Pelajaran"
                                           data-confirm-message="Hapus mata pelajaran '{{ $subject->name }}'?"
                                           class="p-1.5 rounded-lg hover:bg-red-50 text-red-600" title="Hapus">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$subjects" />
        @else
            <x-empty-state title="Belum ada mata pelajaran" description="Tambahkan mata pelajaran yang pertama." />
        @endif
    </div>
</div>
@endsection
