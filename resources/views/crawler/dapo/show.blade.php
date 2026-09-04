@extends('layouts.app')

@section('title', $record->name ?? 'Detail Sekolah DAPO')

@section('content')
    <div class="page-transition space-y-4">

        <x-page-title title="{{ $record->name ?? $record->external_id }}" subtitle="Detail sekolah DAPO Kemendikdasmen">
            <x-slot:actions>
                <x-button href="{{ route('crawler.dapo') }}" variant="ghost" size="sm">← Daftar Sekolah</x-button>
                <x-badge color="violet">DATA EKSTERNAL</x-badge>
            </x-slot:actions>
        </x-page-title>

        @php
            $data = $record->data ?? [];
            $sourceLink = $record->source_url
                ? new \Illuminate\Support\HtmlString(
                    '<a class="text-violet-600 font-bold hover:underline" target="_blank" href="' .
                        e($record->source_url) .
                        '" rel="noopener">Buka ↗</a>',
                )
                : '-';
        @endphp

        @if ($record->latitude && $record->longitude)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">🗺️ Lokasi</div>
                <div id="school-map" class="h-80"></div>
                <div class="px-5 py-3 text-[12px] text-gray-600 flex items-center gap-3 flex-wrap">
                    <span class="font-mono">{{ $record->latitude }}, {{ $record->longitude }}</span>
                    <a href="{{ route('maps.index') }}" class="text-violet-600 font-bold hover:underline">Lihat di Peta
                        Gabungan →</a>
                </div>
            </div>
        @endif

        {{-- Tabs --}}
        <div x-data="{ tab: 'umum' }" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex flex-wrap gap-1 px-3 py-2 border-b border-gray-100 bg-gray-50 tabs">
                <button @click="tab='umum'"
                    :class="tab === 'umum' ? 'bg-white text-violet-700 border-violet-300 shadow-sm' :
                        'text-gray-500 hover:bg-white'"
                    class="px-3 py-1.5 text-[12px] font-bold rounded-lg border border-transparent">Data Umum</button>
                <button @click="tab='murid'"
                    :class="tab === 'murid' ? 'bg-white text-violet-700 border-violet-300 shadow-sm' :
                        'text-gray-500 hover:bg-white'"
                    class="px-3 py-1.5 text-[12px] font-bold rounded-lg border border-transparent">Peserta Didik</button>
                <button @click="tab='guru'"
                    :class="tab === 'guru' ? 'bg-white text-violet-700 border-violet-300 shadow-sm' :
                        'text-gray-500 hover:bg-white'"
                    class="px-3 py-1.5 text-[12px] font-bold rounded-lg border border-transparent">Guru</button>
                <button @click="tab='rombel'"
                    :class="tab === 'rombel' ? 'bg-white text-violet-700 border-violet-300 shadow-sm' :
                        'text-gray-500 hover:bg-white'"
                    class="px-3 py-1.5 text-[12px] font-bold rounded-lg border border-transparent">Rombel</button>
                <button @click="tab='sarana'"
                    :class="tab === 'sarana' ? 'bg-white text-violet-700 border-violet-300 shadow-sm' :
                        'text-gray-500 hover:bg-white'"
                    class="px-3 py-1.5 text-[12px] font-bold rounded-lg border border-transparent">Sarana/Prasarana</button>
                <button @click="tab='sumber'"
                    :class="tab === 'sumber' ? 'bg-white text-violet-700 border-violet-300 shadow-sm' :
                        'text-gray-500 hover:bg-white'"
                    class="px-3 py-1.5 text-[12px] font-bold rounded-lg border border-transparent">Data Sumber</button>
            </div>

            <div class="p-5">
                {{-- Umum --}}
                <div x-show="tab==='umum'" x-cloak>
                    <x-info-grid :rows="[
                        ['Nama', $record->name ?? '-'],
                        ['External ID', $record->external_id],
                        ['Jenjang', data_get($data, 'jenjang') ?? ($record->record_type ?? '-')],
                        ['Status', data_get($data, 'status') ?? '-'],
                        ['NPSN', data_get($data, 'npsn') ?? '-'],
                        ['Provinsi', 'Sulawesi Tengah'],
                        ['Kabupaten', $record->kabupaten_name ?? ($record->kabupaten_code ?? '-')],
                        ['Kecamatan', $record->kecamatan_name ?? ' -'],
                        ['Desa/Kelurahan', $record->desa_name ?? '-'],
                    ]" />
                </div>

                {{-- Murid --}}
                <div x-show="tab==='murid'" x-cloak>
                    <x-empty-state icon="👦" title="Belum ada data peserta didik"
                        description="Field peserta didik akan tampil jika tersedia dari sumber." />
                </div>

                {{-- Guru --}}
                <div x-show="tab==='guru'" x-cloak>
                    <x-empty-state icon="👩‍🏫" title="Belum ada data guru"
                        description="Field guru akan tampil jika tersedia dari sumber." />
                </div>

                {{-- Rombel --}}
                <div x-show="tab==='rombel'" x-cloak>
                    <x-empty-state icon="📚" title="Belum ada data rombel"
                        description="Field rombongan belajar akan tampil jika tersedia dari sumber." />
                </div>

                {{-- Sarana --}}
                <div x-show="tab==='sarana'" x-cloak>
                    <x-empty-state icon="🏗️" title="Belum ada data sarana"
                        description="Field sarana/prasarana akan tampil jika tersedia dari sumber." />
                </div>

                {{-- Sumber --}}
                <div x-show="tab==='sumber'" x-cloak>
                    <x-info-grid :rows="[
                        ['Sumber', $meta['source_label']],
                        ['URL Sumber', $sourceLink],
                        ['Pertama Dilihat', $record->first_seen_at?->format('d M Y H:i') ?? '-'],
                        ['Terakhir Dilihat', $record->last_seen_at?->format('d M Y H:i') ?? '-'],
                        ['Content Hash', substr((string) $record->content_hash, 0, 16).'…'],
                    ]" />
                </div>
            </div>
        </div>

        {{-- Raw JSON --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-extrabold text-gray-900 text-[14px]">Raw Data (JSON)</div>
            <div class="p-4">
                <pre class="text-[11px] leading-relaxed bg-gray-900 text-emerald-100 rounded-xl p-4 overflow-x-auto max-h-90">{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            @if ($record->latitude && $record->longitude)
                window.Mjcc.maps.createMap('school-map', [{
                    name: @json($record->name ?? $record->external_id),
                    latitude: {{ (float) $record->latitude }},
                    longitude: {{ (float) $record->longitude }},
                    sector: 'eksternal',
                    category: 'ext-dapo',
                    details: {
                        Sumber: 'DAPO'
                    },
                }], {
                    cluster: false,
                    resize: true
                });
            @endif
        });
    </script>
@endpush
