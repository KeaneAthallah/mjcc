@extends('layouts.app')

@section('title', 'Pencarian')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Pencarian" subtitle="Cari sekolah, faskes, poskamling, tipkamtikmas, polsek, pasar, kecamatan, kelurahan">
        <x-slot:actions>
            <form method="GET" action="{{ route('search.index') }}" class="flex items-center gap-2">
                <input type="text" name="q" value="{{ $query }}" placeholder="Ketik kata kunci..."
                       class="rounded-xl border border-gray-300 text-[13px] px-3 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500 w-56">
                <x-button type="submit" variant="primary" size="sm">🔍 Cari</x-button>
            </form>
        </x-slot:actions>
    </x-page-title>

    @if ($query === '')
        <x-card title="Mulai Menelusuri" icon="🔎">
            <p class="text-[13px] text-gray-500">Ketik kata kunci di kolom pencarian untuk menjelajahi seluruh data master Morowali.</p>
            @if ($suggestions->isNotEmpty())
                <div class="flex flex-wrap gap-2 mt-3">
                    @foreach ($suggestions as $s)
                        <a href="{{ route('search.index', ['q' => $s]) }}" class="px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 text-[11px] font-bold hover:bg-gray-200">{{ $s }}</a>
                    @endforeach
                </div>
            @endif
        </x-card>
    @else
        <div class="mb-3 text-[13px] text-gray-500">
            <strong class="text-gray-800">{{ $results->count() }}</strong> hasil untuk <strong class="text-emerald-700">"{{ $query }}"</strong>
        </div>

        @if ($results->isNotEmpty())
            <div class="space-y-2">
                @foreach ($results as $result)
                    <a href="{{ $result['url'] }}"
                       class="flex items-start gap-3 rounded-xl bg-white border border-gray-100 shadow-sm p-3 hover:border-emerald-300 hover:shadow transition">
                        <span class="text-xl leading-none mt-0.5">{{ $result['icon'] }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-[12px] font-extrabold text-gray-800 truncate">{{ $result['title'] }}</span>
                                <x-badge color="green">{{ $result['type'] }}</x-badge>
                            </div>
                            <div class="text-[11px] text-gray-400 truncate">{{ $result['subtitle'] }}</div>
                        </div>
                        <span class="text-emerald-600 text-[13px] font-bold">→</span>
                    </a>
                @endforeach
            </div>
        @else
            <x-empty-state title="Tidak ada hasil" description="Coba kata kunci lain atau periksa ejaan pencarian Anda." />
        @endif
    @endif
</div>
@endsection