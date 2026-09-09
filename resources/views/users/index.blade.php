@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Manajemen Pengguna" subtitle="Kelola akun pengguna sistem">
        <x-slot:actions>
            <x-button href="{{ route('users.create') }}" variant="primary" size="sm">+ Tambah Pengguna</x-button>
        </x-slot:actions>
    </x-page-title>

    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari nama / email...">
        <select name="role"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Peran</option>
            <option value="admin" @selected(request('role') === 'admin')>Admin</option>
            <option value="operator" @selected(request('role') === 'operator')>Operator</option>
            <option value="viewer" @selected(request('role') === 'viewer')>Viewer</option>
        </select>
    </x-filter-bar>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($users->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Pengguna</th>
                            <th class="text-left px-2 py-3 font-bold">Email</th>
                            <th class="text-center px-2 py-3 font-bold">Peran</th>
                            <th class="text-right px-4 py-3 font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-blue-600 text-white flex items-center justify-center text-[12px] font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                        <a href="{{ route('users.show', $user) }}" class="font-bold text-gray-800 hover:text-emerald-600">{{ $user->name }} @if ($user->id === $currentUser->id)<span class="text-[10px] text-emerald-600 font-bold">(Anda)</span>@endif</a>
                                    </div>
                                </td>
                                <td class="px-2 py-3">
                                    <div class="text-gray-600">{{ $user->email }}</div>
                                    <x-badge color="{{ $user->hasVerifiedEmail() ? 'green' : 'amber' }}">{{ $user->hasVerifiedEmail() ? 'Terverifikasi' : 'Belum diverifikasi' }}</x-badge>
                                </td>
                                <td class="text-center px-2 py-3">
                                    <div class="flex flex-col items-center gap-1">
                                        <x-badge color="{{ match($user->role) { 'admin' => 'red', 'operator' => 'blue', default => 'green' } }}">{{ ucfirst($user->role) }}</x-badge>
                                        @if ($user->responder_type)
                                            <x-badge color="{{ match($user->responder_type) { 'medical' => 'teal', 'fire' => 'orange', default => 'indigo' } }}">{{ $user->responder_type_label }}</x-badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right px-4 py-3">
                                    <div class="inline-flex gap-1">
                                        <form method="POST" action="{{ route('users.verify-email', $user) }}">@csrf
                                            @if ($user->hasVerifiedEmail())
                                                <button type="submit" name="verified" value="0" class="p-1.5 rounded-lg hover:bg-amber-50 text-amber-600" title="Tandai Belum Diverifikasi">⏸️</button>
                                            @else
                                                <button type="submit" name="verified" value="1" class="p-1.5 rounded-lg hover:bg-emerald-50 text-emerald-600" title="Verifikasi Email">✅</button>
                                            @endif
                                        </form>
                                        <a href="{{ route('users.edit', $user) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-blue-600" title="Ubah">✏️</a>
                                        @if ($user->id !== $currentUser->id)
                                            <a href="{{ route('users.destroy', $user) }}"
                                               data-confirm data-confirm-title="Hapus Pengguna"
                                               data-confirm-message="Hapus akun '{{ $user->name }}'?"
                                               class="p-1.5 rounded-lg hover:bg-red-50 text-red-600" title="Hapus">🗑️</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$users" />
        @else
            <x-empty-state title="Belum ada pengguna" description="Tambahkan pengguna yang pertama." />
        @endif
    </div>
</div>
@endsection
