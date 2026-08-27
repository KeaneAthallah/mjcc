@extends('layouts.app')

@section('title', 'Ubah Polsek')

@section('content')
<div class="page-transition max-w-2xl mx-auto space-y-4">
    <x-page-title title="Ubah Polsek" subtitle="{{ $polsek->name }}">
        <x-slot:actions>
            <x-button href="{{ route('security.polseks.show', $polsek) }}" variant="outline" size="sm">← Lihat</x-button>
        </x-slot:actions>
    </x-page-title>

    <form method="POST" action="{{ route('security.polseks.update', $polsek) }}" class="space-y-5 pb-4">
        @csrf
        @method('PUT')
        <x-card title="Informasi Polsek" icon="🚓">
            <div class="space-y-4">
                <div>
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Kecamatan <span class="text-red-500">*</span></label>
                    <select name="kecamatan_id" required
                            class="w-full rounded-xl border text-[13px] px-3 py-2.5 bg-white focus:outline-none focus:ring-2 transition {{ $errors->has('kecamatan_id') ? 'border-red-400 focus:ring-red-200' : 'border-gray-300 focus:ring-emerald-200 focus:border-emerald-500' }}">
                        <option value="">Pilih Kecamatan</option>
                        @foreach ($kecamatans as $k)
                            <option value="{{ $k->id }}" @selected(old('kecamatan_id', $polsek->kecamatan_id) == $k->id)>{{ $k->name }}</option>
                        @endforeach
                    </select>
                    @error('kecamatan_id') <p class="text-[11px] text-red-600 font-medium mt-1">{{ $message }}</p> @enderror
                </div>
                <x-input label="Nama Polsek" name="name" required placeholder="Nama polsek" :value="$polsek->name" :error="$errors->first('name')" />
                <x-input label="Alamat" name="address" placeholder="Alamat polsek" :value="$polsek->address" :error="$errors->first('address')" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input label="Koordinat Latitude" name="latitude" placeholder="cth: -2.9000" :value="$polsek->latitude" :error="$errors->first('latitude')" />
                    <x-input label="Koordinat Longitude" name="longitude" placeholder="cth: 121.9000" :value="$polsek->longitude" :error="$errors->first('longitude')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input label="Jumlah Personel" name="personnel_count" type="number" min="0" :value="$polsek->personnel_count" :error="$errors->first('personnel_count')" />
                    <x-input label="Jumlah Poskamling" name="poskamling_count" type="number" min="0" :value="$polsek->poskamling_count" :error="$errors->first('poskamling_count')" />
                </div>
                <div>
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Status</label>
                    <select name="status"
                            class="w-full rounded-xl border text-[13px] px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500 {{ $errors->has('status') ? 'border-red-400' : 'border-gray-300' }}">
                        <option value="Aktif" @selected(old('status', $polsek->status) === 'Aktif')>Aktif</option>
                        <option value="Tidak Aktif" @selected(old('status', $polsek->status) === 'Tidak Aktif')>Tidak Aktif</option>
                    </select>
                </div>
            </div>
        </x-card>

        <div class="flex justify-end gap-3">
            <x-button href="{{ route('security.polseks.index') }}" variant="outline">Batal</x-button>
            <x-button type="submit" variant="primary">Perbarui Polsek</x-button>
        </div>
    </form>
</div>
@endsection
