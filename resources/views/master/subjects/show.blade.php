@extends('layouts.app')

@section('title', $subject->name)

@section('content')
<div class="page-transition space-y-5">
    <x-page-title :title="$subject->name" subtitle="Mata pelajaran">
        <x-slot:actions>
            <x-button href="{{ route('master.subjects.index') }}" variant="outline" size="sm">← Daftar</x-button>
            <x-button href="{{ route('master.subjects.edit', $subject) }}" variant="blue" size="sm">✏️ Ubah</x-button>
            <x-button href="{{ route('master.subjects.destroy', $subject) }}" variant="red" size="sm"
                      data-confirm data-confirm-title="Hapus Mata Pelajaran"
                      data-confirm-message="Hapus mata pelajaran '{{ $subject->name }}'?">🗑️ Hapus</x-button>
        </x-slot:actions>
    </x-page-title>

    <x-card title="Informasi Mata Pelajaran" icon="📚">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3.5 text-[13px]">
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Nama</dt><dd class="font-bold text-gray-800">{{ $subject->name }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Kode</dt><dd class="font-bold text-gray-800">{{ $subject->code ?: '-' }}</dd></div>
            <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Status</dt><dd><x-badge color="{{ $subject->is_active ? 'green' : 'gray' }}">{{ $subject->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></dd></div>
            <div class="flex flex-col sm:col-span-2"><dt class="text-gray-400 text-[11px]">Deskripsi</dt><dd class="text-gray-800">{{ $subject->description ?: '-' }}</dd></div>
        </dl>
    </x-card>
</div>
@endsection
