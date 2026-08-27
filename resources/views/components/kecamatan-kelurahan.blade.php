@props([
    'kecamatans' => [],
    'kecamatanId' => null,
    'kelurahans' => [],
    'kelurahanId' => null,
    'kecamatanError' => null,
    'kelurahanError' => null,
    'kecamatanCanWrite' => true,
])

<div x-data="kecamatanKelurahan({
    initialKecamatan: @js(old('kecamatan_id', $kecamatanId)),
    initialKelurahan: @js($kelurahans->count() ? old('kelurahan_id', $kelurahanId) : null),
    preloadedKelurahans: @json($kelurahans->map(fn ($k) => ['id' => $k->id, 'name' => $k->name])),
})" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-1">
        <label for="kecamatan_id" class="block text-[12px] font-bold text-gray-700">Kecamatan <span class="text-red-500">*</span></label>
        <select id="kecamatan_id" name="kecamatan_id" x-model="kecamatanId" @change="loadKelurahan()" @if($kecamatanError) x-init="() => $('#kecamatan_id').setCustomValidity('')" @endif
                class="w-full rounded-xl border text-[13px] px-3 py-2.5 bg-white focus:outline-none focus:ring-2 transition {{ $kecamatanError ? 'border-red-400 focus:ring-red-200' : 'border-gray-300 focus:ring-emerald-200 focus:border-emerald-500' }}">
            <option value="">Pilih Kecamatan</option>
            @foreach ($kecamatans as $k)
                <option value="{{ $k->id }}">{{ $k->name }}</option>
            @endforeach
        </select>
        @if ($kecamatanError)
            <p class="text-[11px] text-red-600 font-medium mt-1">{{ $kecamatanError }}</p>
        @endif
    </div>

    <div class="space-y-1">
        <label for="kelurahan_id" class="block text-[12px] font-bold text-gray-700">Kelurahan/Desa</label>
        <select id="kelurahan_id" name="kelurahan_id" x-model="kelurahanId"
                class="w-full rounded-xl border text-[13px] px-3 py-2.5 bg-white focus:outline-none focus:ring-2 transition {{ $kelurahanError ? 'border-red-400 focus:ring-red-200' : 'border-gray-300 focus:ring-emerald-200 focus:border-emerald-500' }}">
            <option value="">-- Pilih kecamatan terlebih dahulu --</option>
            <template x-for="k in kelurahans" :key="k.id">
                <option :value="k.id" x-text="k.name"></option>
            </template>
        </select>
        @if ($kelurahanError)
            <p class="text-[11px] text-red-600 font-medium mt-1">{{ $kelurahanError }}</p>
        @endif
    </div>
</div>

@once
    @push('scripts')
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('kecamatanKelurahan', (config) => ({
            kecamatanId: config.initialKecamatan,
            kelurahanId: config.initialKelurahan,
            kelurahans: config.preloadedKelurahans.length ? config.preloadedKelurahans : [],
            loading: false,
            init() {
                if (this.kecamatanId && !this.kelurahans.length) {
                    this.loadKelurahan();
                }
            },
            async loadKelurahan() {
                this.kelurahanId = '';
                if (!this.kecamatanId) {
                    this.kelurahans = [];
                    return;
                }
                this.loading = true;
                try {
                    const res = await fetch('{{ route('api.kelurahans.by-kecamatan') }}?kecamatan_id=' + this.kecamatanId, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    this.kelurahans = await res.json();
                } catch (e) {
                    this.kelurahans = [];
                } finally {
                    this.loading = false;
                }
            },
        }));
    });
    </script>
    @endpush
@endonce
