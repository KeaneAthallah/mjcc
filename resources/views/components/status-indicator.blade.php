@props([
    'status' => 'baik',
    'score' => null,
    'label' => null,
    'compact' => false,
])

@php
    $config = [
        'baik' => ['color' => 'bg-emerald-500', 'soft' => 'bg-emerald-100 text-emerald-800', 'dot' => 'bg-emerald-500', 'icon' => '✓', 'ring' => 'ring-emerald-200'],
        'waspada' => ['color' => 'bg-amber-500', 'soft' => 'bg-amber-100 text-amber-800', 'dot' => 'bg-amber-500', 'icon' => '!', 'ring' => 'ring-amber-200'],
        'perlu_perhatian' => ['color' => 'bg-orange-500', 'soft' => 'bg-orange-100 text-orange-800', 'dot' => 'bg-orange-500', 'icon' => '⚠', 'ring' => 'ring-orange-200'],
        'kritis' => ['color' => 'bg-red-600', 'soft' => 'bg-red-100 text-red-800', 'dot' => 'bg-red-600', 'icon' => '✕', 'ring' => 'ring-red-200'],
        'tidak_ada_data' => ['color' => 'bg-gray-400', 'soft' => 'bg-gray-100 text-gray-700', 'dot' => 'bg-gray-400', 'icon' => '·', 'ring' => 'ring-gray-200'],
    ];
    $cfg = $config[$status] ?? $config['baik'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 rounded-full font-extrabold tracking-wider ' . ($compact ? 'px-2.5 py-1 text-[10px] ' . $cfg['soft'] : 'px-4 py-2 text-[13px] text-white ' . $cfg['color'])]) }}>
    <span class="w-2 h-2 rounded-full {{ $cfg['dot'] }} {{ $compact ? 'ring-2 ring-white' : 'bg-white/70' }}"></span>
    {{ $label ?? (config("command-center.status_labels.{$status}", strtoupper($status))) }}
    @if ($score !== null)
        <span class="{{ $compact ? 'opacity-70' : 'opacity-80' }} font-bold">{{ $score }}</span>
    @endif
</span>