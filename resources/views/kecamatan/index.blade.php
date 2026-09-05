@extends('layouts.app')

@section('title', 'Intelijen Kecamatan')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Intelijen Kecamatan" subtitle="Rangking keseluruhan dari skor data nyata per kecamatan">
        <x-slot:actions>
            <a href="{{ route('kecamatan.overview', ['refresh' => 1]) }}"
               class="px-4 py-2 rounded-xl bg-emerald-100 text-emerald-800 text-[12px] font-bold hover:bg-emerald-200">🔄 Muat Ulang</a>
        </x-slot:actions>
    </x-page-title>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($rows->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-center px-3 py-3 font-bold w-10">#</th>
                            <th class="text-left px-2 py-3 font-bold">Kecamatan</th>
                            <th class="text-left px-2 py-3 font-bold">Skor & Status</th>
                            <th class="text-center px-2 py-3 font-bold">Pendidikan</th>
                            <th class="text-center px-2 py-3 font-bold">Ketertiban</th>
                            <th class="text-center px-2 py-3 font-bold">Kesehatan</th>
                            <th class="text-center px-2 py-3 font-bold">Alert</th>
                            <th class="text-right px-4 py-3 font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($rows as $row)
                            @php
                                $score = $row['kecamatan']['score'];
                                $status = $row['kecamatan']['status'];
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="text-center px-3 py-3">
                                    <span @class([
                                        'inline-flex w-7 h-7 items-center justify-center rounded-full text-[11px] font-extrabold',
                                        'bg-amber-100 text-amber-600' => $row['rank'] <= 3,
                                        'bg-gray-100 text-gray-500' => $row['rank'] > 3,
                                    ])>{{ $row['rank'] }}</span>
                                </td>
                                <td class="px-2 py-3">
                                    <a href="{{ route('kecamatan.show', $row['kecamatan']['id']) }}" class="font-bold text-gray-800 hover:text-emerald-600">{{ $row['kecamatan']['name'] }}</a>
                                    <div class="text-[11px] text-gray-400">{{ $row['kecamatan']['kelurahan'] }} kelurahan · {{ number_format($row['kecamatan']['population']) }} jiwa</div>
                                </td>
                                <td class="px-2 py-3 min-w-[170px]">
                                    @if ($score !== null)
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1">
                                                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                                    <div @class([
                                                        'h-full',
                                                        'bg-emerald-500' => $score >= 85,
                                                        'bg-amber-500' => $score >= 70 && $score < 85,
                                                        'bg-orange-500' => $score >= 50 && $score < 70,
                                                        'bg-red-500' => $score < 50,
                                                    ]) style="width: {{ min($score, 100) }}%"></div>
                                                </div>
                                            </div>
                                            <span class="text-[12px] font-extrabold tabular-nums text-gray-700">{{ number_format($score) }}</span>
                                        </div>
                                        <x-status-indicator :status="$status" :score="$score" compact label="{{ $row['status']['label'] }}"/>
                                    @else
                                        <span class="text-[11px] text-gray-400">Belum ada data</span>
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <span class="font-bold text-gray-700">{{ $row['counts']['sekolah'] }} sekolah</span>
                                    <div class="text-[11px] text-gray-400">{{ number_format($row['counts']['siswa']) }} siswa · {{ $row['counts']['guru'] }} guru</div>
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <span class="font-bold text-gray-700">{{ $row['counts']['poskamling_aktif'] }}/{{ $row['counts']['poskamling'] }} poskamling</span>
                                    <div class="text-[11px] text-gray-400">{{ $row['counts']['tipkamtikmas_aktif'] }}/{{ $row['counts']['tipkamtikmas'] }} tipkamtikmas</div>
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <span class="font-bold text-gray-700">{{ $row['counts']['faskes_aktif'] }}/{{ $row['counts']['faskes'] }} faskes</span>
                                    <div class="text-[11px] text-gray-400">{{ $row['counts']['dokter'] }} dokter · {{ $row['counts']['perawat'] }} perawat</div>
                                </td>
                                <td class="px-2 py-3 text-center">
                                    <span @class([
                                        'inline-flex px-2 py-1 rounded-full text-[11px] font-bold',
                                        'bg-red-100 text-red-700' => $row['kecamatan']['open_alerts'] > 0,
                                        'bg-emerald-100 text-emerald-700' => $row['kecamatan']['open_alerts'] === 0,
                                    ])>{{ $row['kecamatan']['open_alerts'] }}</span>
                                </td>
                                <td class="text-right px-4 py-3">
                                    <a href="{{ route('kecamatan.show', $row['kecamatan']['id']) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-[11px] font-bold hover:bg-emerald-700">
                                        Buka Profil →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state title="Belum ada kecamatan" description="Tambahkan kecamatan terlebih dahulu pada menu Data Master." />
        @endif
    </div>
</div>
@endsection