@extends('layouts.app')

@section('title', 'Command Alerts')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Command Alerts" subtitle="Masalah terdeteksi dari data nyata — urut sesuai tingkat keparahan">
        <x-slot:actions>
            <x-alert-summary-badges :counts="$counts" :link="null"/>
        </x-slot:actions>
    </x-page-title>

    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari judul/deskripsi alert...">
        <select name="severity"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Keparahan</option>
            <option value="critical" @selected(request('severity') === 'critical')>🔴 Critical</option>
            <option value="warning" @selected(request('severity') === 'warning')>🟠 Warning</option>
            <option value="info" @selected(request('severity') === 'info')>🔵 Info</option>
        </select>
        <select name="sector"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Sektor</option>
            <option value="pendidikan" @selected(request('sector') === 'pendidikan')>Pendidikan</option>
            <option value="ketertiban" @selected(request('sector') === 'ketertiban')>Ketertiban</option>
            <option value="kesehatan" @selected(request('sector') === 'kesehatan')>Kesehatan</option>
        </select>
        <select name="status"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Status</option>
            @foreach (\App\Models\CommandAlert::statuses() as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ strtoupper($s) }}</option>
            @endforeach
        </select>
        <select name="kecamatan"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500 min-w-[150px]">
            <option value="">Semua Kecamatan</option>
            @foreach ($kecamatans as $k)
                <option value="{{ $k->id }}" @selected((string) request('kecamatan') === (string) $k->id)>{{ $k->name }}</option>
            @endforeach
        </select>
    </x-filter-bar>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($alerts->count())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-bold">Alert</th>
                            <th class="text-left px-2 py-3 font-bold">Keparahan</th>
                            <th class="text-left px-2 py-3 font-bold">Sektor</th>
                            <th class="text-left px-2 py-3 font-bold">Kecamatan</th>
                            <th class="text-left px-2 py-3 font-bold">Status</th>
                            <th class="text-left px-2 py-3 font-bold">Terbuka</th>
                            <th class="text-right px-4 py-3 font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($alerts as $alert)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('alerts.show', $alert) }}" class="font-bold text-gray-800 hover:text-emerald-600">{{ $alert->title }}</a>
                                    <div class="text-[11px] text-gray-400">{{ $alert->description }}</div>
                                </td>
                                <td class="px-2 py-3"><x-severity-badge :severity="$alert->severity"/></td>
                                <td class="px-2 py-3 text-gray-600">{{ $alert->sector }}</td>
                                <td class="px-2 py-3 text-gray-600">{{ $alert->kecamatan?->name ?? '-' }}</td>
                                <td class="px-2 py-3"><x-badge color="{{ ['baru' => 'blue', 'ditinjau' => 'amber', 'ditangani' => 'indigo', 'selesai' => 'gray'][$alert->status] ?? 'gray' }}">{{ $alert->statusLabel() }}</x-badge></td>
                                <td class="px-2 py-3 text-gray-500">{{ $alert->opened_at?->diffForHumans() }}</td>
                                <td class="text-right px-4 py-3">
                                    <div class="inline-flex gap-1">
                                        @if ($alert->url())
                                            <a href="{{ $alert->url() }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500" title="Lihat detail">👁️</a>
                                        @endif
                                        @if ($alert->mapUrl())
                                            <a href="{{ $alert->mapUrl() }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-emerald-600" title="Lihat di peta">🗺️</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :rows="$alerts" />
        @else
            <x-empty-state title="Tidak ada alert" description="Tidak ada alert yang cocok dengan filter saat ini." />
        @endif
    </div>
</div>
@endsection