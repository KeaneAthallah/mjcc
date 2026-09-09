@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<div class="space-y-5 page-transition">

    {{-- Command header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900">Selamat Datang, {{ $currentUser->name }} 👋</h1>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-[12px] text-gray-500">
                <span>📊 Total penduduk: <strong class="text-gray-800">{{ number_format($stats['population'], 0, ',', '.') }} jiwa</strong></span>
                <span>🏙️ {{ $stats['kecamatan'] }} Kecamatan</span>
                <span>🏘️ {{ $stats['kelurahan'] }} Kelurahan/Desa</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <div class="text-right">
                <div class="text-[11px] uppercase tracking-wide font-bold text-gray-400">Data terakhir diperbarui</div>
                <div class="text-[13px] font-bold text-gray-700">
                    @if ($freshness['last_update'])
                        {{ \Illuminate\Support\Carbon::parse($freshness['last_update'])->translatedFormat('d M Y, H:i') }}
                    @else
                        Belum ada data
                    @endif
                </div>
                <x-badge color="{{ $freshness['status'] === 'terbaru' ? 'green' : ($freshness['status'] === 'perlu_diperbarui' ? 'amber' : ($freshness['status'] === 'data_lama' ? 'red' : 'gray')) }}">
                    {{ $freshness['label'] }}
                </x-badge>
            </div>
            <form method="POST" action="{{ route('dashboard.refresh') }}">
                @csrf
                <x-button type="submit" variant="outline" size="sm" title="Muat ulang data dashboard">
                    🔄 Muat Ulang
                </x-button>
            </form>

            <div x-data="dashboardAutoRefresh()"
                 data-seconds="{{ (int) config('command-center.dashboard.live_refresh_seconds', 300) }}"
                 data-default="{{ config('command-center.dashboard.live_refresh_default', true) ? '1' : '0' }}"
                 data-url="{{ route('dashboard.refresh') }}">
                <button type="button"
                        @click="toggle()"
                        class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-[12px] font-bold transition"
                        :class="enabled ? 'bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                        :title="enabled ? 'Auto-refresh aktif — data dimuat ulang otomatis' : 'Auto-refresh nonaktif'">
                    <span x-text="enabled ? '⏸' : '▶'"></span>
                    <span x-text="enabled ? 'Auto' : 'Auto Off'"></span>
                    <span x-show="enabled" class="tabular-nums font-mono opacity-90" x-text="remainingLabel"></span>
                </button>
            </div>

            <a href="{{ route('education.dashboard') }}" class="px-4 py-2 rounded-xl bg-emerald-100 text-emerald-800 text-[12px] font-bold hover:bg-emerald-200">🎓 Pendidikan</a>
            <a href="{{ route('security.dashboard') }}" class="px-4 py-2 rounded-xl bg-blue-100 text-blue-800 text-[12px] font-bold hover:bg-blue-200">🛡️ Ketertiban</a>
            <a href="{{ route('health.dashboard') }}" class="px-4 py-2 rounded-xl bg-red-100 text-red-800 text-[12px] font-bold hover:bg-red-200">🏥 Kesehatan</a>
        </div>
    </div>

    {{-- STATUS MOROWALI --}}
    <x-dashboard-section title="Status Morowali" subtitle="Skor 0–100 dari agregasi data nyata" icon="🛰️" :pad="false">
        <div class="p-5">
            <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                <div class="flex items-center gap-5 lg:w-72 shrink-0 rounded-2xl {{ $status['no_data'] ? 'bg-gray-50' : 'bg-gray-900' }} p-5">
                    <div class="flex-1">
                        <div class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">{{ config('command-center.overall.label') }}</div>
                        <x-status-indicator :status="$status['status']" :score="$status['score']" class="mt-2" label="{{ $status['label'] }}"/>
                        @if (! $status['no_data'])
                            <p class="text-[11px] text-gray-300 mt-2">Berdasarkan {{ collect($status['sectors'])->filter(fn ($s) => $s !== 'tidak_ada_data')->count() }} sektor dengan data.</p>
                        @else
                            <p class="text-[11px] text-gray-400 mt-2">Belum ada cukup data untuk menilai.</p>
                        @endif
                    </div>
                </div>

                <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach ($status['sector_details'] as $sector)
                        <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[12px] font-bold text-gray-700">{{ $sector['label'] }}</span>
                                <x-status-indicator :status="$sector['status']" :score="$sector['score']" compact label="{{ $sector['score'] !== null ? number_format($sector['score']) : '—' }}"/>
                            </div>
                            <div class="space-y-1.5">
                                @forelse ($sector['rules'] as $rule)
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1">
                                            <div class="flex justify-between text-[10px] text-gray-500">
                                                <span class="truncate pr-2">{{ $rule['label'] }}</span>
                                                <span>{{ $rule['score'] !== null ? number_format($rule['score']).'%' : 'tdk ada data' }}</span>
                                            </div>
                                            <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden mt-0.5">
                                                <div class="h-full {{ $rule['score'] !== null && $rule['score'] >= 85 ? 'bg-emerald-500' : ($rule['score'] !== null && $rule['score'] >= 70 ? 'bg-amber-500' : ($rule['score'] !== null && $rule['score'] >= 50 ? 'bg-orange-500' : 'bg-red-500')) }}"
                                                     style="width: {{ min($rule['score'] ?? 0, 100) }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-[11px] text-gray-400">Tidak ada data sektor.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-dashboard-section>

    {{-- Executive KPI --}}
    <x-kpi-grid cols="6" label="Indikator Kunci Utama">
        <x-metric-comparison label="Total Sekolah" value="{{ number_format($stats['total_sekolah']) }}" icon="🏫" color="green" footer="SD {{ number_format($stats['total_sd']) }} · SMP {{ number_format($stats['total_smp']) }} · 🏗️ Baik {{ number_format($stats['sekolah_baik']) }}"/>
        <x-metric-comparison label="Total Siswa" value="{{ number_format($stats['total_siswa']) }}" icon="🎓" color="blue" footer="Guru: {{ number_format($stats['total_guru']) }}"/>
        <x-metric-comparison label="Fasilitas Kesehatan" value="{{ number_format($stats['total_faskes']) }}" icon="🏥" color="green" footer="Aktif {{ number_format($stats['faskes_aktif']) }}" />
        <x-metric-comparison label="Tenaga Kesehatan" value="{{ number_format($stats['total_dokter'] + $stats['total_perawat'] + $stats['total_bidan']) }}" icon="🩺" color="blue" footer="Dokter {{ number_format($stats['total_dokter']) }} · Perawat {{ number_format($stats['total_perawat']) }} · Bidan {{ number_format($stats['total_bidan']) }}"/>
        <x-metric-comparison label="Total Poskamling" value="{{ number_format($stats['total_poskamling']) }}" icon="🛡️" color="{{ $stats['poskamling_aktif'] < $stats['total_poskamling'] ? 'red' : 'green' }}" footer="Aktif {{ number_format($stats['poskamling_aktif']) }} · ⚠ Nonaktif {{ number_format($stats['total_poskamling'] - $stats['poskamling_aktif']) }}"/>
        <x-metric-comparison label="Total Polsek" value="{{ number_format($stats['total_polsek']) }}" icon="🚓" color="amber"/>
        <x-metric-comparison label="Kecamatan" value="{{ number_format($stats['kecamatan']) }}" icon="🏙️" color="emerald" footer=""/>
        <x-metric-comparison label="Kelurahan / Desa" value="{{ number_format($stats['kelurahan']) }}" icon="🏘️" color="teal"/>
        <x-metric-comparison label="Pasar" value="{{ number_format($stats['total_pasar']) }}" icon="🏪" color="violet"/>
    </x-kpi-grid>

    {{-- Data Publik (Satu Data Morowali) --}}
    @php
        $publicThemes = [
            'pendidikan' => ['icon' => '🎓', 'chip' => 'bg-emerald-100 text-emerald-600', 'hover' => 'hover:border-emerald-200', 'link' => 'group-hover:text-emerald-700'],
            'kesehatan' => ['icon' => '🏥', 'chip' => 'bg-red-100 text-red-600', 'hover' => 'hover:border-red-200', 'link' => 'group-hover:text-red-700'],
            'keamanan' => ['icon' => '🛡️', 'chip' => 'bg-blue-100 text-blue-600', 'hover' => 'hover:border-blue-200', 'link' => 'group-hover:text-blue-700'],
        ];
    @endphp
    <x-dashboard-section title="Data Publik" icon="📊"
                         subtitle="Statistik terbuka dihimpun otomatis dari portal Satu Data Morowali"
                         :pad="false">
        <x-slot:actions>
            <div class="flex items-center gap-3">
                @can('create', \App\Models\School::class)
                    <form method="POST" action="{{ route('data-import.run') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 text-[12px] font-bold text-white bg-violet-600 hover:bg-violet-700 rounded-xl px-3 py-2 transition">
                            ⇄ Sinkronkan Sekarang
                        </button>
                    </form>
                @endcan
                <a href="{{ route('public-data.index') }}"
                   class="text-[12px] font-bold text-violet-700 hover:text-violet-900 hover:underline">
                    Lihat Data Publik →
                </a>
            </div>
        </x-slot:actions>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4">
            <x-stat-card label="Total Rekaman" :value="number_format($publicData['records'])" icon="🗃️" color="emerald"/>
            <x-stat-card label="Total Dataset" :value="number_format($publicData['datasets'])" icon="📚" color="violet"/>
            <x-stat-card label="Jadwal Sinkron" value="{{ config('public_data.schedule', '03:00') }} WITA" icon="⏰" color="amber"/>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 px-4 pb-4">
            @foreach ($publicData['sectors'] as $key => $sector)
                @php
                    $theme = $publicThemes[$key] ?? $publicThemes['pendidikan'];
                @endphp
                <a href="{{ route('public-data.show', $key) }}"
                   class="group rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition hover:shadow-md {{ $theme['hover'] }}">
                    <div class="flex items-center justify-between">
                        <span class="w-10 h-10 rounded-xl {{ $theme['chip'] }} flex items-center justify-center text-lg">{{ $theme['icon'] }}</span>
                        <x-badge :color="$sector['last_success_at'] ? 'green' : 'gray'">
                            {{ $sector['last_success_at'] ? 'Tersinkron' : 'Belum pernah' }}
                        </x-badge>
                    </div>
                    <div class="mt-3 text-[12.5px] font-bold text-gray-700 {{ $theme['link'] }}">{{ $sector['label'] }}</div>
                    <div class="mt-0.5 text-[26px] font-extrabold text-gray-900 leading-none tabular-nums">{{ number_format($sector['records']) }}</div>
                    <div class="mt-2 flex items-center justify-between text-[11px] text-gray-400">
                        <span>{{ number_format($sector['datasets']) }} dataset</span>
                        <span class="flex items-center gap-1">
                            @if ($sector['last_success_at'])
                                {{ \Illuminate\Support\Carbon::parse($sector['last_success_at'])->translatedFormat('d M Y') }}
                            @else
                                belum pernah
                            @endif
                            <span class="transform transition group-hover:translate-x-0.5">→</span>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </x-dashboard-section>

    {{-- Perlu Perhatian --}}
    <x-dashboard-section title="Perlu Perhatian" icon="⚠️"
                         subtitle="Masalah terdeteksi dari data nyata — urut sesuai tingkat keparahan"
                         :pad="false">
        <x-slot:actions>
            <x-alert-summary-badges :counts="$alertCounts" :link="route('alerts.index')"/>
        </x-slot:actions>
        @if ($openAlerts->isNotEmpty())
            <div class="divide-y divide-gray-50">
                @foreach ($openAlerts as $alert)
                    <div class="p-3">
                        <x-command-alert :alert="$alert" :transition="false"/>
                    </div>
                @endforeach
            </div>
            <div class="px-5 py-3 border-t border-gray-100 text-right">
                <a href="{{ route('alerts.index') }}" class="text-[12px] font-bold text-emerald-700 hover:text-emerald-900 hover:underline">
                    Lihat semua alert →
                </a>
            </div>
        @else
            <div class="p-5 text-center">
                <div class="text-4xl mb-2">✅</div>
                <h4 class="text-[14px] font-extrabold text-gray-700">Semua aman</h4>
                <p class="text-[12px] text-gray-400 mt-1">Tidak ada masalah yang memerlukan perhatian saat ini.</p>
            </div>
        @endif
    </x-dashboard-section>

    {{-- Charts row 1 --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <x-chart-card title="Perbandingan Sektor per Kecamatan" subtitle="Sekolah · Tipkamtikmas · Faskes" icon="📊" class="lg:col-span-2">
            <div class="h-72"><canvas id="chart-comparison"></canvas></div>
        </x-chart-card>
        <x-chart-card title="Komposisi Infrastruktur" subtitle="Pendidikan · Ketertiban · Kesehatan" icon="🧮">
            <div class="h-72"><canvas id="chart-infra"></canvas></div>
        </x-chart-card>
    </div>

    {{-- Charts row 2 --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <x-chart-card title="Jumlah Siswa per Kecamatan" subtitle="Laki-laki & Perempuan" icon="🎓" class="lg:col-span-2">
            <div class="h-72"><canvas id="chart-students"></canvas></div>
        </x-chart-card>
        <x-chart-card title="Tenaga Kesehatan" subtitle="Dokter · Perawat · Bidan" icon="🩺">
            <div class="h-72"><canvas id="chart-workforce"></canvas></div>
        </x-chart-card>
    </div>

    {{-- Rankings --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <x-card title="Top Kecamatan · Sekolah" icon="🏆">
            <ol class="space-y-2.5">
                @foreach ($topSekolah as $index => $row)
                    <li class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full {{ $index === 0 ? 'bg-amber-100 text-amber-600' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center text-[11px] font-bold">{{ $index + 1 }}</span>
                        <span class="text-[13px] font-semibold text-gray-700 flex-1">{{ $row->name }}</span>
                        <x-badge color="{{ $index === 0 ? 'amber' : 'green' }}">{{ $row->schools_count }}</x-badge>
                    </li>
                @endforeach
            </ol>
        </x-card>
        <x-card title="Top Kecamatan · Poskamling" icon="🛡️">
            <ol class="space-y-2.5">
                @foreach ($topPoskamling as $index => $row)
                    <li class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full {{ $index === 0 ? 'bg-amber-100 text-amber-600' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center text-[11px] font-bold">{{ $index + 1 }}</span>
                        <span class="text-[13px] font-semibold text-gray-700 flex-1">{{ $row->name }}</span>
                        <x-badge color="blue">{{ $row->active_poskamling }}</x-badge>
                    </li>
                @endforeach
            </ol>
        </x-card>
        <x-card title="Top Kecamatan · Tenaga Medis" icon="👨‍⚕️">
            <ol class="space-y-2.5">
                @foreach ($topKesehatan as $index => $row)
                    <li class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full {{ $index === 0 ? 'bg-amber-100 text-amber-600' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center text-[11px] font-bold">{{ $index + 1 }}</span>
                        <span class="text-[13px] font-semibold text-gray-700 flex-1">{{ $row->name }}</span>
                        <x-badge color="teal">{{ $row->workers }}</x-badge>
                    </li>
                @endforeach
            </ol>
        </x-card>
    </div>

    {{-- Map --}}
    <x-card title="Persebaran Aset Morowali" icon="🗺️" :padding="false">
        <div class="flex gap-1 p-3 border-b border-gray-100 flex-wrap">
            @php
                $tabs = [
                    'sekolah' => ['label' => '🎓 Sekolah', 'active' => true],
                    'ketertiban' => ['label' => '🛡️ Ketertiban', 'active' => false],
                    'kesehatan' => ['label' => '🏥 Kesehatan', 'active' => false],
                ];
            @endphp
            @foreach ($tabs as $key => $tab)
                <button data-map-tab="{{ $key }}"
                        class="px-4 py-2 rounded-xl text-[12px] font-bold {{ $tab['active'] ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>
        <div id="map-tab-sekolah" class="map-tab h-[440px]"></div>
        <div id="map-tab-ketertiban" class="map-tab h-[440px] hidden"></div>
        <div id="map-tab-kesehatan" class="map-tab h-[440px] hidden"></div>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const C = window.Mjcc.charts;
    const M = window.Mjcc.maps;
    const data = @json($dashboardData);

    C.makeBar(document.getElementById('chart-comparison'), data.comparison.labels, data.comparison.datasets);

    C.makePolar(document.getElementById('chart-infra'), data.infra.labels, data.infra.data, data.palette.slice(0, 3));

    C.makeStackedBar(document.getElementById('chart-students'), data.students.labels, data.students.datasets);

    C.makeDoughnut(document.getElementById('chart-workforce'), data.workforce.labels, data.workforce.data, ['#2563eb', '#10b981', '#a7f3d0']);

    // Map tabs
    const mapData = {
        sekolah: @json($schoolMap),
        ketertiban: @json($securityMap),
        kesehatan: @json($healthMap),
    };
    const maps = {};

    function initMap(key) {
        if (maps[key]) { maps[key].invalidateSize(); return; }
        maps[key] = M.createMap('map-tab-' + key, mapData[key], {
            resize: true,
            cluster: true,
            center: [-3.25, 121.85],
        });
    }

    document.querySelectorAll('[data-map-tab]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.mapTab;
            document.querySelectorAll('[data-map-tab]').forEach(b => {
                b.classList.toggle('bg-emerald-600', b === btn);
                b.classList.toggle('text-white', b === btn);
                b.classList.remove('text-gray-600');
            });
            document.querySelectorAll('.map-tab').forEach(el => el.classList.add('hidden'));
            document.getElementById('map-tab-' + key).classList.remove('hidden');
            initMap(key);
        });
    });

    initMap('sekolah');
});
</script>
@endpush