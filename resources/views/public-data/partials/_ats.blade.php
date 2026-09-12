{{-- Anak Tidak Sekolah: widget eksekutif + daftar record mentah --}}
@include('partials.ats-widgets', ['ats' => $ats, 'hideSourceLink' => true])

<div class="mt-5 grid grid-cols-1 gap-5">
    @include('public-data.partials._generic')
</div>