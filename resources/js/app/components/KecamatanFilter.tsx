import { useKecamatans } from '../hooks/useKecamatans';

export function KecamatanFilter({
    value,
    onChange,
}: {
    value: number | null;
    onChange: (kecamatanId: number | null) => void;
}): React.JSX.Element {
    const { data: kecamatans, isPending } = useKecamatans();

    return (
        <select
            value={value ?? ''}
            disabled={isPending}
            onChange={(event) => onChange(event.target.value === '' ? null : Number(event.target.value))}
            className="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[12px] font-semibold text-gray-700 disabled:opacity-60"
            aria-label="Filter kecamatan"
        >
            <option value="">Semua Kecamatan</option>
            {(kecamatans ?? []).map((kecamatan) => (
                <option key={kecamatan.id} value={kecamatan.id}>
                    {kecamatan.name}
                </option>
            ))}
        </select>
    );
}