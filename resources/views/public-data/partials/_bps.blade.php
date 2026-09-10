{{-- BPS --}}
@php
    $observations = $observations ?? null;
    $datasets = $datasets ?? collect();
    $years = $years ?? collect();
@endphp

<div class="grid grid-cols-2 md:grid-cols-3 gap-3">
    <x-stat-card label="Total Observasi" :value="number_format($observations?->total() ?? 0)" icon="📊" color="blue"/>
    <x-stat-card label="Dataset" :value="number_format($datasets->count())" icon="📦" color="violet"/>
    <x-stat-card label="Tahun Tersedia" :value="$years->count()" icon="📅" color="amber"/>
</div>

<x-card title="Filter" subtitle="Pilih dataset, indikator, dan tahun" icon="🔍">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <select name="dataset_id" class="rounded-xl border border-gray-300 text-[12px] px-3 py-2.5 bg-white focus:ring-2 focus:ring-violet-200">
            <option value="">Semua Dataset</option>
            @foreach ($datasets as $d)
                <option value="{{ $d->id }}" @selected(request('dataset_id') == $d->id)>{{ $d->name }}</option>
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
            <a href="{{ route('public-data.source', 'bps') }}" class="text-[12px] font-bold text-gray-500 border border-gray-200 rounded-xl px-3 py-2.5 transition">Reset</a>
        </div>
    </form>
</x-card>

<x-card title="Data BPS" subtitle="Observasi statistik dari Badan Pusat Statistik" icon="📋" :padding="false">
    @if ($observations?->isEmpty())
        <x-empty-state icon="📊" title="Belum ada data BPS" message="Konfigurasikan BPS_APP_ID di .env, lalu jalankan sinkronisasi."/>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-[12px]">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 uppercase tracking-wide text-[11px]">
                        <th class="px-4 py-3 font-bold">Indikator</th>
                        <th class="px-4 py-3 font-bold">Wilayah</th>
                        <th class="px-4 py-3 font-bold">Tahun</th>
                        <th class="px-4 py-3 font-bold">Periode</th>
                        <th class="px-4 py-3 font-bold text-right">Nilai</th>
                        <th class="px-4 py-3 font-bold text-right">Satuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($observations ?? [] as $obs)
                        <tr class="hover:bg-violet-50/40 transition">
                            <td class="px-4 py-2.5 text-gray-800 font-semibold max-w-[300px] truncate" title="{{ $obs->indicator }}">{{ $obs->indicator }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $obs->region_name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $obs->year }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $obs->period ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-gray-800">{{ $obs->value !== null ? number_format($obs->value, 2, ',', '.') : '—' }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-500">{{ $obs->unit ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-[13px] text-gray-500">Tidak ada data untuk filter yang dipilih.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($observations)
            <x-pagination :rows="$observations"/>
        @endif
    @endif
</x-card>