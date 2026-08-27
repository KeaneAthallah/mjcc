@extends('layouts.app')

@section('title', 'Tambah Tipkamtikmas')

@section('content')
<div class="page-transition max-w-2xl mx-auto space-y-4">
    <x-page-title title="Tambah Tipkamtikmas" subtitle="Lengkapi data Tipkamtikmas baru">
        <x-slot:actions>
            <x-button href="{{ route('security.tipkamtikmas.index') }}" variant="outline" size="sm">← Kembali</x-button>
        </x-slot:actions>
    </x-page-title>

    <form method="POST" action="{{ route('security.tipkamtikmas.store') }}" class="space-y-5 pb-4">
        @csrf
        <x-card title="Informasi Tipkamtikmas" icon="🪖">
            <div class="space-y-4">
                <x-kecamatan-kelurahan
                    :kecamatans="$kecamatans"
                    :kelurahans="$kelurahans"
                    kecamatan-error="{{ $errors->first('kecamatan_id') }}"
                    kelurahan-error="{{ $errors->first('kelurahan_id') }}"
                />
                <x-input label="Nama / Judul" name="title" required placeholder="cth: Tipkamtikmas Bungku" :error="$errors->first('title')" />
                <div>
                    <label class="block text-[12px] font-bold text-gray-700 mb-1">Deskripsi</label>
                    <textarea name="description" rows="3" placeholder="Deskripsi kegiatan/personel"
                              class="w-full rounded-xl border border-gray-300 text-[13px] px-3.5 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">{{ old('description') }}</textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input label="Koordinat Latitude" name="latitude" placeholder="cth: -2.9000" :error="$errors->first('latitude')" />
                    <x-input label="Koordinat Longitude" name="longitude" placeholder="cth: 121.9000" :error="$errors->first('longitude')" />
                </div>
                <div>
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Status</label>
                    <select name="status"
                            class="w-full rounded-xl border text-[13px] px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500 {{ $errors->has('status') ? 'border-red-400' : 'border-gray-300' }}">
                        <option value="aktif" @selected(old('status') === 'aktif')>Aktif</option>
                        <option value="tidak aktif" @selected(old('status') === 'tidak aktif')>Tidak Aktif</option>
                    </select>
                </div>
            </div>
        </x-card>

        <div class="flex justify-end gap-3">
            <x-button href="{{ route('security.tipkamtikmas.index') }}" variant="outline">Batal</x-button>
            <x-button type="submit" variant="primary">Simpan Tipkamtikmas</x-button>
        </div>
    </form>
</div>
@endsection
