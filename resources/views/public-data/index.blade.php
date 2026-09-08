@extends('layouts.app')

@section('title', 'Data Publik')

@section('content')
<div class="page-transition space-y-4">

    <x-page-title title="Data Publik" subtitle="Statistik terbuka dari portal Satu Data Morowali — data.morowalikab.go.id">
        <x-slot:actions>
            <a href="{{ config('public_data.satudata.base_url', '#') }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 text-[12px] font-bold text-white bg-gray-800 hover:bg-gray-700 rounded-xl px-3 py-2 transition">
                ⬅️ Buka Portal Satudata
            </a>
        </x-slot:actions>
    </x-page-title>

    {{-- Catatan sumber --}}
    <x-card title="Tentang Modul" subtitle="Data terhimpun otomatis dari portal terbuka pemerintah daerah" icon="ℹ️">
        <div class="text-[12.5px] leading-relaxed text-gray-600 space-y-3">
            <p>
                Data Publik menghimpun statistik statistik <strong>sektor Pendidikan, Kesehatan, dan Keamanan</strong>
                yang diterbitkan oleh Pemerintah Kabupaten Morowali melalui portal
                <strong>Satu Data Morowali</strong> (<em>{{ config('public_data.satudata.base_url') }}</em>).
                Pengumpulan dilakukan terjadwal setiap hari dan data disimpan dalam bentuk mentah hasil normalisasi.
            </p>
            <p class="text-gray-500">
                Keterbatasan yang jujur: portal sejauh ini belum menerbitkan dataset keamanan dari lembaga kepolisian.
                Sektor Keamanan menghimpun yang tersedia (Kesbangpol &amp; Penanggulangan Bencana) dan terus memantau
                setiap sinkronisasi untuk dataset baru.
            </p>
        </div>
    </x-card>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <x-stat-card label="Total Record" :value="number_format($totalRecords)" icon="🗃️" color="emerald"/>
        <x-stat-card label="Total Dataset" :value="number_format($totalDatasets)" icon="📦" color="violet"/>
        <x-stat-card label="Sinkronisasi Terakhir" :value="$latestScrapedAt?->translatedFormat('d M Y H:i') ?? '—'" icon="🔄" color="amber"/>
    </div>

    {{-- Grid sektor --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($sectors as $sector)
            <a href="{{ $sector['url'] }}" class="group block">
                <x-card :title="$sector['label']"
                        :subtitle="$sector['key'] === 'keamanan' ? 'Kesbangpol · Penanggulangan Bencana' : 'Statistik sektor '. $sector['key']"
                        icon="{{ match($sector['key']) { 'pendidikan' => '🎓', 'kesehatan' => '🩺', default => '🛡️' } }}"
                        class="h-full transition group-hover:-translate-y-0.5 group-hover:shadow-lg">
                    <div class="flex items-baseline justify-between gap-2 mb-4">
                        <div>
                            <div class="text-2xl font-extrabold text-gray-800">{{ number_format($sector['records']) }}</div>
                            <div class="text-[11.5px] text-gray-400">record tersimpan</div>
                        </div>
                        <x-badge :color="$sector['sync']['status'] === 'berhasil' ? 'green' : ($sector['sync']['status'] === 'gagal' ? 'red' : 'gray')">
                            {{ $sector['sync']['status_label'] }}
                        </x-badge>
                    </div>
                    <dl class="space-y-1.5 text-[12px] text-gray-500">
                        <div class="flex justify-between"><dt>Dataset</dt><dd class="font-semibold text-gray-700">{{ number_format($sector['datasets']) }}</dd></div>
                        <div class="flex justify-between"><dt>Terakhir berhasil</dt><dd class="font-semibold text-gray-700">{{ $sector['sync']['last_success_at']?->translatedFormat('d M Y H:i') ?? 'Belum pernah' }}</dd></div>
                        <div class="flex justify-between"><dt>Percobaan terakhir</dt><dd class="font-semibold text-gray-700">{{ $sector['sync']['last_attempt_at']?->translatedFormat('d M Y H:i') ?? 'Belum pernah' }}</dd></div>
                    </dl>
                    <div class="mt-4 pt-3 border-t border-gray-100 text-[12px] font-bold text-emerald-600 group-hover:text-emerald-700">
                        Lihat detail sektor →
                    </div>
                </x-card>
            </a>
        @endforeach
    </div>

</div>
@endsection