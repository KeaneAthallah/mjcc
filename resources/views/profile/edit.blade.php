@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="page-transition max-w-3xl mx-auto space-y-5">

    <x-page-title title="Profile" subtitle="Kelola informasi akun Anda" />

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
        <span class="w-16 h-16 rounded-full bg-gradient-to-br from-emerald-500 to-blue-600 text-white flex items-center justify-center text-2xl font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
        <div>
            <h2 class="text-lg font-extrabold text-gray-900">{{ $user->name }}</h2>
            <p class="text-[13px] text-gray-500">{{ $user->email }}</p>
            <x-badge color="{{ match($user->role) { 'admin' => 'red', 'operator' => 'blue', default => 'green' } }}" class="mt-1">{{ ucfirst($user->role) }}</x-badge>
        </div>
    </div>

    {{-- Update profile --}}
    <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('PUT')
        <x-card title="Informasi Profil" icon="👤">
            <div class="space-y-4">
                <x-input label="Nama Lengkap" name="name" required placeholder="Nama Anda" :value="$user->name" :error="$errors->first('name')" />
                <x-input label="Email" name="email" type="email" required placeholder="nama@morowali.go.id" :value="$user->email" :error="$errors->first('email')" />
            </div>
        </x-card>
        <div class="flex justify-end pb-2">
            <x-button type="submit" variant="primary">Perbarui Profil</x-button>
        </div>
    </form>

    {{-- Change password --}}
    <form method="POST" action="{{ route('profile.password') }}" class="space-y-5 pb-4">
        @csrf
        @method('PUT')
        <x-card title="Ubah Kata Sandi" icon="🔒">
            <div class="space-y-4">
                <x-input label="Kata Sandi Saat Ini" name="current_password" type="password" required placeholder="Kata sandi saat ini" :error="$errors->first('current_password')" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input label="Kata Sandi Baru" name="password" type="password" required placeholder="Minimal 8 karakter" :error="$errors->first('password')" />
                    <x-input label="Konfirmasi Kata Sandi" name="password_confirmation" type="password" required placeholder="Ulangi kata sandi baru" :error="$errors->first('password_confirmation')" />
                </div>
            </div>
        </x-card>
        <div class="flex justify-end pb-2">
            <x-button type="submit" variant="dark">Perbarui Kata Sandi</x-button>
        </div>
    </form>
</div>
@endsection
