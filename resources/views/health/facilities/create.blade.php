@extends('layouts.app')

@section('title', 'Tambah Fasilitas Kesehatan')

@section('content')
<div class="page-transition max-w-3xl mx-auto space-y-4">
    <x-page-title title="Tambah Fasilitas Kesehatan" subtitle="Lengkapi data fasilitas kesehatan baru">
        <x-slot:actions>
            <x-button href="{{ route('health.facilities.index') }}" variant="outline" size="sm">← Kembali</x-button>
        </x-slot:actions>
    </x-page-title>

    <form method="POST" action="{{ route('health.facilities.store') }}" class="space-y-5 pb-4">
        @csrf
        <x-card title="Informasi Fasilitas" icon="🏥">
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Jenis Fasilitas <span class="text-red-500">*</span></label>
                        <select name="facility_type" required
                                class="w-full rounded-xl border text-[13px] px-3 py-2.5 bg-white focus:outline-none focus:ring-2 transition {{ $errors->has('facility_type') ? 'border-red-400 focus:ring-red-200' : 'border-gray-300 focus:ring-emerald-200 focus:border-emerald-500' }}">
                            <option value="">Pilih Jenis</option>
                            @foreach ($types as $t)
                                <option value="{{ $t }}" @selected(old('facility_type') === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                        @error('facility_type') <p class="text-[11px] text-red-600 font-medium mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <x-input label="Nama Fasilitas" name="name" required placeholder="cth: Puskesmas Bungku" :error="$errors->first('name')" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Alamat" name="address" placeholder="Alamat fasilitas" :error="$errors->first('address')" />
                    <x-input label="Telepon" name="phone" placeholder="cth: 0852-xxxx-xxxx" :error="$errors->first('phone')" />
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <x-input label="Koordinat Latitude" name="latitude" placeholder="cth: -2.9000" :error="$errors->first('latitude')" />
                    <x-input label="Koordinat Longitude" name="longitude" placeholder="cth: 121.9000" :error="$errors->first('longitude')" />
                    <x-input label="Kondisi" name="condition" placeholder="Baik / Rusak" :error="$errors->first('condition')" />
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <x-input label="Dokter" name="doctors" type="number" min="0" required :error="$errors->first('doctors')" />
                    <x-input label="Perawat" name="nurses" type="number" min="0" required :error="$errors->first('nurses')" />
                    <x-input label="Bidan" name="midwives" type="number" min="0" required :error="$errors->first('midwives')" />
                    <x-input label="Tempat Tidur" name="beds" type="number" min="0" required :error="$errors->first('beds')" />
                </div>
                <div>
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Status</label>
                    <select name="status"
                            class="w-full rounded-xl border text-[13px] px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500 {{ $errors->has('status') ? 'border-red-400' : 'border-gray-300' }}">
                        <option value="Aktif" @selected(old('status') === 'Aktif')>Aktif</option>
                        <option value="Tidak Aktif" @selected(old('status') === 'Tidak Aktif')>Tidak Aktif</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[12px] font-bold text-gray-700 mb-1">Deskripsi</label>
                    <textarea name="description" rows="3" placeholder="Deskripsi singkat fasilitas"
                              class="w-full rounded-xl border border-gray-300 text-[13px] px-3.5 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">{{ old('description') }}</textarea>
                </div>
            </div>
        </x-card>

        <div class="flex justify-end gap-3">
            <x-button href="{{ route('health.facilities.index') }}" variant="outline">Batal</x-button>
            <x-button type="submit" variant="primary">Simpan Fasilitas</x-button>
        </div>
    </form>
</div>
@endsection
