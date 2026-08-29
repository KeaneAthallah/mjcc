@props(['sos'])

<x-stat-card label="Status" value="" icon="🚨" color="red" icon-bg="bg-red-100 text-red-600" value-id="sos-stat-value">
    <x-slot:footer>
        <x-badge color="{{ ['active' => 'red', 'acknowledged' => 'amber', 'responding' => 'blue', 'resolved' => 'green', 'cancelled' => 'gray'][$sos->status] ?? 'gray' }}">
            {{ ['active' => 'Aktif', 'acknowledged' => 'Diterima', 'responding' => 'Menuju Lokasi', 'resolved' => 'Selesai', 'cancelled' => 'Dibatalkan'][$sos->status] ?? $sos->status }}
        </x-badge>
    </x-slot:footer>
</x-stat-card>