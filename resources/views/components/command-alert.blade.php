@props([
    'alert' => null,
    'transition' => false,
])

@php
    $severityCfg = [
        'critical' => ['icon' => '🔴', 'border' => 'border-l-red-500', 'soft' => 'bg-red-50'],
        'warning' => ['icon' => '🟠', 'border' => 'border-l-amber-500', 'soft' => 'bg-amber-50/60'],
        'info' => ['icon' => '🔵', 'border' => 'border-l-blue-500', 'soft' => 'bg-blue-50/60'],
    ];
    $sc = $severityCfg[$alert->severity] ?? $severityCfg['info'];
    $statusColors = [
        'baru' => 'blue',
        'ditinjau' => 'amber',
        'ditangani' => 'indigo',
        'selesai' => 'gray',
    ];
@endphp

<div class="flex items-start gap-3 rounded-xl bg-white border border-gray-100 border-l-4 {{ $sc['border'] }} p-3 shadow-sm">
    <span class="mt-0.5 text-base leading-none">{{ $sc['icon'] }}</span>

    <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $alert->url() ?? '#' }}"
               class="text-[12px] font-bold text-gray-800 hover:text-emerald-700 truncate">
                {{ $alert->title }}
            </a>
            <x-badge color="{{ strtolower($alert->sector_key) === 'pendidikan' ? 'green' : (strtolower($alert->sector_key) === 'ketertiban' ? 'blue' : 'red') }}">
                {{ $alert->sector }}
            </x-badge>
            <x-badge color="{{ $statusColors[$alert->status] ?? 'gray' }}">{{ $alert->statusLabel() }}</x-badge>
        </div>

        <p class="text-[12px] text-gray-500 mt-0.5 leading-snug">{{ $alert->description }}</p>

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5 text-[11px] text-gray-400">
            @if ($alert->kecamatan)
                <span>📍 {{ $alert->kecamatan->name }}</span>
            @endif
            @if ($alert->opened_at)
                <span>🕐 {{ $alert->opened_at->diffForHumans() }}</span>
            @endif
        </div>

        @if ($transition)
            <div class="flex flex-wrap gap-1.5 mt-2">
                @foreach (\App\Models\CommandAlert::statuses() as $s)
                    @if (auth()->user()?->canManageData())
                        <form method="POST" action="{{ route('alerts.status', $alert) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $s }}">
                            <button type="submit"
                                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold transition
                                           {{ $alert->status === $s
                                               ? 'bg-emerald-600 text-white'
                                               : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                {{ $alert->statusLabel() }}
                            </button>
                        </form>
                    @endif
                @endforeach
            </div>
        @endif

        <div class="flex flex-wrap gap-2 mt-2">
            @if ($alert->url())
                <a href="{{ $alert->url() }}"
                   class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-[11px] font-bold hover:bg-gray-200">
                    Lihat Detail
                </a>
            @endif
            @if ($alert->mapUrl())
                <a href="{{ $alert->mapUrl() }}"
                   class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-600 text-white text-[11px] font-bold hover:bg-emerald-700">
                    🗺️ Lihat di Peta
                </a>
            @endif
        </div>
    </div>
</div>