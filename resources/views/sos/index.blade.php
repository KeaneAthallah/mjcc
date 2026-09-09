@extends('layouts.app')

@section('title', 'SOS Darurat')

@section('content')
<div class="page-transition space-y-5">

    <x-page-title title="SOS Darurat" subtitle="Pusat tanggap darurat warga — pantau dan tangani permintaan bantuan secara real-time">
        <x-slot:actions>
            <x-button href="{{ url()->current() }}" variant="blue" size="sm">↻ Muat Ulang</x-button>
        </x-slot:actions>
    </x-page-title>

    {{-- Stat cards --}}
    <div id="sos-stats" class="grid grid-cols-2 md:grid-cols-4 gap-3" x-data="sosLiveIndex()" data-url="{{ route('sos.live') }}">
        <x-stat-card label="SOS Aktif" value="{{ $counts['active'] }}" icon="🚨" color="red" icon-bg="bg-red-100 text-red-600" value-id="sos-stat-active"/>
        <x-stat-card label="Diterima" value="{{ $counts['acknowledged'] }}" icon="📥" color="amber" icon-bg="bg-amber-100 text-amber-600" value-id="sos-stat-acknowledged"/>
        <x-stat-card label="Menuju Lokasi" value="{{ $counts['responding'] }}" icon="🚓" color="blue" icon-bg="bg-blue-100 text-blue-600" value-id="sos-stat-responding"/>
        <x-stat-card label="Total Terbuka" value="{{ $counts['open'] }}" icon="⚠️" color="red" icon-bg="bg-red-100 text-red-600" value-id="sos-stat-open"/>
    </div>

    {{-- Filters --}}
    <x-filter-bar search="{{ request('search') }}" search-placeholder="Cari nama pelapor...">
        <select name="status"
                class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            <option value="">Semua Status</option>
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}"
               class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500" title="Dari tanggal">
        <input type="date" name="to" value="{{ request('to') }}"
               class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500" title="Sampai tanggal">
    </x-filter-bar>

    {{-- Map of open alerts --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-red-100 text-red-600 flex items-center justify-center text-lg">🗺️</span>
            <div>
                <h3 class="font-extrabold text-gray-900 text-[14px]">Lokasi SOS Terbuka</h3>
                <p class="text-[11px] text-gray-400">Marker merah 🆘 = permintaan · marker biru 🚓 = posisi petugas terbaru</p>
            </div>
        </div>
        <div id="sos-map" class="h-[340px]"></div>
        @if ($markers->isEmpty())
            <div class="px-5 py-3 text-[12px] text-gray-500 border-t border-gray-100">Tidak ada SOS terbuka pada peta.</div>
        @endif
    </div>

    {{-- List --}}
    <div id="sos-list" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @include('sos.partials._list', [
            'alerts' => $alerts,
            'statusLabels' => $statusLabels,
            'statusColors' => $statusColors,
        ])
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const M = window.Mjcc.maps;
    const markers = @json($markers);
    window.sosIndexMap = M.createMap('sos-map', markers, {
        cluster: false,
        resize: true,
        center: [-3.25, 121.85],
        zoom: 9,
    });
});
</script>
@endpush