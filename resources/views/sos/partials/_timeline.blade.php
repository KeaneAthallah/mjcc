@props(['sos'])

@php
    $steps = ['active', 'acknowledged', 'responding', 'resolved'];
    $stepLabels = [
        'active' => 'SOS Dikirim',
        'acknowledged' => 'SOS Diterima',
        'responding' => 'Petugas Menuju Lokasi',
        'resolved' => 'SOS Selesai',
    ];
    $currentIndex = array_search($sos->status, $steps, true);
@endphp

<x-card title="Perkembangan Penanganan">
    @if ($sos->status === 'cancelled')
        <div class="flex items-center gap-2 rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-[13px] font-bold">
            ⚠️ SOS ini telah dibatalkan dan tidak memerlukan penanganan.
        </div>
    @else
        <ol class="space-y-0">
            @foreach ($steps as $i => $step)
                @php $reached = $currentIndex !== false && $i <= $currentIndex; @endphp
                <li class="flex gap-3 relative">
                    @if ($i < count($steps) - 1)
                        <span class="absolute left-[11px] top-6 bottom-0 w-0.5 bg-emerald-200"></span>
                    @endif
                    <span class="relative z-10 w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold
                        {{ $reached ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-400' }}">
                        {{ $reached ? '✓' : $i + 1 }}
                    </span>
                    <div class="pb-4">
                        <div class="text-[13px] font-bold {{ $reached ? 'text-gray-800' : 'text-gray-400' }}">{{ $stepLabels[$step] }}</div>
                        @if ($step === 'acknowledged' && $sos->status === 'acknowledged')
                            <div class="text-[11px] text-gray-500 mt-0.5">{{ $sos->respondedBy?->name ?? 'Petugas' }} · {{ $sos->responded_at?->format('H:i d M Y') }}</div>
                        @endif
                        @if ($step === 'responding' && in_array($sos->status, ['responding', 'resolved'], true))
                            <div class="text-[11px] text-gray-500 mt-0.5">{{ $sos->respondedBy?->name ?? 'Petugas' }} · {{ $sos->responded_at?->format('H:i d M Y') }}</div>
                        @endif
                        @if ($step === 'resolved' && $sos->status === 'resolved')
                            <div class="text-[11px] text-gray-500 mt-0.5">{{ $sos->resolvedBy?->name ?? 'Petugas' }} · {{ $sos->resolved_at?->format('H:i d M Y') }}</div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
        @if (in_array($sos->status, ['acknowledged', 'responding', 'resolved'], true) && $sos->response_message)
            <div class="mt-2 rounded-xl bg-blue-50 border border-blue-200 px-4 py-3">
                <div class="text-[11px] text-blue-500 font-bold uppercase tracking-wide">Pesan dari petugas</div>
                <div class="text-[13px] text-gray-800 mt-1">{{ $sos->response_message }}</div>
            </div>
        @endif
    @endif
</x-card>