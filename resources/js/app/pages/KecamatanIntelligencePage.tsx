import { useMemo, useState } from 'react';
import { MapView } from '../components/MapView';
import { DashboardError } from '../components/DashboardError';
import { Card } from '../components/Card';
import { Badge } from '../components/Badge';
import { useMapData } from '../hooks/useMapData';
import { formatNumber } from '../lib/format';
import { MARKER_TYPE_LABELS } from '../types/maps';
import type { MapMarker } from '../types/maps';

interface KecamatanSummary {
    name: string;
    total: number;
    byType: Record<string, number>;
    lat: number;
    lng: number;
}

function summarize(markers: MapMarker[]): KecamatanSummary[] {
    const groups = new Map<string, { byType: Map<string, number>; lats: number[]; lngs: number[] }>();

    for (const marker of markers) {
        const key = marker.kecamatan ?? 'Tanpa Kecamatan';
        const group = groups.get(key) ?? { byType: new Map<string, number>(), lats: [], lngs: [] };

        group.byType.set(marker.type, (group.byType.get(marker.type) ?? 0) + 1);
        group.lats.push(marker.latitude);
        group.lngs.push(marker.longitude);
        groups.set(key, group);
    }

    return [...groups.entries()]
        .map(([name, group]) => ({
            name,
            total: group.lats.length,
            byType: Object.fromEntries(group.byType),
            lat: group.lats.reduce((sum, value) => sum + value, 0) / group.lats.length,
            lng: group.lngs.reduce((sum, value) => sum + value, 0) / group.lngs.length,
        }))
        .sort((a, b) => b.total - a.total);
}

export function KecamatanIntelligencePage(): React.JSX.Element {
    const [selectedName, setSelectedName] = useState<string | null>(null);

    const { data, isPending, isError, refetch } = useMapData({ sector: '', type: '', kecamatanId: null });

    const markers = data?.markers ?? [];
    const summaries = useMemo(() => summarize(markers), [markers]);

    const selected = summaries.find((summary) => summary.name === selectedName) ?? null;
    const selectedKecamatanName = selected?.name ?? selectedName;
    const scopedMarkers = selectedKecamatanName ? markers.filter((marker) => (marker.kecamatan ?? 'Tanpa Kecamatan') === selectedKecamatanName) : markers;
    const center: [number, number] | null = selected
        ? [selected.lat, selected.lng]
        : scopedMarkers.length > 0
            ? [
                  scopedMarkers.reduce((sum, marker) => sum + marker.latitude, 0) / scopedMarkers.length,
                  scopedMarkers.reduce((sum, marker) => sum + marker.longitude, 0) / scopedMarkers.length,
              ]
            : null;

    return (
        <div className="space-y-5 p-5 page-transition">
            <div className="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 className="text-xl sm:text-2xl font-extrabold text-gray-900">Intelijen Kecamatan</h1>
                    <p className="text-[12px] text-gray-500 mt-0.5">Peta sebaran dan ringkasan fasilitas per kecamatan</p>
                </div>
                <button
                    type="button"
                    onClick={() => void refetch()}
                    className="rounded-xl border px-3 py-2 text-[12px] font-bold bg-white text-gray-600 border-gray-300 hover:bg-gray-50 transition"
                >
                    🔄 Muat Ulang
                </button>
            </div>

            {isPending ? (
                <div className="rounded-2xl bg-gray-100 h-[480px] animate-pulse" />
            ) : isError || !data ? (
                <DashboardError onRetry={() => void refetch()} />
            ) : (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div className="lg:col-span-2 space-y-4 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap">
                            <Badge color="blue">{formatNumber(markers.length)} titik</Badge>
                            {selectedKecamatanName ? (
                                <Badge color="green">Kecamatan: {selectedKecamatanName}</Badge>
                            ) : (
                                <Badge color="gray">Semua kecamatan</Badge>
                            )}
                        </div>

                        {scopedMarkers.length === 0 ? (
                            <Card>
                                <div className="py-6 text-center">
                                    <div className="text-4xl mb-2">🗺️</div>
                                    <h4 className="text-[14px] font-extrabold text-gray-700">Tidak ada titik</h4>
                                    <p className="text-[12px] text-gray-400 mt-1">Tidak ada lokasi dengan koordinat untuk pilihan ini.</p>
                                </div>
                            </Card>
                        ) : (
                            <MapView markers={scopedMarkers} center={center} className="h-[480px]" />
                        )}
                    </div>

                    <div className="min-w-0">
                        <Card>
                            <div className="grid grid-cols-1 gap-2">
                                {summaries.length === 0 ? (
                                    <p className="text-[12px] text-gray-400 py-3 text-center">Belum ada data koordinat.</p>
                                ) : (
                                    summaries.map((summary) => (
                                        <button
                                            key={summary.name}
                                            type="button"
                                            onClick={() => setSelectedName(summary.name)}
                                            className={`text-left rounded-xl border px-3 py-2.5 transition ${
                                                selectedKecamatanName === summary.name
                                                    ? 'border-emerald-400 bg-emerald-50'
                                                    : 'border-gray-200 bg-white hover:bg-gray-50'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="text-[13px] font-extrabold text-gray-800 truncate">{summary.name}</span>
                                                <Badge color={selectedKecamatanName === summary.name ? 'green' : 'gray'}>{summary.total}</Badge>
                                            </div>
                                            <div className="mt-1.5 flex items-center gap-1.5 flex-wrap">
                                                {Object.entries(summary.byType).map(([type, count]) => (
                                                    <span key={type} className="text-[10px] font-bold text-gray-500 bg-gray-100 rounded-full px-2 py-0.5">
                                                        {MARKER_TYPE_LABELS[type] ?? type} {count}
                                                    </span>
                                                ))}
                                            </div>
                                        </button>
                                    ))
                                )}
                            </div>
                        </Card>
                    </div>
                </div>
            )}
        </div>
    );
}