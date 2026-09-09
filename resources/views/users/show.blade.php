@extends('layouts.app')

@section('title', $user->name)

@section('content')
<div class="page-transition space-y-5">

    <x-page-title :title="$user->name" :subtitle="'Akun pengguna · ' . ucfirst($user->role)">
        <x-slot:actions>
            <x-button href="{{ route('users.index') }}" variant="outline" size="sm">← Daftar</x-button>
            @if ($user->hasVerifiedEmail())
                <form method="POST" action="{{ route('users.verify-email', $user) }}" class="inline">@csrf
                    <input type="hidden" name="verified" value="0">
                    <x-button type="submit" variant="outline" size="sm">⏸️ Tandai Belum Diverifikasi</x-button>
                </form>
            @else
                <form method="POST" action="{{ route('users.verify-email', $user) }}" class="inline">@csrf
                    <input type="hidden" name="verified" value="1">
                    <x-button type="submit" variant="primary" size="sm">✅ Verifikasi Email</x-button>
                </form>
            @endif
            <x-button href="{{ route('users.edit', $user) }}" variant="blue" size="sm">✏️ Ubah</x-button>
            @if ($user->id !== $currentUser->id)
                <x-button href="{{ route('users.destroy', $user) }}" variant="red" size="sm"
                          data-confirm data-confirm-title="Hapus Pengguna"
                          data-confirm-message="Hapus akun '{{ $user->name }}'?">🗑️ Hapus</x-button>
            @endif
        </x-slot:actions>
    </x-page-title>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <x-card title="Informasi Akun" icon="👤">
            <div class="flex items-center gap-4 mb-5">
                <span class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-blue-600 text-white flex items-center justify-center text-2xl font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                <div>
                    <div class="text-lg font-bold text-gray-800">{{ $user->name }}</div>
                    <div class="flex items-center gap-1.5">
                        <x-badge color="{{ match($user->role) { 'admin' => 'red', 'operator' => 'blue', default => 'green' } }}">{{ ucfirst($user->role) }}</x-badge>
                        @if ($user->responder_type)
                            <x-badge color="{{ match($user->responder_type) { 'medical' => 'teal', 'fire' => 'orange', default => 'indigo' } }}">{{ $user->responder_type_label }}</x-badge>
                        @endif
                    </div>
                </div>
            </div>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3.5 text-[13px]">
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Nama Lengkap</dt><dd class="font-bold text-gray-800">{{ $user->name }}</dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Email</dt><dd class="font-bold text-gray-800">{{ $user->email }}</dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Verifikasi Email</dt><dd><x-badge color="{{ $user->hasVerifiedEmail() ? 'green' : 'amber' }}">{{ $user->hasVerifiedEmail() ? 'Terverifikasi' : 'Belum diverifikasi' }}</x-badge></dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Peran</dt><dd><x-badge color="{{ match($user->role) { 'admin' => 'red', 'operator' => 'blue', default => 'green' } }}">{{ ucfirst($user->role) }}</x-badge></dd></div>
                @if ($user->responder_type)
                    <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Peran Petugas</dt><dd><x-badge color="{{ match($user->responder_type) { 'medical' => 'teal', 'fire' => 'orange', default => 'indigo' } }}">{{ $user->responder_type_label }}</x-badge></dd></div>
                @endif
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Status Akun</dt><dd><x-badge color="green">Aktif</x-badge></dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Terdaftar Sejak</dt><dd class="font-bold text-gray-800">{{ $user->created_at?->translatedFormat('d M Y') ?? '-' }}</dd></div>
                <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Terakhir Diperbarui</dt><dd class="font-bold text-gray-800">{{ $user->updated_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="Hak Akses" icon="🔐">
            @php
                $permissions = [
                    'admin' => [
                        ['label' => 'Kelola pengguna', 'allowed' => true],
                        ['label' => 'Tulis data kecamatan', 'allowed' => true],
                        ['label' => 'Tulis data operasional', 'allowed' => true],
                        ['label' => 'Menambah / mengubah / menghapus data', 'allowed' => true],
                        ['label' => 'Melihat semua dashboard & peta', 'allowed' => true],
                    ],
                    'operator' => [
                        ['label' => 'Kelola pengguna', 'allowed' => false],
                        ['label' => 'Tulis data kecamatan', 'allowed' => false],
                        ['label' => 'Tulis data operasional', 'allowed' => true],
                        ['label' => 'Menambah / mengubah / menghapus data', 'allowed' => true],
                        ['label' => 'Melihat semua dashboard & peta', 'allowed' => true],
                    ],
                    'viewer' => [
                        ['label' => 'Kelola pengguna', 'allowed' => false],
                        ['label' => 'Tulis data kecamatan', 'allowed' => false],
                        ['label' => 'Tulis data operasional', 'allowed' => false],
                        ['label' => 'Menambah / mengubah / menghapus data', 'allowed' => false],
                        ['label' => 'Melihat semua dashboard & peta', 'allowed' => true],
                    ],
                ];
                $rows = $permissions[$user->role] ?? [];
            @endphp
            <ul class="space-y-3">
                @foreach ($rows as $row)
                    <li class="flex items-center justify-between text-[13px] gap-3">
                        <span class="text-gray-700">{{ $row['label'] }}</span>
                        <x-badge color="{{ $row['allowed'] ? 'green' : 'gray' }}">{{ $row['allowed'] ? 'Diizinkan' : 'Tidak' }}</x-badge>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>
</div>
@endsection
