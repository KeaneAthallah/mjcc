@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('content')
<div class="page-transition max-w-2xl mx-auto space-y-4">
    <x-page-title title="Tambah Pengguna" subtitle="Buat akun pengguna baru">
        <x-slot:actions>
            <x-button href="{{ route('users.index') }}" variant="outline" size="sm">← Kembali</x-button>
        </x-slot:actions>
    </x-page-title>

    <form method="POST" action="{{ route('users.store') }}" class="space-y-5 pb-4">
        @csrf
        <x-card title="Informasi Pengguna" icon="👤">
            <div class="space-y-4">
                <x-input label="Nama Lengkap" name="name" required placeholder="Nama pengguna" :error="$errors->first('name')" />
                <x-input label="Email" name="email" type="email" required placeholder="nama@morowali.go.id" :error="$errors->first('email')" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input label="Kata Sandi" name="password" type="password" required placeholder="Minimal 8 karakter" :error="$errors->first('password')" />
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Peran <span class="text-red-500">*</span></label>
                        <select name="role" required
                                class="w-full rounded-xl border text-[13px] px-3 py-2.5 bg-white focus:outline-none focus:ring-2 transition {{ $errors->has('role') ? 'border-red-400 focus:ring-red-200' : 'border-gray-300 focus:ring-emerald-200 focus:border-emerald-500' }}">
                            <option value="">Pilih Peran</option>
                            <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                            <option value="operator" @selected(old('role') === 'operator')>Operator</option>
                            <option value="viewer" @selected(old('role') === 'viewer')>Viewer</option>
                        </select>
                        @error('role') <p class="text-[11px] text-red-600 font-medium mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3 text-[12px] text-emerald-800">
                    <strong>Admin</strong> — akses penuh (data & pengguna) · <strong>Operator</strong> — kelola data operasional · <strong>Viewer</strong> — hanya membaca.
                </div>
            </div>
        </x-card>

        <div class="flex justify-end gap-3">
            <x-button href="{{ route('users.index') }}" variant="outline">Batal</x-button>
            <x-button type="submit" variant="primary">Simpan Pengguna</x-button>
        </div>
    </form>
</div>
@endsection
