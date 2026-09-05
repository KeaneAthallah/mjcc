@extends('layouts.app')

@section('title', $alert->title)

@section('content')
<div class="page-transition space-y-4">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('alerts.index') }}" class="text-[12px] font-bold text-gray-500 hover:text-emerald-700">← Semua Alert</a>
        </div>
        <div class="flex items-center gap-2">
            @if ($alert->url())
                <a href="{{ $alert->url() }}" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 text-[12px] font-bold hover:bg-gray-200">Lihat Detail Data</a>
            @endif
            @if ($alert->mapUrl())
                <a href="{{ $alert->mapUrl() }}" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-[12px] font-bold hover:bg-emerald-700">🗺️ Lihat di Peta</a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-4">
            <x-dashboard-section :title="$alert->title" :subtitle="$alert->sector . ' · ' . $alert->severityLabel()" icon="⚠️">
                <p class="text-[14px] text-gray-600 leading-relaxed">{{ $alert->description }}</p>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4 text-[12px]">
                    <div class="rounded-lg bg-gray-50 px-3 py-2">
                        <dt class="text-gray-400">Aturan</dt>
                        <dd class="font-extrabold text-gray-800">{{ $alert->rule }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2">
                        <dt class="text-gray-400">Tipe Resource</dt>
                        <dd class="font-extrabold text-gray-800">{{ $alert->resource_slug ?: 'Sistem' }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2">
                        <dt class="text-gray-400">Kecamatan</dt>
                        <dd class="font-extrabold text-gray-800">{{ $alert->kecamatan?->name ?? '-' }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2">
                        <dt class="text-gray-400">Status</dt>
                        <dd class="font-extrabold text-gray-800">{{ $alert->statusLabel() }}</dd>
                    </div>
                </dl>

                @if ($alert->latitude && $alert->longitude)
                    <p class="text-[11px] text-gray-400 mt-3">📍 {{ $alert->latitude }}, {{ $alert->longitude }}</p>
                @endif
            </x-dashboard-section>

            <x-dashboard-section title="Status Penanganan" subtitle="Rangkaian: BARU → DITINJAU → DITANGANI → SELESAI" icon="🧭">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach (\App\Models\CommandAlert::statuses() as $i => $s)
                        @php
                            $idx = array_search($alert->status, \App\Models\CommandAlert::statuses(), true);
                            $state = $i < $idx || $alert->status === \App\Models\CommandAlert::STATUS_SELESAI
                                ? 'done'
                                : ($i === $idx ? 'current' : 'todo');
                            $label = config("command-center.alerts.statuses.{$s}", strtoupper($s));
                        @endphp
                        <div @class([
                            'rounded-xl p-3 text-center border-2',
                            'border-emerald-500 bg-emerald-50' => $state === 'done',
                            'border-blue-500 bg-blue-50' => $state === 'current',
                            'border-gray-200 bg-gray-50' => $state === 'todo',
                        ])>
                            <div class="text-[10px] uppercase tracking-wide font-bold {{ $state === 'done' ? 'text-emerald-700' : ($state === 'current' ? 'text-blue-700' : 'text-gray-400') }}">
                                Langkah {{ $i + 1 }}
                            </div>
                            <div class="text-[13px] font-extrabold mt-1 {{ $state === 'todo' ? 'text-gray-400' : 'text-gray-800' }}">
                                {{ $label }}
                            </div>
                        </div>
                    @endforeach
                </div>

                @can('update', $alert)
                    <div class="mt-4">
                        <p class="text-[12px] font-bold text-gray-600 mb-1.5">Perbarui status:</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (\App\Models\CommandAlert::statuses() as $s)
                                <form method="POST" action="{{ route('alerts.status', $alert) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $s }}">
                                    <button type="submit"
                                            @class([
                                                'px-3.5 py-2 rounded-lg text-[11px] font-bold transition',
                                                'bg-emerald-600 text-white shadow-sm' => $alert->status === $s,
                                                'bg-gray-100 text-gray-600 hover:bg-gray-200' => $alert->status !== $s,
                                            ])>
                                        {{ config("command-center.alerts.statuses.{$s}", strtoupper($s)) }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                        @if ($alert->status === \App\Models\CommandAlert::STATUS_SELESAI)
                            <p class="text-[11px] text-gray-400 mt-2">
                                Diselesaikan {{ $alert->resolved_at?->diffForHumans() }}{{ ! $alert->isOpen() ? ' — status final.' : '' }}
                            </p>
                        @endif
                    </div>
                @endcan
            </x-dashboard-section>
        </div>

        <div class="space-y-4">
            <x-card title="Ringkasan" icon="📋">
                <dl class="space-y-2 text-[12px]">
                    <div class="flex justify-between"><dt class="text-gray-500">Keparahan</dt><dd><x-severity-badge :severity="$alert->severity"/></dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Sektor</dt><dd class="font-bold text-gray-800">{{ $alert->sector }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd class="font-bold text-gray-800">{{ $alert->statusLabel() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Terbuka</dt><dd class="font-bold text-gray-800">{{ $alert->opened_at?->translatedFormat('d M Y, H:i') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Terakhir terdeteksi</dt><dd class="font-bold text-gray-800">{{ $alert->last_seen_at?->diffForHumans() }}</dd></div>
                </dl>
            </x-card>
        </div>
    </div>
</div>
@endsection