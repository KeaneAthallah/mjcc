@props(['sos'])

@can('manage', $sos)
    @if ($sos->status === 'active')
        <form method="POST" action="{{ route('sos.acknowledge', $sos) }}" class="inline">
            @csrf
            <input type="hidden" name="response_message" value="">
            <x-button type="submit" variant="blue" size="sm">📥 Terima</x-button>
        </form>
    @endif
    @if (in_array($sos->status, ['active', 'acknowledged'], true))
        <form method="POST" action="{{ route('sos.respond', $sos) }}" class="inline">
            @csrf
            <input type="hidden" name="response_message" value="">
            <x-button type="submit" variant="dark" size="sm">🚓 Menuju Lokasi</x-button>
        </form>
    @endif
    @if (in_array($sos->status, ['acknowledged', 'responding'], true))
        <form method="POST" action="{{ route('sos.resolve', $sos) }}" class="inline">
            @csrf
            <input type="hidden" name="response_message" value="">
            <x-button type="submit" variant="primary" size="sm">✓ Selesaikan</x-button>
        </form>
    @endif
@endcan

@can('cancel', $sos)
    <x-button href="{{ route('sos.cancel', $sos) }}" variant="red" size="sm"
              data-confirm data-confirm-method="POST" data-confirm-title="Batalkan SOS"
              data-confirm-message="Batalkan permintaan SOS ini?">✕ Batalkan</x-button>
@endcan