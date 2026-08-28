@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Log Aktivitas" subtitle="Jejak audit sistem untuk keperluan monitoring dan akuntabilitas" />

    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari resource / aksi...">
        <select name="user"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Pengguna</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(request('user') == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>

        <select name="action"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Aksi</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst(str_replace('_', ' ', $action)) }}</option>
            @endforeach
        </select>

        <select name="resource"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Resource</option>
            @foreach ($resources as $resource)
                <option value="{{ $resource }}" @selected(request('resource') === $resource)>{{ $resource }}</option>
            @endforeach
        </select>

        <input type="date" name="from" value="{{ request('from') }}"
               class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500" title="Dari tanggal">
        <input type="date" name="to" value="{{ request('to') }}"
               class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500" title="Sampai tanggal">
    </x-filter-bar>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($logs->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Waktu</th>
                            <th class="text-left px-2 py-3 font-bold">Pengguna</th>
                            <th class="text-left px-2 py-3 font-bold">Aksi</th>
                            <th class="text-left px-2 py-3 font-bold">Resource</th>
                            <th class="text-right px-4 py-3 font-bold">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($logs as $log)
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                    {{ $log->created_at->format('d M Y H:i:s') }}
                                </td>
                                <td class="px-2 py-3 font-semibold text-gray-700">
                                    {{ $log->user?->name ?? 'Sistem' }}
                                    <div class="text-[11px] text-gray-400">{{ $log->user?->email }}</div>
                                </td>
                                <td class="px-2 py-3">
                                    <x-badge color="{{ match($log->action) {
                                        'login' => 'green',
                                        'logout' => 'gray',
                                        'create' => 'blue',
                                        'update' => 'indigo',
                                        'delete' => 'red',
                                        'force_delete' => 'red',
                                        'restore' => 'teal',
                                        'role_change' => 'amber',
                                        default => 'gray',
                                    } }}">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</x-badge>
                                </td>
                                <td class="px-2 py-3 text-gray-700">
                                    {{ $log->description() }}
                                    @if ($log->resource_type)
                                        <div class="text-[11px] text-gray-400">#{{ $log->resource_id }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 font-mono text-[11px] text-right">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$logs" />
        @else
            <x-empty-state title="Tidak ada aktivitas" description="Belum ada kegiatan yang tercatat untuk filter ini." />
        @endif
    </div>
</div>
@endsection
