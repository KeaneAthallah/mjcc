{{-- Anak Tidak Sekolah (ATS) — widget bersama untuk dashboard pendidikan & halaman sumber --}}
@php($atsKpi = $ats['kpi'])
<div class="rounded-2xl border border-rose-100 bg-white overflow-hidden shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-rose-50 bg-rose-50/40 px-5 py-4">
        <div class="flex items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-rose-100 text-[22px]">🎒</span>
            <div>
                <h3 class="text-[16px] font-extrabold text-gray-900 leading-tight">Anak Tidak Sekolah (ATS)</h3>
                <p class="text-[12px] text-gray-500">{{ $ats['scope'] }} · {{ $ats['source_label'] }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-[11.5px]">
            @if ($ats['as_of'])
                <span class="rounded-full bg-white border border-rose-100 px-2.5 py-1 text-rose-600 flex items-center gap-1">🔄 Sinkron {{ \Carbon\Carbon::parse($ats['as_of'])->translatedFormat('d M Y H:i') }}</span>
            @endif
            @unless ($hideSourceLink ?? false)
                <a href="{{ route('public-data.source', 'ats') }}" target="_blank" class="rounded-full bg-white border border-rose-100 px-2.5 py-1 text-gray-600 hover:border-rose-300 transition">Sumber ↗</a>
            @endunless
        </div>
    </div>

    <div class="space-y-5 p-5">
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
            <x-stat-card label="Total ATS" value="{{ number_format((int) $atsKpi['total']) }}" icon="🎒" color="red"/>
            <x-stat-card label="DO · Putus Sekolah" value="{{ number_format((int) $atsKpi['do']) }}" icon="🚧" color="amber"/>
            <x-stat-card label="LTM · Lama Tak Sekolah" value="{{ number_format((int) $atsKpi['ltm']) }}" icon="🕰️" color="blue"/>
            <x-stat-card label="BPB · Belum Sekolah" value="{{ number_format((int) $atsKpi['bpb']) }}" icon="👶" color="violet"/>
            <x-stat-card label="Terverifikasi" value="{{ number_format((int) $atsKpi['verified']) }} ({{ $atsKpi['verified_pct'] }}%)" icon="🔍" color="green"/>
            <x-stat-card label="Kembali Sekolah" value="{{ number_format((int) $atsKpi['recovery']) }} ({{ $atsKpi['recovery_pct'] }}%)" icon="🔄" color="green"/>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <x-card title="Komposisi ATS per Jenis" icon="🧩">
                <div class="h-56 relative">
                    <canvas id="chart-ats-composition"></canvas>
                    <div class="absolute inset-x-0 bottom-2 text-center text-[12px] font-bold text-gray-500">{{ number_format((int) $atsKpi['total']) }} total</div>
                </div>
            </x-card>
            <x-card title="Progres Verifikasi" icon="🔍">
                <div class="h-56 relative">
                    <canvas id="chart-ats-verification"></canvas>
                    <div class="absolute inset-0 grid place-items-center pointer-events-none">
                        <span class="text-[26px] font-extrabold text-emerald-600">{{ $ats['verification']['pct'] }}%</span>
                    </div>
                </div>
            </x-card>
            <x-card title="Progres Pemulihan (Kembali Sekolah)" icon="🔄">
                <div class="h-56 relative">
                    <canvas id="chart-ats-recovery"></canvas>
                    <div class="absolute inset-0 grid place-items-center pointer-events-none">
                        <span class="text-[26px] font-extrabold text-rose-600">{{ $ats['recovery']['pct'] }}%</span>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <x-card title="Pemulihan DO per Jenjang" icon="📈">
                <div class="h-64"><canvas id="chart-ats-jenjang"></canvas></div>
            </x-card>
            <x-card title="Pemulihan DO per Tingkat (1–13)" icon="🎓" :padding="false">
                <div class="h-64 p-5"><canvas id="chart-ats-tingkat"></canvas></div>
            </x-card>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <x-card title="Sebaran ATS per Kecamatan" icon="🗺️" :padding="false" class="xl:col-span-2">
                <div id="ats-map" class="h-[420px]"></div>
            </x-card>
            <x-card title="Kecamatan Prioritas" icon="🏆">
                <div class="space-y-4">
                    <div>
                        <div class="text-[11px] font-extrabold uppercase tracking-wide text-gray-400 mb-2">5 Terbanyak</div>
                        @foreach ($ats['rankings']['top'] as $i => $row)
                            <div class="flex items-center gap-2 py-1">
                                <span class="w-5 text-center text-[12px] font-black text-rose-500">{{ $i + 1 }}</span>
                                <span class="text-[12px] font-semibold text-gray-700 flex-1 truncate">{{ $row['name'] }}</span>
                                <span class="text-[12px] font-bold text-gray-900">{{ number_format($row['total']) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-gray-100 pt-3">
                        <div class="text-[11px] font-extrabold uppercase tracking-wide text-gray-400 mb-2">5 Paling Sedikit</div>
                        @foreach ($ats['rankings']['bottom'] as $row)
                            <div class="flex items-center gap-2 py-1">
                                <span class="w-5 text-center text-[12px] font-black text-emerald-500">↓</span>
                                <span class="text-[12px] font-semibold text-gray-700 flex-1 truncate">{{ $row['name'] }}</span>
                                <span class="text-[12px] font-bold text-gray-900">{{ number_format($row['total']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-card>
        </div>

        <x-card title="Alasan Verifikasi (k-1 … k-25)" icon="📋" :padding="false">
            <div class="px-5 pt-3 pb-1 text-[11.5px] text-gray-500">
                Distribusi alasan atas <strong>{{ number_format($ats['reasons']['total']) }}</strong> anak yang telah diverifikasi ({{ $ats['scope'] }}).
            </div>
            <div class="h-80"><canvas id="chart-ats-reasons"></canvas></div>
        </x-card>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
            @foreach (array_slice($ats['insights'], 0, 4) as $insight)
                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="flex items-start gap-3">
                        <span class="text-lg leading-none mt-0.5">{{ $insight['icon'] }}</span>
                        <div>
                            <div class="text-[13px] font-extrabold text-gray-900">{{ $insight['title'] }}</div>
                            <div class="text-[12px] text-gray-600 leading-relaxed mt-1">{{ $insight['detail'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const C = window.Mjcc.charts;
    const M = window.Mjcc.maps;
    const ATS = @json($ats);

    if (document.getElementById('chart-ats-composition')) {
        C.makeDoughnut(document.getElementById('chart-ats-composition'), ATS.composition.labels, ATS.composition.data, ['#dc2626', '#f59e0b', '#3b82f6']);

        C.makeDoughnut(document.getElementById('chart-ats-verification'), ATS.verification.labels, ATS.verification.data, ['#059669', '#e5e7eb'], { cutout: '72%' });

        C.makeDoughnut(document.getElementById('chart-ats-recovery'), ATS.recovery.labels, ATS.recovery.data, ['#f43f5e', '#fecdd3'], { cutout: '72%' });

        C.makeBar(document.getElementById('chart-ats-jenjang'), ATS.doJenjang.labels, [
            { label: 'Pemulihan DO', data: ATS.doJenjang.data, backgroundColor: 'rgba(124,58,237,0.8)', borderColor: 'rgba(124,58,237,1)', borderWidth: 1 },
        ]);

        C.makeHorizontalBar(document.getElementById('chart-ats-tingkat'), ATS.doTingkat.labels, [
            { label: 'Pemulihan DO', data: ATS.doTingkat.data, backgroundColor: 'rgba(245,158,11,0.85)', borderColor: 'rgba(245,158,11,1)', borderWidth: 1 },
        ]);

        C.makeHorizontalBar(document.getElementById('chart-ats-reasons'), ATS.reasons.labels, [
            { label: 'Alasan', data: ATS.reasons.data, backgroundColor: 'rgba(249,115,22,0.8)', borderColor: 'rgba(249,115,22,1)', borderWidth: 1 },
        ]);

        M.createAtsBubbleMap('ats-map', ATS.map);
    }
});
</script>
@endpush