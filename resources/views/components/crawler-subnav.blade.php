@props(['links' => []])

<div class="py-1">
    @foreach ($links as $link)
        <a href="{{ route($link['route']) }}{{ $link['extra'] ?? '' }}"
           class="flex items-center gap-2 pl-12 pr-5 py-2 text-[12px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs($link['route'] . '*') ? 'bg-white/10 text-white border-violet-400' : 'text-white/60 border-transparent' }}">
            <span class="text-[9px] opacity-60">▸</span>{{ $link['label'] }}
        </a>
    @endforeach
</div>