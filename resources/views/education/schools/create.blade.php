@extends('layouts.app')

@section('title', 'Tambah Sekolah')

@section('content')
<div class="page-transition max-w-4xl mx-auto space-y-4">

    <x-page-title title="Tambah Sekolah" subtitle="Lengkapi data sekolah baru">
        <x-slot:actions>
            <x-button href="{{ route('education.schools.index') }}" variant="outline" size="sm">← Kembali</x-button>
        </x-slot:actions>
    </x-page-title>

    <form method="POST" action="{{ route('education.schools.store') }}" class="space-y-5">
        @csrf

        <x-card title="Informasi Dasar" icon="🏫">
            <div class="space-y-4">
                <x-kecamatan-kelurahan
                    :kecamatans="$kecamatans"
                    :kelurahans="$kelurahans"
                    kecamatan-error="{{ $errors->first('kecamatan_id') }}"
                    kelurahan-error="{{ $errors->first('kelurahan_id') }}"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Nama Sekolah" name="name" required placeholder="SD Negeri 1 Bungku" :error="$errors->first('name')" />
                    <x-input label="NPSN" name="npsn" placeholder="Contoh: 12345678" :error="$errors->first('npsn')" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Jenjang <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            @foreach (\App\Models\School::SCHOOL_TYPES as $t)
                                <label class="flex items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-[13px] font-bold cursor-pointer transition
                                    {{ old('school_type') === $t ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-300 text-gray-600 hover:border-emerald-300' }}">
                                    <input type="radio" name="school_type" value="{{ $t }}" @checked(old('school_type') === $t) class="accent-emerald-600">
                                    {{ $t }}
                                </label>
                            @endforeach
                        </div>
                        @error('school_type') <p class="text-[11px] text-red-600 font-medium mt-1">{{ $message }}</p> @enderror
                    </div>
                    <x-input label="Kondisi" name="condition" placeholder="Baik / Rusak Ringan / dsb." :error="$errors->first('condition')" />
                </div>

                <x-input label="Alamat" name="address" placeholder="Alamat lengkap sekolah" :error="$errors->first('address')" />
            </div>
        </x-card>

        <x-card title="Data Siswa & Guru" icon="🎓">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-input label="Siswa Laki-laki" name="students_male" type="number" min="0" required :error="$errors->first('students_male')" />
                <x-input label="Siswa Perempuan" name="students_female" type="number" min="0" required :error="$errors->first('students_female')" />
                <x-input label="Jumlah Guru" name="teachers" type="number" min="0" required :error="$errors->first('teachers')" />
                <x-input label="Jumlah Kelas" name="classes" type="number" min="0" required :error="$errors->first('classes')" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <x-input label="Kapasitas (Daya Tampung)" name="capacity" type="number" min="0" required :error="$errors->first('capacity')" />
                <x-input label="Koordinat Latitude" name="latitude" placeholder="cth: -3.0000" :error="$errors->first('latitude')" />
                <x-input label="Koordinat Longitude" name="longitude" placeholder="cth: 121.8500" :error="$errors->first('longitude')" />
            </div>
        </x-card>

        <x-card title="Kelengkapan Sarana (%)" icon="🏗️">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-3">
                @php
                    $facilities = [
                        'library_percentage' => 'Perpustakaan',
                        'science_lab_percentage' => 'Lab IPA',
                        'computer_lab_percentage' => 'Lab Komputer',
                        'teacher_room_percentage' => 'Ruang Guru',
                        'toilet_percentage' => 'WC / Toilet',
                        'worship_room_percentage' => 'Ruang Ibadah',
                    ];
                @endphp
                @foreach ($facilities as $field => $label)
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1" for="{{ $field }}">{{ $label }}</label>
                        <input type="number" min="0" max="100" name="{{ $field }}" id="{{ $field }}" value="{{ old($field) }}"
                               class="w-full rounded-xl border border-gray-300 text-[13px] px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="Mata Pelajaran & Status" icon="📚">
            <div class="space-y-4">
                <div>
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Mata Pelajaran</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach ($subjects as $sub)
                            <label class="flex items-center gap-2 text-[12px] text-gray-700">
                                <input type="checkbox" name="subjects[]" value="{{ $sub->id }}" @checked(in_array($sub->id, old('subjects', []))) class="rounded border-gray-300 accent-emerald-600">
                                {{ $sub->name }}
                            </label>
                        @endforeach
                    </div>
                    @error('subjects') <p class="text-[11px] text-red-600 font-medium mt-1">{{ $message }}</p> @enderror
                </div>
                <label class="inline-flex items-center gap-2 text-[13px] font-bold text-gray-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded accent-emerald-600 w-4 h-4">
                    Sekolah Aktif
                </label>
            </div>
        </x-card>

        <div class="flex justify-end gap-3 pb-4">
            <x-button href="{{ route('education.schools.index') }}" variant="outline">Batal</x-button>
            <x-button type="submit" variant="primary">Simpan Sekolah</x-button>
        </div>
    </form>
</div>
@endsection
