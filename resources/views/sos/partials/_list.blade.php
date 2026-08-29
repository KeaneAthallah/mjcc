@props(['alerts', 'statusLabels', 'statusColors'])

@if ($alerts->count())
    <div class="overflow-x-auto">
        <table class="w-full text-[12px]">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3 font-bold">Waktu</th>
                    <th class="text-left px-2 py-3 font-bold">Pelapor</th>
                    <th class="text-left px-2 py-3 font-bold">Status</th>
                    <th class="text-left px-2 py-3 font-bold">Pesan</th>
                    <th class="text-left px-2 py-3 font-bold">Koordinat</th>
                    <th class="text-right px-4 py-3 font-bold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($alerts as $sos)
                    <tr class="hover:bg-gray-50 align-top">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                            {{ $sos->created_at->format('d M Y H:i') }}
                            @if ($sos->status === 'active')
                                <div class="text-[10px] font-bold text-red-500 animate-pulse">BARU</div>
                            @endif
                        </td>
                        <td class="px-2 py-3 font-semibold text-gray-700">
                            {{ $sos->user?->name ?? 'Pengguna' }}
                            <div class="text-[11px] text-gray-400">{{ $sos->user?->role ?? '-' }}</div>
                        </td>
                        <td class="px-2 py-3">
                            <x-badge color="{{ $statusColors[$sos->status] ?? 'gray' }}">{{ $statusLabels[$sos->status] ?? $sos->status }}</x-badge>
                        </td>
                        <td class="px-2 py-3 text-gray-700 max-w-[240px] break-words">{{ $sos->message ?: '—' }}</td>
                        <td class="px-2 py-3 text-gray-500 font-mono text-[11px]">{{ number_format((float) $sos->latitude, 5) }}, {{ number_format((float) $sos->longitude, 5) }}</td>
                        <td class="px-4 py-3 text-right">
                            <x-button href="{{ route('sos.show', $sos) }}" variant="outline" size="xs">Detail</x-button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-pagination :rows="$alerts" />
@else
    <x-empty-state title="Tidak ada data SOS" description="Belum ada permintaan SOS yang cocok dengan filter ini." />
@endif