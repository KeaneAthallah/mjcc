@extends('layouts.app')

@section('title', 'Sampah')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Sampah" subtitle="Data {{ $label }} yang telah dihapus">
        <x-slot:actions>
            <x-button href="{{ route($route.'.index') }}" variant="outline" size="sm">← Kembali</x-button>
        </x-slot:actions>
    </x-page-title>

    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari data {{ $label }}..." action="{{ route($route.'.trash') }}" />

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($trashed->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Nama</th>
                            <th class="text-left px-2 py-3 font-bold">Dihapus Pada</th>
                            <th class="text-right px-4 py-3 font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($trashed as $item)
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="px-4 py-3 font-bold text-gray-800">{{ $item->{$searchColumn} }}</td>
                                <td class="px-2 py-3 text-gray-500">{{ $item->deleted_at?->format('d M Y H:i') }}</td>
                                <td class="text-right px-4 py-3">
                                    <div class="inline-flex gap-1">
                                        @can('restore', $item)
                                            <form method="POST" action="{{ route($route.'.restore', $item) }}">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit"
                                                        class="px-2.5 py-1.5 rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 text-[11px] font-semibold"
                                                        title="Pulihkan">↩ Pulihkan</button>
                                            </form>
                                        @endcan
                                        @can('forceDelete', $item)
                                            <form method="POST" action="{{ route($route.'.force-destroy', $item) }}"
                                                  data-confirm data-confirm-title="Hapus Permanen"
                                                  data-confirm-message="Hapus permanen '{{ $item->{$searchColumn} }}'? Tindakan ini tidak dapat dibatalkan.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="px-2.5 py-1.5 rounded-lg bg-red-50 text-red-700 hover:bg-red-100 text-[11px] font-semibold"
                                                        title="Hapus Permanen">Hapus Permanen</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$trashed" />
        @else
            <x-empty-state title="Sampah kosong" description="Tidak ada data {{ $label }} yang dihapus untuk filter ini." />
        @endif
    </div>
</div>
@endsection
