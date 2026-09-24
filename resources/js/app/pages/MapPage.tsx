import { useMemo, useState } from 'react';
import { MapView } from '../components/MapView';
import { DashboardError } from '../components/DashboardError';
import { KecamatanFilter } from '../components/KecamatanFilter';
import { Card } from '../components/Card';
import { Badge } from '../components/Badge';
import { useMapData } from '../hooks/useMapData';
import { MARKER_TYPE_COLORS, MARKER_TYPE_LABELS, SECTOR_LABELS } from '../types/maps';

const TYPE_OPTIONS: Record<string, { value: string; label: string }[]> = {
    pendidikan: [{ value: 'school', label: 'Sekolah' }],
    ketertiban: [
        { value: 'polsek', label: 'Polsek' },
        { value: 'tipkamtikmas', label: 'Tipkamtikmas' },
        { value: 'poskamling', label: 'Poskamling' },
        { value: 'market', label: 'Pasar' },
    ],
    kesehatan: [{ value: 'health', label: 'Faskes' }],
};

function centroid(latitudes: number[], longitudes: number[]): [number, number] | null {
    if (latitudes.length === 0) {
        return null;
    }

    return [
        latitudes.reduce((sum, value) => sum + value, 0) / latitudes.length,
        longitudes.reduce((sum, value) => sum + value, 0) / longitudes.length,
    ];
}

export function MapPage(): React.JSX.Element {
    const [sector, setSector] = useState('');
    const [type, setType] = useState('');
    const [kecamatanId, setKecamatanId] = useState<number | null>(null);

    const { data, isPending, isError, refetch } = useMapData({ sector, type, kecamatanId });

    const markers = data?.markers ?? [];

    const counts = useMemo(() => {
        const map = new Map<string, number>();

        for (const marker of markers) {
            map.set(marker.type, (map.get(marker.type) ?? 0) + 1);
        }

        return map;
    }, [markers]);

    const center = useMemo(() => centroid(markers.map((m) => m.latitude), markers.map((m) => m.longitude)), [markers]);

    const handleSectorChange = (value: string): void => {
        setSector(value);
        setType('');
    };

    return (
        <div className="space-y-5 p-5 page-transition">
            <div className="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 className="text-xl sm:text-2xl font-extrabold text-gray-900">Peta Gabungan</h1>
                    <p className="text-[12px] text-gray-500 mt-0.5">Sebaran fasilitas lintas sektor di Morowali</p>
                </div>
                <KecamatanFilter value={kecamatanId} onChange={setKecamatanId} />
            </div>

            <Card>
                <div className="flex items-center gap-3 flex-wrap">
                    <label className="text-[12px] font-bold text-gray-600">Sektor</label>
                    <select
                        value={sector}
                        onChange={(event) => handleSectorChange(event.target.value)}
                        className="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[12px] font-semibold text-gray-700"
                        aria-label="Filter sektor"
                    >
                        <option value="">Semua sektor</option>
                        {Object.entries(SECTOR_LABELS).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>

                    <label className="text-[12px] font-bold text-gray-600">Jenis</label>
                    <select
                        value={type}
                        onChange={(event) => setType(event.target.value)}
                        className="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[12px] font-semibold text-gray-700"
                        disabled={sector === ''}
                        aria-label="Filter jenis"
                    >
                        <option value="">Semua jenis</option>
                        {(TYPE_OPTIONS[sector] ?? []).map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>

                    <div className="flex-1" />

                    <button
                        type="button"
                        onClick={() => void refetch()}
                        className="rounded-xl border px-3 py-2 text-[12px] font-bold bg-white text-gray-600 border-gray-300 hover:bg-gray-50 transition"
                    >
                        🔄 Muat Ulang
                    </button>
                </div>
            </Card>

            {isPending ? (
                <div className="rounded-2xl bg-gray-100 h-[480px] animate-pulse" />
            ) : isError || !data ? (
                <DashboardError onRetry={() => void refetch()} />
            ) : (
                <>
                    <div className="flex items-center gap-2 flex-wrap">
                        <Badge color="blue">{markers.length} titik</Badge>
                        {[...counts.entries()].map(([markerType, count]) => (
                            <span
                                key={markerType}
                                className="inline-flex items-center gap-1.5 rounded-full bg-white border border-gray-200 px-3 py-1 text-[11px] font-bold text-gray-700"
                            >
                                <span className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: MARKER_TYPE_COLORS[markerType] ?? '#6366f1' }} />
                                {MARKER_TYPE_LABELS[markerType] ?? markerType} {count}
                            </span>
                        ))}
                    </div>

                    {markers.length === 0 ? (
                        <Card>
                            <div className="py-6 text-center">
                                <div className="text-4xl mb-2">🗺️</div>
                                <h4 className="text-[14px] font-extrabold text-gray-700">Tidak ada titik</h4>
                                <p className="text-[12px] text-gray-400 mt-1">Tidak ada lokasi dengan koordinat untuk filter ini.</p>
                            </div>
                        </Card>
                    ) : (
                        <MapView markers={markers} center={center} className="h-[480px]" />
                    )}
                </>
            )}
        </div>
    );
}