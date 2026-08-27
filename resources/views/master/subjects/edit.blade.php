@extends('layouts.app')

@section('title', 'Ubah Mata Pelajaran')

@section('content')
<div class="page-transition max-w-2xl mx-auto space-y-4">
    <x-page-title title="Ubah Mata Pelajaran" subtitle="{{ $subject->name }}">
        <x-slot:actions>
            <x-button href="{{ route('master.subjects.show', $subject) }}" variant="outline" size="sm">← Lihat</x-button>
        </x-slot:actions>
    </x-page-title>

    <form method="POST" action="{{ route('master.subjects.update', $subject) }}" class="space-y-5 pb-4">
        @csrf
        @method('PUT')
        <x-card title="Informasi Mata Pelajaran" icon="📚">
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input label="Nama Mata Pelajaran" name="name" required placeholder="Nama mapel" :value="$subject->name" :error="$errors->first('name')" />
                    <x-input label="Kode" name="code" placeholder="cth: MTK" :value="$subject->code" :error="$errors->first('code')" />
                </div>
                <div>
                    <label class="block text-[12px] font-bold text-gray-700 mb-1">Deskripsi</label>
                    <textarea name="description" rows="3" placeholder="Deskripsi singkat"
                              class="w-full rounded-xl border border-gray-300 text-[13px] px-3.5 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">{{ old('description', $subject->description) }}</textarea>
                </div>
                <label class="inline-flex items-center gap-2 text-[13px] font-bold text-gray-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $subject->is_active)) class="rounded accent-emerald-600 w-4 h-4">
                    Mapel Aktif
                </label>
            </div>
        </x-card>

        <div class="flex justify-end gap-3">
            <x-button href="{{ route('master.subjects.index') }}" variant="outline">Batal</x-button>
            <x-button type="submit" variant="primary">Perbarui Mapel</x-button>
        </div>
    </form>
</div>
@endsection
