@extends('layouts.app')

@section('title', 'Tambah Poskamling')

@section('content')
<div class="page-transition max-w-2xl mx-auto space-y-4">
    <x-page-title title="Tambah Poskamling" subtitle="Lengkapi data poskamling baru">
        <x-slot:actions>
            <x-button href="{{ route('security.poskamlings.index') }}" variant="outline" size="sm">← Kembali</x-button>
        </x-slot:actions>
    </x-page-title>

    <form method="POST" action="{{ route('security.poskamlings.store') }}" class="space-y-5 pb-4">
        @csrf
        <x-card title="Informasi Poskamling" icon="🛡️">
            <div class="space-y-4">
                <x-kecamatan-kelurahan
                    :kecamatans="$kecamatans"
                    :kelurahans="$kelurahans"
                    kecamatan-error="{{ $errors->first('kecamatan_id') }}"
                    kelurahan-error="{{ $errors->first('kelurahan_id') }}"
                />
                <x-input label="Nama Poskamling" name="name" required placeholder="cth: Poskamling Laroenai" :error="$errors->first('name')" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input label="Koordinat Latitude" name="latitude" placeholder="cth: -2.9000" :error="$errors->first('latitude')" />
                    <x-input label="Koordinat Longitude" name="longitude" placeholder="cth: 121.9000" :error="$errors->first('longitude')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input label="Status" name="status" placeholder="cth: Berjaga / Siaga" :error="$errors->first('status')" />
                    <label class="inline-flex items-center gap-2 text-[13px] font-bold text-gray-700 self-end pb-2.5">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded accent-emerald-600 w-4 h-4">
                        Aktif
                    </label>
                </div>
            </div>
        </x-card>

        <div class="flex justify-end gap-3">
            <x-button href="{{ route('security.poskamlings.index') }}" variant="outline">Batal</x-button>
            <x-button type="submit" variant="primary">Simpan Poskamling</x-button>
        </div>
    </form>
</div>
@endsection
