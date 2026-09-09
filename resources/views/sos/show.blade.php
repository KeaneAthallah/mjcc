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
                <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-lg">🚓</span>
                    <div>
                        <h3 class="font-extrabold text-gray-900 text-[14px]">Lokasi Sender &amp; Petugas</h3>
                        <p class="text-[11px] text-gray-400">🆘 lokasi pelapor · 🚓 posisi petugas terbaru · garis biru = rute terdekat</p>
                    </div>
                </div>
                <div class="relative">
                    <div id="sos-detail-map" class="h-[280px]"></div>
                    <div id="sos-route-info" class="hidden absolute top-2 right-2 z-[1000] rounded-lg bg-white/95 border border-blue-200 px-3 py-1.5 text-[11px] font-bold text-blue-800 shadow-sm"></div>
                </div>
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
    const responders = @json($responders);
    const map = M.createMap('sos-detail-map', [marker], {
        cluster: false,
        resize: true,
        center: [marker.latitude, marker.longitude],
        zoom: 15,
    });

    window.sosDetailMap = map;
    window.sosDetailSender = marker;
    M.renderResponders(map, marker, responders, {
        onRoute: ({ distanceKm, etaMin }) => {
            const info = document.getElementById('sos-route-info');
            if (info) {
                info.textContent = `🚓 Rute terdekat ${distanceKm} km · ±${etaMin} mnt`;
                info.classList.remove('hidden');
            }
        },
    });

    const respondersWithCoords = responders
        .filter((r) => Number.isFinite(Number(r.latitude)) && Number.isFinite(Number(r.longitude)))
        .map((r) => [Number(r.latitude), Number(r.longitude)]);
    if (respondersWithCoords.length) {
        map.fitBounds([[marker.latitude, marker.longitude], ...respondersWithCoords], {
            padding: [40, 40],
            maxZoom: 14,
        });
    }
});
</script>
@endpush