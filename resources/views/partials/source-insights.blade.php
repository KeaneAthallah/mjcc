@props(['insights' => []])

@if (count($insights) > 0)
    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <span class="text-[15px]">💡</span>
            <h4 class="text-[13.5px] font-extrabold text-gray-900">Insight & Rekomendasi</h4>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
            @foreach ($insights as $i)
                <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-4">
                    <div class="flex items-start gap-2.5">
                        <span class="text-[18px] leading-none">{{ $i['icon'] }}</span>
                        <div>
                            <p class="text-[12.5px] font-bold text-gray-800">{{ $i['title'] }}</p>
                            <p class="mt-0.5 text-[11.5px] leading-relaxed text-gray-500">{{ $i['detail'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif