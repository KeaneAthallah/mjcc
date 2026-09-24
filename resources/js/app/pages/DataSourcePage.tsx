import { Link, useParams } from 'react-router-dom';
import { usePublicSource } from '../hooks/usePublicData';
import { compactNumber, formatDateTime } from '../lib/format';
import { Badge } from '../components/Badge';
import { Card } from '../components/Card';
import { DashboardError } from '../components/DashboardError';

const IGNORED_COLUMNS = new Set([
    'id',
    'created_at',
    'updated_at',
    'deleted_at',
    'dedupe_key',
    'raw_data',
    'scraped_at',
    'source_key',
    'source_url',
    'latitude',
    'longitude',
]);

const COLUMN_LABELS: Record<string, string> = {
    record_date: 'Tanggal',
    latest_date: 'Terbaru',
    event_date: 'Tanggal Kejadian',
    commodity: 'Komoditas',
    category: 'Kategori',
    current_price: 'Harga Saat Ini',
    previous_price: 'Harga Sebelumnya',
    price_change: 'Perubahan Harga',
    percentage_change: 'Perubahan (%)',
    availability: 'Ketersediaan',
    market: 'Pasar',
    region: 'Wilayah',
    year: 'Tahun',
    location: 'Lokasi',
    indicator: 'Indikator',
    value: 'Nilai',
    unit: 'Satuan',
    dataset: 'Dataset',
    topic: 'Topik',
    sector: 'Sektor',
    source: 'Sumber',
    hazard_type: 'Jenis Bahaya',
    risk_level: 'Tingkat Risiko',
    district: 'Kecamatan',
    disaster_type: 'Jenis Bencana',
};

function columnLabel(key: string): string {
    return COLUMN_LABELS[key] ?? key.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function cellValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '-';
    }

    if (typeof value === 'boolean') {
        return value ? 'Ya' : 'Tidak';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
}

function deriveColumns(records: Record<string, unknown>[]): string[] {
    const keys = new Set<string>();

    for (const record of records.slice(0, 20)) {
        for (const key of Object.keys(record)) {
            if (!IGNORED_COLUMNS.has(key)) {
                keys.add(key);
            }
        }
    }

    return [...keys];
}

export function DataSourcePage(): React.JSX.Element {
    const { key = '' } = useParams<{ key: string }>();
    const { data, isPending, isError, refetch } = usePublicSource(key);

    const columns = data ? deriveColumns(data.records) : [];

    return (
        <div className="space-y-5 p-5 page-transition">
            <div className="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <Link to="/data-publik" className="text-[12px] font-bold text-violet-600 hover:underline">
                        ← Kembali ke Data Publik
                    </Link>
                    <h1 className="text-xl sm:text-2xl font-extrabold text-gray-900 mt-1">{data?.source.name ?? 'Detail Sumber Data'}</h1>
                </div>
                <button
                    type="button"
                    onClick={() => void refetch()}
                    className="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-[12px] font-bold bg-white text-gray-600 border-gray-300 hover:bg-gray-50 transition"
                >
                    🔄 Muat Ulang
                </button>
            </div>

            {isPending ? (
                <Card>
                    <div className="h-64 animate-pulse rounded-xl bg-gray-100" />
                </Card>
            ) : isError || !data ? (
                <DashboardError onRetry={() => void refetch()} />
            ) : (
                <>
                    <Card>
                        <div className="flex items-center gap-x-6 gap-y-2 flex-wrap text-[12px] text-gray-500">
                            <span>
                                <Badge color={data.source.status === 'active' ? 'green' : 'amber'}>{data.source.status}</Badge>
                            </span>
                            <span>
                                <strong className="text-gray-800 tabular-nums">{compactNumber(data.source.record_count)}</strong> rekaman
                            </span>
                            <span>Sinkron terakhir {formatDateTime(data.source.last_success_at)}</span>
                            {data.latest_date ? <span>Data terbaru {formatDateTime(data.latest_date)}</span> : null}
                            {data.year ? <span>Tahun {data.year}</span> : null}
                            {typeof data.total_pendapatan === 'number' ? (
                                <span>
                                    Pendapatan <strong className="text-gray-800">{compactNumber(data.total_pendapatan)}</strong>
                                </span>
                            ) : null}
                            {typeof data.total_belanja === 'number' ? (
                                <span>
                                    Belanja <strong className="text-gray-800">{compactNumber(data.total_belanja)}</strong>
                                </span>
                            ) : null}
                        </div>
                    </Card>

                    {data.records.length === 0 ? (
                        <Card>
                            <div className="py-6 text-center">
                                <div className="text-4xl mb-2">🗂️</div>
                                <h4 className="text-[14px] font-extrabold text-gray-700">Belum ada rekaman</h4>
                                <p className="text-[12px] text-gray-400 mt-1">Sumber ini belum disinkronkan dengan data baru.</p>
                            </div>
                        </Card>
                    ) : (
                        <Card title={`Rekaman (${compactNumber(data.total)})`} icon="🗂️">
                            <div className="overflow-x-auto -mx-2">
                                <table className="w-full text-left text-[12px] min-w-[560px]">
                                    <thead>
                                        <tr className="text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                                            {columns.map((column) => (
                                                <th key={column} className="px-2 py-2 font-bold whitespace-nowrap">
                                                    {columnLabel(column)}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-50">
                                        {data.records.map((record, index) => (
                                            <tr key={index} className="hover:bg-gray-50">
                                                {columns.map((column) => (
                                                    <td key={column} className="px-2 py-2 align-top">
                                                        {cellValue(record[column])}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </Card>
                    )}
                </>
            )}
        </div>
    );
}