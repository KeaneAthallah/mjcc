@extends('layouts.app')

@section('title', 'Tambah Kelurahan')

@section('content')
<div class="page-transition max-w-2xl mx-auto space-y-4">
    <x-page-title title="Tambah Kelurahan/Desa" subtitle="Lengkapi data kelurahan/desa baru">
        <x-slot:actions>
            <x-button href="{{ route('master.kelurahans.index') }}" variant="outline" size="sm">← Kembali</x-button>
        </x-slot:actions>
    </x-page-title>

    <form method="POST" action="{{ route('master.kelurahans.store') }}" class="space-y-5 pb-4">
        @csrf
        <x-card title="Informasi Kelurahan" icon="🏘️">
            <div class="space-y-4">
                <div>
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Kecamatan <span class="text-red-500">*</span></label>
                    <select name="kecamatan_id" required
                            class="w-full rounded-xl border text-[13px] px-3 py-2.5 bg-white focus:outline-none focus:ring-2 transition {{ $errors->has('kecamatan_id') ? 'border-red-400 focus:ring-red-200' : 'border-gray-300 focus:ring-emerald-200 focus:border-emerald-500' }}">
                        <option value="">Pilih Kecamatan</option>
                        @foreach ($kecamatans as $k)
                            <option value="{{ $k->id }}" @selected(old('kecamatan_id') == $k->id)>{{ $k->name }}</option>
                        @endforeach
                    </select>
                    @error('kecamatan_id') <p class="text-[11px] text-red-600 font-medium mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input label="Nama Kelurahan/Desa" name="name" required placeholder="cth: Kelurahan Laroenai" :error="$errors->first('name')" />
                    <x-input label="Kode" name="code" placeholder="cth: KL01" :error="$errors->first('code')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input label="Koordinat Latitude" name="latitude" placeholder="cth: -2.9000" :error="$errors->first('latitude')" />
                    <x-input label="Koordinat Longitude" name="longitude" placeholder="cth: 121.9000" :error="$errors->first('longitude')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input label="Populasi" name="population" type="number" min="0" placeholder="Jumlah penduduk" :error="$errors->first('population')" />
                    <x-input label="Status" name="status" placeholder="cth: Kelurahan / Desa" :error="$errors->first('status')" />
                </div>
            </div>
        </x-card>

        <div class="flex justify-end gap-3">
            <x-button href="{{ route('master.kelurahans.index') }}" variant="outline">Batal</x-button>
            <x-button type="submit" variant="primary">Simpan Kelurahan</x-button>
        </div>
    </form>
</div>
@endsection
