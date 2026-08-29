@extends('layouts.app')

@section('title', 'Detail SOS #'.$sos->id)

@section('content')
<div class="page-transition space-y-5" x-data="sosLiveDetail()" data-url="{{ route('sos.live-show', $sos) }}">

    <x-page-title :title="'SOS Darurat #'.$sos->id" :subtitle="'Dikirim oleh '.($sos->user?->name ?? 'Pengguna').' · '.$sos->created_at->format('d M Y H:i')">
        <x-slot:actions>
            <x-button href="{{ route('sos.index') }}" variant="outline" size="sm">← Kembali</x-button>

            <span id="sos-actions">
                @include('sos.partials._actions', ['sos' => $sos])
            </span>
        </x-slot:actions>
    </x-page-title>

    <span id="sos-status-card">
        @include('sos.partials._status_card', ['sos' => $sos])
    </span>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Timeline --}}
        <span id="sos-timeline">
            @include('sos.partials._timeline', ['sos' => $sos])
        </span>

        {{-- Detail + Map --}}
        <div class="space-y-5">
            <x-card title="Informasi SOS" icon="🆘">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3.5 text-[13px]">
                    <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Pelapor</dt><dd class="font-bold text-gray-800">{{ $sos->user?->name ?? '-' }}</dd></div>
                    <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Peran</dt><dd class="capitalize text-gray-800">{{ $sos->user?->role ?? '-' }}</dd></div>
                    <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Koordinat</dt><dd class="font-mono text-gray-800">{{ number_format((float) $sos->latitude, 6) }}, {{ number_format((float) $sos->longitude, 6) }}</dd></div>
                    <div class="flex flex-col"><dt class="text-gray-400 text-[11px]">Akurasi</dt><dd class="text-gray-800">{{ $sos->accuracy !== null ? round($sos->accuracy) . ' m' : '-' }}</dd></div>
                    <div class="flex flex-col sm:col-span-2"><dt class="text-gray-400 text-[11px]">Pesan</dt><dd class="text-gray-800">{{ $sos->message ?: '-' }}</dd></div>
                </dl>
            </x-card>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div id="sos-detail-map" class="h-[280px]"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const M = window.Mjcc.maps;
    const marker = @json($marker);
    M.createMap('sos-detail-map', [marker], {
        cluster: false,
        resize: true,
        center: [marker.latitude, marker.longitude],
        zoom: 15,
    });
});
</script>
@endpush