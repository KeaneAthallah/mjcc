@extends('layouts.app')

@section('title', $source->name . ' — Data Publik')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title :title="$source->name" :subtitle="$source->description">
        <x-slot:actions>
            <a href="{{ route('public-data.category', $source->category) }}"
               class="inline-flex items-center gap-1.5 text-[12px] font-bold text-gray-600 border border-gray-200 hover:border-gray-300 rounded-xl px-3 py-2 transition">
                ← Kembali
            </a>
            @can('create', \App\Models\School::class)
                <form method="POST" action="{{ route('public-data.sync-source', $source->key) }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 text-[12px] font-bold text-white bg-violet-600 hover:bg-violet-700 rounded-xl px-3 py-2 transition">
                        🔄 Sinkronkan
                    </button>
                </form>
            @endcan
        </x-slot:actions>
    </x-page-title>

    {{-- Status sinkronisasi --}}
    @if ($source->status === 'berhasil')
        <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[12.5px] text-emerald-800">
            <span class="text-lg leading-none">✅</span>
            <div>
                Sinkronisasi terakhir <strong>{{ $source->last_success_at?->translatedFormat('d M Y H:i') }}</strong>,
                menghimpun <strong>{{ number_format($source->record_count) }}</strong> record.
                @if ($source->sync_duration_ms)
                    <span class="text-emerald-600">({{ number_format($source->sync_duration_ms) }}ms)</span>
                @endif
            </div>
        </div>
    @elseif ($source->status === 'gagal')
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[12.5px] text-amber-800">
            <span class="text-lg leading-none">⚠️</span>
            <div>
                Sinkronisasi gagal. Menampilkan data terakhir yang berhasil diperoleh.
                @if ($source->last_error)
                    <span class="block mt-1 text-[11.5px] text-amber-700">Alasan: {{ $source->last_error }}</span>
                @endif
            </div>
        </div>
    @else
        <div class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-[12.5px] text-gray-600">
            <span class="text-lg leading-none">🕘</span>
            <div>Sumber ini belum pernah disinkronkan. Klik "Sinkronkan" untuk mengambil data.</div>
        </div>
    @endif

    {{-- Source attribution --}}
    <x-source-attribution
        :source-url="$source->source_url ?? '#'"
        :source-name="$source->name"
        :synced-at="$source->last_success_at"
    />

    {{-- Source-specific content --}}
    @if ($source->key === 'sp2kp')
        @include('public-data.partials._commodity')
    @elseif ($source->key === 'bps')
        @include('public-data.partials._bps')
    @elseif ($source->key === 'irbi')
        @include('public-data.partials._risk')
    @elseif ($source->key === 'sitaba')
        @include('public-data.partials._disaster')
    @elseif ($source->key === 'apbd')
        @include('public-data.partials._apbd')
    @else
        @include('public-data.partials._generic')
    @endif

</div>
@endsection