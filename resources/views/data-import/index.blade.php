@extends('layouts.app')

@section('title', 'Sinkronisasi Data')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Sinkronisasi Data"
                  subtitle="Impor statistik dari portal Satu Data Morowali ke data master (sekolah, fasilitas kesehatan, kecamatan)">
        <x-slot:actions>
            <form method="POST" action="{{ route('data-import.run') }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 text-[12px] font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl px-3 py-2 transition">
                    ⇄ Sinkronkan Sekarang
                </button>
            </form>
        </x-slot:actions>
    </x-page-title>

    {{-- Penjelasan --}}
    <x-card title="Tentang Modul" subtitle="Bagaimana data statistik menjadi data master" icon="⚙️">
        <div class="text-[12.5px] leading-relaxed text-gray-600 space-y-3">
            <p>
                Setiap hari pukul <strong>{{ config('public_data.schedule', '03:00') }}</strong> perintah
                <code class="text-gray-800 bg-gray-100 px-1.5 py-0.5 rounded text-[11.5px]">data:sync</code> mengambil
                dataset <strong>Pendidikan, Kesehatan, dan Keamanan</strong> dari data.morowalikab.go.id, lalu mengimpornya ke
                data master yang dipakai dashboard dan peta.
            </p>
            <p class="text-gray-500">
                Jenis data statistik (misal "Jumlah sarana pendidikan per kecamatan") hanya mengisi tabel
                <strong>Kecamatan</strong>. Dataset per-entitas (satu baris per sekolah / puskesmas) yang dapat
                diverifikasi lokasinya juga diimpor ke tabel <strong>Sekolah</strong> dan <strong>Fasilitas Kesehatan</strong>.
                Tombol "Sinkronkan Sekarang" menjalankan ulang impor dari hasil pengambilan terakhir tanpa menyentuh portal.
            </p>
        </div>
    </x-card>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <x-stat-card label="Total Run Tercatat" :value="number_format($totals->runs)" icon="🗃️" color="emerald"/>
        <x-stat-card label="Sinkronisasi Terakhir" value="{{ $sectors['pendidikan']['latest']?->finished_at?->translatedFormat('d M Y H:i') ?? 'Belum pernah' }}" icon="🔄" color="amber"/>
        <x-stat-card label="Entitas Diimpor" :value="number_format($totals->entities)" icon="🏫" color="blue"/>
        <x-stat-card label="Kecamatan Diisi" :value="number_format($totals->kecamatan)" icon="🗂️" color="violet"/>
    </div>

    {{-- Sektor --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($sectors as $sector)
            @php($latest = $sector['latest'])
            <x-card :title="$sector['label']"
                    subtitle="Run terakhir per sektor"
                    icon="{{ match($sector['key']) { 'pendidikan' => '🎓', 'kesehatan' => '🩺', default => '🛡️' } }}">
                <div class="flex items-center justify-between mb-4">
                    <x-badge :color="$latest?->status === 'berhasil' ? 'green' : ($latest?->status === 'gagal' ? 'red' : 'gray')">
                        {{ $latest?->statusLabel() ?? 'Belum pernah' }}
                    </x-badge>
                    <span class="text-[11px] text-gray-400">{{ $latest?->finished_at?->translatedFormat('d M Y H:i') ?? '—' }}</span>
                </div>
                <dl class="space-y-1.5 text-[12px] text-gray-500">
                    <div class="flex justify-between"><dt>Dataset dipindai</dt><dd class="font-semibold text-gray-700">{{ number_format((int) ($latest?->datasets_scanned ?? 0)) }}</dd></div>
                    <div class="flex justify-between"><dt>Entitas dibuat / diperbarui</dt><dd class="font-semibold text-gray-700">{{ number_format((int) ($latest?->entities_created ?? 0)) }} / {{ number_format((int) ($latest?->entities_updated ?? 0)) }}</dd></div>
                    <div class="flex justify-between"><dt>Dilewati (tak jelas lokasinya)</dt><dd class="font-semibold text-gray-700">{{ number_format((int) ($latest?->entities_skipped ?? 0)) }}</dd></div>
                    <div class="flex justify-between"><dt>Kecamatan dibuat / diperbarui</dt><dd class="font-semibold text-gray-700">{{ number_format((int) ($latest?->kecamatan_created ?? 0)) }} / {{ number_format((int) ($latest?->kecamatan_updated ?? 0)) }}</dd></div>
                </dl>
            </x-card>
        @endforeach
    </div>

    {{-- Riwayat --}}
    <x-card title="Riwayat Sinkronisasi" subtitle="Setiap run data:sync tercatat sekali per sektor" icon="🧾">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-[12.5px]">
                <thead>
                <tr class="text-[11px] uppercase tracking-wide text-gray-400 border-b border-gray-100">
                    <th class="py-2 pr-3 font-bold">Waktu</th>
                    <th class="py-2 pr-3 font-bold">Sektor</th>
                    <th class="py-2 pr-3 font-bold">Status</th>
                    <th class="py-2 pr-3 font-bold text-right">Dataset</th>
                    <th class="py-2 pr-3 font-bold text-right">Entitas +/−</th>
                    <th class="py-2 pr-3 font-bold text-right">Kecamatan +/−</th>
                    <th class="py-2 font-bold">Catatan</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                @forelse ($logs as $log)
                    <tr>
                        <td class="py-2.5 pr-3 text-gray-700">{{ $log->started_at?->translatedFormat('d M Y H:i') }}</td>
                        <td class="py-2.5 pr-3 font-semibold text-gray-800">
                            {{ config('public_data.sectors.'.$log->sector.'.label', ucfirst($log->sector)) }}
                        </td>
                        <td class="py-2.5 pr-3">
                            <x-badge :color="$log->status === 'berhasil' ? 'green' : ($log->status === 'gagal' ? 'red' : 'amber')">
                                {{ $log->statusLabel() }}
                            </x-badge>
                        </td>
                        <td class="py-2.5 pr-3 text-right text-gray-700">{{ number_format($log->datasets_scanned) }}</td>
                        <td class="py-2.5 pr-3 text-right text-gray-700">{{ number_format($log->entities_created) }} / {{ number_format($log->entities_updated) }}</td>
                        <td class="py-2.5 pr-3 text-right text-gray-700">{{ number_format($log->kecamatan_created) }} / {{ number_format($log->kecamatan_updated) }}</td>
                        <td class="py-2.5 text-gray-500 max-w-[260px] truncate">
                            {{ $log->error_summary ?? ($log->entities_skipped > 0 ? $log->entities_skipped.' entitas dilewati (lokasi tak terverifikasi)' : '') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-gray-400">
                            Belum ada run sinkronisasi. Jalankan <code class="bg-gray-100 px-1.5 py-0.5 rounded">php artisan data:sync</code>
                            atau gunakan tombol "Sinkronkan Sekarang".
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </x-card>

</div>
@endsection