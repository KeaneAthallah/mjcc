{{-- Dapodik --}}
@php
    $records = $records ?? null;
    $chart = $chart ?? ['labels' => [], 'data' => []];
    $indicators = $indicators ?? collect();
    $years = $years ?? collect();
    $datasets = $datasets ?? collect();
    $locations = $locations ?? 0;
    $insights = $insights ?? [];
    $byKecamatan = $byKecamatan ?? collect();
    $topKecamatan = $topKecamatan ?? collect();
    $bottomKecamatan = $bottomKecamatan ?? collect();

    $totalSekolah = $kabupatenSchools ?? 0;
    $totalSiswa = $kabupatenStudents ?? 0;
    $totalGuru = $kabupatenTeachers ?? 0;
@endphp

<x-source-widget-header icon="🏫" title="Data Dapodik" subtitle="Data pokok pendidikan dari Kemdikbud-Ristek" key="dapodik" accent="emerald">

@include('partials.source-insights', ['insights' => $insights])

<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <x-stat-card label="Sekolah" :value="number_format($totalSekolah)" icon="🏫" color="blue"/>
    <x-stat-card label="Siswa" :value="number_format($totalSiswa)" icon="👩‍🎓" color="violet"/>
    <x-stat-card label="Guru" :value="number_format($totalGuru)" icon="👨‍🏫" color="emerald"/>
    <x-stat-card label="Kecamatan Terdata" :value="number_format($byKecamatan->count())" icon="📍" color="amber"/>
</div>

@if ($byKecamatan->isNotEmpty())
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <x-card title="Siswa per Kecamatan" subtitle="Sebaran peserta didik terdaftar" icon="👩‍🎓">
            <div class="h-64"><canvas id="chart-dapodik-students"></canvas></div>
        </x-card>
        <x-card title="Sekolah per Kecamatan" subtitle="Sebaran satuan pendidikan" icon="🏫">
            <div class="h-64"><canvas id="chart-dapodik-schools"></canvas></div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-card title="Kecamatan Terpadat" subtitle="Lima kecamatan dengan siswa terbanyak" icon="🏆">
            <div class="space-y-2">
                @foreach ($topKecamatan as $i => $k)
                    <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
                        <span class="text-[12px] text-gray-700 font-semibold">{{ $i + 1 }}. {{ $k['name'] }}</span>
                        <span class="text-[12px] font-bold text-violet-600">{{ number_format((int) $k['students']) }} siswa</span>
                    </div>
                @endforeach
            </div>
        </x-card>
        <x-card title="Kecamatan Paling Sedikit" subtitle="Store perhatian untuk pemerataan" icon="⚠️">
            <div class="space-y-2">
                @foreach ($bottomKecamatan as $i => $k)
                    <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
                        <span class="text-[12px] text-gray-700 font-semibold">{{ $i + 1 }}. {{ $k['name'] }}</span>
                        <span class="text-[12px] font-bold text-amber-600">{{ number_format((int) $k['students']) }} siswa</span>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>
@endif

<x-card title="Filter" subtitle="Saring data indikator dan tahun" icon="🔍">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <select name="indicator" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200">
            <option value="">Semua Indikator</option>
            @foreach ($indicators as $ind)
                <option value="{{ $ind }}" @selected(request('indicator') === $ind)>{{ $ind }}</option>
            @endforeach
        </select>
        <select name="year" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200">
            <option value="">Semua Tahun</option>
            @foreach ($years as $y)
                <option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 text-[12px] font-bold text-white bg-violet-600 hover:bg-violet-700 rounded-xl px-4 py-2.5 transition">Terapkan</button>
            <a href="{{ route('public-data.source', 'dapodik') }}" class="text-[12px] font-bold text-gray-500 border border-gray-200 rounded-xl px-3 py-2.5 transition">Reset</a>
        </div>
    </form>
</x-card>

<x-card title="Rekap Data" subtitle="Baris indikator terperinci per wilayah" icon="📋" :padding="false">
    @if ($records?->isEmpty())
        <x-empty-state icon="🏫" title="Belum ada data Dapodik" message="Jalankan sinkronisasi untuk menarik data pokok pendidikan."/>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-[12px]">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 uppercase tracking-wide text-[11px]">
                        <th class="px-4 py-3 font-bold">Indikator</th>
                        <th class="px-4 py-3 font-bold">Wilayah</th>
                        <th class="px-4 py-3 font-bold">Tahun</th>
                        <th class="px-4 py-3 font-bold text-right">Nilai</th>
                        <th class="px-4 py-3 font-bold">Satuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records ?? [] as $row)
                        <tr class="hover:bg-violet-50/40 transition">
                            <td class="px-4 py-2.5 text-gray-800 font-semibold max-w-[300px] truncate" title="{{ $row->indicator }}">{{ $row->indicator }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $row->location ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $row->year ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-gray-800">{{ $row->value !== null ? number_format($row->value, 0, ',', '.') : '—' }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-500">{{ $row->unit ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-[13px] text-gray-500">Tidak ada data untuk filter yang dipilih.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records)
            <x-pagination :rows="$records"/>
        @endif
    @endif
</x-card>

</x-source-widget-header>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const byKecamatan = @json($byKecamatan);
    if (byKecamatan.length > 0) {
        const labels = byKecamatan.map(k => k.name);

        const students = byKecamatan.map(k => Number(k.students || 0));
        if (document.getElementById('chart-dapodik-students')) {
            window.Mjcc.charts.makeBar(document.getElementById('chart-dapodik-students'), labels, [{
                data: students,
                label: 'Siswa',
                backgroundColor: 'rgba(139,92,246,0.8)',
                borderWidth: 1,
            }]);
        }

        const schools = byKecamatan.map(k => Number(k.schools || 0));
        if (document.getElementById('chart-dapodik-schools')) {
            window.Mjcc.charts.makeBar(document.getElementById('chart-dapodik-schools'), labels, [{
                data: schools,
                label: 'Sekolah',
                backgroundColor: 'rgba(59,130,246,0.8)',
                borderWidth: 1,
            }]);
        }
    }
});
</script>
@endpush