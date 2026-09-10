@extends('layouts.app')

@section('title', 'Data Publik')

@section('content')
<div class="page-transition space-y-5">

    <x-page-title title="Data Publik" subtitle="Pemantauan data sektoral dan informasi publik Kabupaten Morowali">
        <x-slot:actions>
            @can('create', \App\Models\School::class)
                <form method="POST" action="{{ route('public-data.sync-all') }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 text-[12px] font-bold text-white bg-violet-600 hover:bg-violet-700 rounded-xl px-3 py-2 transition">
                        🔄 Sinkronkan Semua
                    </button>
                </form>
            @endcan
        </x-slot:actions>
    </x-page-title>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <x-stat-card label="Sumber Data" :value="number_format($totalSources)" icon="📡" color="violet"/>
        <x-stat-card label="Sumber Aktif" :value="number_format($activeSources)" icon="✅" color="emerald"/>
        <x-stat-card label="Total Record" :value="number_format($totalRecords)" icon="🗃️" color="blue"/>
        <x-stat-card label="Terakhir Diperbarui" :value="$latestSync ? \Illuminate\Support\Carbon::parse($latestSync)->translatedFormat('d M Y H:i') : '—'" icon="🔄" color="amber"/>
    </div>

    {{-- Kategori --}}
    @foreach ($categories as $key => $cat)
        <x-dashboard-section :title="$cat['label']" :subtitle="$cat['description']" :icon="$cat['icon']" :pad="false">
            <x-slot:actions>
                <a href="{{ route('public-data.category', $key) }}" class="text-[12px] font-bold text-emerald-700 hover:text-emerald-900 hover:underline">
                    Lihat Semua {{ $cat['source_count'] }} Sumber →
                </a>
            </x-slot:actions>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ min($cat['source_count'], 4) }} gap-3 p-4">
                @forelse ($cat['sources'] as $src)
                    <x-source-status-card :source="$src"/>
                @empty
                    <div class="col-span-full p-6 text-center text-[12px] text-gray-400">
                        Belum ada sumber data terdaftar untuk kategori ini.
                    </div>
                @endforelse
            </div>
        </x-dashboard-section>
    @endforeach

</div>
@endsection