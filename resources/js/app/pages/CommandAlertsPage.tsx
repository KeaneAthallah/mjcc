import { useState } from 'react';
import { useCommandAlerts } from '../hooks/useCommandAlerts';
import { useKecamatans } from '../hooks/useKecamatans';
import { formatDateTime } from '../lib/format';
import { Badge } from '../components/Badge';
import { Card } from '../components/Card';
import { DashboardError } from '../components/DashboardError';
import type { AlertFilters, AlertSeverity, AlertStatus, CommandAlertItem } from '../types/alerts';

const SEVERITY_OPTIONS: { value: AlertSeverity; label: string; color: string }[] = [
    { value: 'critical', label: 'Kritis', color: 'red' },
    { value: 'warning', label: 'Peringatan', color: 'amber' },
    { value: 'info', label: 'Info', color: 'blue' },
];

const STATUS_OPTIONS: { value: AlertStatus; label: string }[] = [
    { value: 'baru', label: 'Baru' },
    { value: 'ditinjau', label: 'Ditinjau' },
    { value: 'ditangani', label: 'Ditangani' },
    { value: 'selesai', label: 'Selesai' },
];

function SeverityDot({ severity }: { severity: AlertSeverity }): React.JSX.Element {
    const colors: Record<AlertSeverity, string> = {
        critical: 'bg-red-500',
        warning: 'bg-amber-400',
        info: 'bg-blue-400',
    };

    return <span className={`mt-1.5 w-2.5 h-2.5 rounded-full shrink-0 ${colors[severity]}`} />;
}

function StatusBadge({ status }: { status: AlertStatus }): React.JSX.Element {
    const colors: Record<AlertStatus, string> = {
        baru: 'red',
        ditinjau: 'amber',
        ditangani: 'blue',
        selesai: 'green',
    };

    return <Badge color={colors[status]}>{status}</Badge>;
}

export function CommandAlertsPage(): React.JSX.Element {
    const [filters, setFilters] = useState<AlertFilters>({
        severity: '',
        status: '',
        kecamatan: '',
        search: '',
    });
    const { data, isPending, isError, refetch } = useCommandAlerts(filters);
    const { data: kecamatans } = useKecamatans();

    const setSeverity = (severity: AlertSeverity | ''): void => setFilters((prev) => ({ ...prev, severity }));
    const setStatus = (status: AlertStatus | ''): void => setFilters((prev) => ({ ...prev, status }));
    const setKecamatan = (kecamatan: number | ''): void => setFilters((prev) => ({ ...prev, kecamatan }));
    const setSearch = (search: string): void => setFilters((prev) => ({ ...prev, search }));

    return (
        <div className="space-y-5 p-5 page-transition">
            <div className="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 className="text-xl sm:text-2xl font-extrabold text-gray-900">Command Alerts</h1>
                    <p className="text-[12px] text-gray-500 mt-0.5">Masalah terdeteksi dari data — perlu peninjauan & tindak lanjut</p>
                </div>
                <button
                    type="button"
                    onClick={() => void refetch()}
                    className="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-[12px] font-bold bg-white text-gray-600 border-gray-300 hover:bg-gray-50 transition"
                >
                    🔄 Muat Ulang
                </button>
            </div>

            {data ? (
                <div className="flex items-center gap-2 flex-wrap">
                    <Badge color="red">Kritis {data.counts.critical}</Badge>
                    <Badge color="amber">Peringatan {data.counts.warning}</Badge>
                    <Badge color="blue">Info {data.counts.info}</Badge>
                    <Badge color="gray">Terbuka {data.counts.open}</Badge>
                </div>
            ) : null}

            <Card>
                <div className="flex items-center gap-2 flex-wrap">
                    <select
                        value={filters.severity}
                        onChange={(event) => setSeverity(event.target.value as AlertSeverity | '')}
                        className="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[12px] font-semibold text-gray-700"
                        aria-label="Filter tingkat keparahan"
                    >
                        <option value="">Semua Keparahan</option>
                        {SEVERITY_OPTIONS.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <select
                        value={filters.status}
                        onChange={(event) => setStatus(event.target.value as AlertStatus | '')}
                        className="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[12px] font-semibold text-gray-700"
                        aria-label="Filter status"
                    >
                        <option value="">Semua Status</option>
                        {STATUS_OPTIONS.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <select
                        value={filters.kecamatan}
                        onChange={(event) => setKecamatan(event.target.value === '' ? '' : Number(event.target.value))}
                        className="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[12px] font-semibold text-gray-700"
                        aria-label="Filter kecamatan"
                    >
                        <option value="">Semua Kecamatan</option>
                        {(kecamatans ?? []).map((kecamatan) => (
                            <option key={kecamatan.id} value={kecamatan.id}>
                                {kecamatan.name}
                            </option>
                        ))}
                    </select>
                    <input
                        type="search"
                        value={filters.search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Cari judul / deskripsi…"
                        className="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[12px] font-semibold text-gray-700 min-w-[220px]"
                        aria-label="Cari alert"
                    />
                </div>
            </Card>

            {isPending ? (
                <Card>
                    <div className="space-y-3">
                        {Array.from({ length: 5 }).map((_, index) => (
                            <div key={index} className="rounded-xl bg-gray-100 h-16 animate-pulse" />
                        ))}
                    </div>
                </Card>
            ) : isError || !data ? (
                <DashboardError onRetry={() => void refetch()} />
            ) : data.alerts.length === 0 ? (
                <Card>
                    <div className="py-6 text-center">
                        <div className="text-4xl mb-2">✅</div>
                        <h4 className="text-[14px] font-extrabold text-gray-700">Tidak ada alert</h4>
                        <p className="text-[12px] text-gray-400 mt-1">Alert dengan filter ini tidak ditemukan.</p>
                    </div>
                </Card>
            ) : (
                <div className="space-y-3">
                    {data.alerts.map((alert) => (
                        <AlertCard key={alert.id} alert={alert} />
                    ))}
                </div>
            )}
        </div>
    );
}

function AlertCard({ alert }: { alert: CommandAlertItem }): React.JSX.Element {
    return (
        <div className="rounded-2xl bg-white border border-gray-200 shadow-sm p-4">
            <div className="flex items-start gap-3">
                <SeverityDot severity={alert.severity} />
                <div className="min-w-0 flex-1">
                    <div className="flex items-center justify-between gap-3 flex-wrap">
                        <h3 className="text-[14px] font-extrabold text-gray-800">{alert.title}</h3>
                        <StatusBadge status={alert.status} />
                    </div>
                    <p className="text-[12px] text-gray-500 mt-1">{alert.description}</p>
                    <div className="mt-2 flex items-center gap-x-4 gap-y-1 flex-wrap text-[11px] text-gray-400">
                        <span>
                            {alert.sector} · {alert.severity_label}
                        </span>
                        {alert.kecamatan_name ? <span>📍 {alert.kecamatan_name}</span> : null}
                        <span>Mulai {formatDateTime(alert.opened_at)}</span>
                        {alert.resolved_at ? <span>Diselesaikan {formatDateTime(alert.resolved_at)}</span> : null}
                        <span className="ml-auto">
                            #{alert.id} · {alert.status_label}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
}