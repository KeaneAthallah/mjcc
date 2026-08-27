@extends('layouts.app')

@section('title', 'Tambah Kecamatan')

@section('content')
<div class="page-transition max-w-2xl mx-auto space-y-4">
    <x-page-title title="Tambah Kecamatan" subtitle="Lengkapi data kecamatan baru">
        <x-slot:actions>
            <x-button href="{{ route('master.kecamatans.index') }}" variant="outline" size="sm">← Kembali</x-button>
        </x-slot:actions>
    </x-page-title>

    <form method="POST" action="{{ route('master.kecamatans.store') }}" class="space-y-5 pb-4">
        @csrf
        <x-card title="Informasi Kecamatan" icon="🗂️">
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input label="Nama Kecamatan" name="name" required placeholder="cth: Bungku Tengah" :error="$errors->first('name')" />
                    <x-input label="Kode" name="code" placeholder="cth: KL07" :error="$errors->first('code')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input label="Koordinat Latitude" name="latitude" placeholder="cth: -2.9000" :error="$errors->first('latitude')" />
                    <x-input label="Koordinat Longitude" name="longitude" placeholder="cth: 121.9000" :error="$errors->first('longitude')" />
                </div>
                <div>
                    <label class="block text-[12px] font-bold text-gray-700 mb-1">Deskripsi</label>
                    <textarea name="description" rows="3" placeholder="Deskripsi singkat kecamatan"
                              class="w-full rounded-xl border border-gray-300 text-[13px] px-3.5 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">{{ old('description') }}</textarea>
                </div>
                <label class="inline-flex items-center gap-2 text-[13px] font-bold text-gray-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded accent-emerald-600 w-4 h-4">
                    Kecamatan Aktif
                </label>
            </div>
        </x-card>

        <div class="flex justify-end gap-3">
            <x-button href="{{ route('master.kecamatans.index') }}" variant="outline">Batal</x-button>
            <x-button type="submit" variant="primary">Simpan Kecamatan</x-button>
        </div>
    </form>
</div>
@endsection
