@extends('layouts.app')

@section('title', 'Peta Gabungan')

@section('content')
<div class="space-y-5 page-transition">

    <x-page-title title="Peta Gabungan" subtitle="Persebaran seluruh aset Morowali Juara dalam satu peta">
        <x-slot:actions>
            <a href="{{ route('dashboard') }}" class="text-[12px] font-bold text-gray-500 hover:text-gray-700">← Kembali</a>
        </x-slot:actions>
    </x-page-title>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">
        {{-- Filter / legend panel --}}
        <div class="space-y-4">
            <x-card title="Filter" icon="🔍">
                <div class="space-y-4">
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Sektor</label>
                        <div class="flex flex-wrap gap-2">
                            @php
                                $sectors = [
                                    'all' => ['label' => 'Semua', 'color' => '#64748b'],
                                    'pendidikan' => ['label' => '🎓 Pendidikan', 'color' => '#10b981'],
                                    'ketertiban' => ['label' => '🛡️ Ketertiban', 'color' => '#2563eb'],
                                    'kesehatan' => ['label' => '🏥 Kesehatan', 'color' => '#dc2626'],
                                ];
                            @endphp
                            @foreach ($sectors as $key => $s)
                                <button data-sector="{{ $key }}"
                                        class="sector-btn text-[11px] font-bold px-3 py-1.5 rounded-lg transition {{ $key === 'all' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                    {{ $s['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Kecamatan</label>
                        <select id="kecamatan-filter"
                                class="w-full rounded-xl border border-gray-300 text-[13px] px-3 py-2 bg-white focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
                            <option value="">Semua Kecamatan</option>
                            @foreach ($kecamatans as $k)
                                <option value="{{ $k['name'] }}">{{ $k['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-card>

            <x-card title="Legenda" icon="🏷️">
                <div id="map-legend" class="space-y-1.5">
                    <span class="text-[12px] text-gray-500">Klik sektor untuk melihat legenda</span>
                </div>
            </x-card>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="text-[12px] text-gray-600 leading-relaxed">
                    <strong class="text-gray-800">Total marker:</strong> <span id="marker-count" class="font-bold text-emerald-600">0</span> titik
                    <br>Klik marker untuk melihat detail.
                </div>
            </div>
        </div>

        {{-- Map --}}
        <div class="lg:col-span-3">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div id="combined-map" class="h-[640px]"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const M = window.Mjcc.maps;
    const allMarkers = @json($markers);

    let activeSector = 'all';
    let activeKecamatan = '';
    let map = null;
    const focus = new URLSearchParams(location.search).get('focus');

    const colorBySector = {
        pendidikan: '#10b981',
        ketertiban: '#2563eb',
        kesehatan: '#dc2626',
    };

    function focusTarget(markers) {
        return M.findFocus(markers, focus);
    }

    function openFocusPopup(markers) {
        const target = focusTarget(markers);
        if (!target || !map) {
            return;
        }
        map.once('zoomend', () => {
            setTimeout(() => {
                map.eachLayer((layer) => {
                    if (layer instanceof L.Marker) {
                        const ll = layer.getLatLng();
                        if (Math.round(ll.lat * 10000) === Math.round(Number(target.latitude) * 10000)
                            && Math.round(ll.lng * 10000) === Math.round(Number(target.longitude) * 10000)) {
                            layer.openPopup();
                        }
                    }
                });
            }, 200);
        });
    }

    function filteredMarkers() {
        return allMarkers.filter((m) => {
            const sectorOk = activeSector === 'all' || m.sector === activeSector;
            const kecOk = !activeKecamatan || m.kecamatan === activeKecamatan;
            return sectorOk && kecOk;
        });
    }

    function activeCategories() {
        const markers = filteredMarkers();
        const cats = [...new Set(markers.map((m) => m.category))];
        return cats;
    }

    function renderLegend() {
        const el = document.getElementById('map-legend');
        const cats = activeCategories();
        if (!cats.length) {
            el.innerHTML = '<span class="text-[12px] text-gray-500">Tidak ada marker</span>';
            return;
        }
        el.innerHTML = cats.map((cat) => {
            const cfg = M.categoryConfig[cat] ?? { color: '#64748b', label: cat };
            const count = filteredMarkers().filter((m) => m.category === cat).length;
            const sectorColor = colorBySector[filteredMarkers().find((m) => m.category === cat)?.sector] ?? cfg.color;
            return `<div class="flex items-center justify-between py-1 border-b border-gray-50"><span class="inline-flex items-center text-[12px] font-semibold text-gray-700"><span class="w-3 h-3 rounded-full mr-2" style="background:${cfg.color}"></span>${cfg.label}</span><span class="text-[11px] text-gray-400">${count}</span></div>`;
        }).join('');
    }

    function renderMap() {
        const markers = filteredMarkers();
        const target = focusTarget(markers);
        if (!map) {
            map = M.createMap('combined-map', markers, {
                resize: true,
                cluster: !target,
                fitBounds: !target,
                center: target ? [Number(target.latitude), Number(target.longitude)] : [-3.25, 121.85],
                zoom: target ? 14 : 9,
            });
        } else {
            M.renderMarkers(map, markers, { cluster: !focusTarget(markers) });
        }
        document.getElementById('marker-count').textContent = markers.length;
        renderLegend();
        openFocusPopup(markers);
    }

    document.querySelectorAll('[data-sector]').forEach((btn) => {
        btn.addEventListener('click', () => {
            activeSector = btn.dataset.sector;
            document.querySelectorAll('[data-sector]').forEach((b) => {
                const on = b === btn;
                b.classList.toggle('bg-gray-800', on);
                b.classList.toggle('text-white', on);
                b.classList.toggle('bg-gray-100', !on);
                b.classList.toggle('text-gray-600', !on);
            });
            renderMap();
        });
    });

    document.getElementById('kecamatan-filter').addEventListener('change', (e) => {
        activeKecamatan = e.target.value;
        renderMap();
    });

    renderMap();
});
</script>
@endpush
