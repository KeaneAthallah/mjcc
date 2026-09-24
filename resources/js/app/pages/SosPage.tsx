import { Link } from 'react-router-dom';
import { useSosAlerts, isResponderUser, useSosAction } from '../hooks/useSos';
import { formatDateTime } from '../lib/format';
import { Badge } from '../components/Badge';
import { Card } from '../components/Card';
import { DashboardError } from '../components/DashboardError';
import { SOS_OPEN_STATUSES, SOS_STATUS_COLORS } from '../types/sos';
import { useState } from 'react';

const CATEGORY_ICONS: Record<string, string> = {
    general: '🆘',
    medical: '🚑',
    fire: '🚒',
    police: '🚓',
};

export function SosPage(): React.JSX.Element {
    const [status, setStatus] = useState('');
    const { data, isPending, isError, refetch } = useSosAlerts(status);
    const action = useSosAction();
    const responder = isResponderUser();

    const items = data ?? [];
    const openCount = items.filter((item) => SOS_OPEN_STATUSES.includes(item.status)).length;

    const quickAccept = (sosId: number): void => {
        action.mutate({ sosId, action: 'accept' });
    };

    return (
        <div className="space-y-5 p-5 page-transition">
            <div className="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 className="text-xl sm:text-2xl font-extrabold text-gray-900">Papan SOS</h1>
                    <p className="text-[12px] text-gray-500 mt-0.5">Laporan darurat warga secara realtime</p>
                </div>
                <Link
                    to="/sos/kirim"
                    className="rounded-xl bg-red-600 hover:bg-red-700 text-white text-[12px] font-bold px-4 py-2.5 transition"
                >
                    🆘 Kirim SOS
                </Link>
            </div>

            <Card>
                <div className="flex items-center gap-3 flex-wrap">
                    <label className="text-[12px] font-bold text-gray-600">Status</label>
                    <select
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[12px] font-semibold text-gray-700"
                        aria-label="Filter status SOS"
                    >
                        <option value="">Semua status</option>
                        {SOS_OPEN_STATUSES.map((statusKey) => (
                            <option key={statusKey} value={statusKey}>
                                {statusKey}
                            </option>
                        ))}
                        <option value="resolved">Selesai</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                    <div className="flex-1" />
                    <Badge color={openCount > 0 ? 'red' : 'green'}>{openCount} terbuka</Badge>
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
                <Card>
                    <div className="space-y-3">
                        {Array.from({ length: 4 }).map((_, index) => (
                            <div key={index} className="rounded-xl bg-gray-100 h-16 animate-pulse" />
                        ))}
                    </div>
                </Card>
            ) : isError || !data ? (
                <DashboardError onRetry={() => void refetch()} />
            ) : items.length === 0 ? (
                <Card>
                    <div className="py-6 text-center">
                        <div className="text-4xl mb-2">🟢</div>
                        <h4 className="text-[14px] font-extrabold text-gray-700">Tidak ada SOS</h4>
                        <p className="text-[12px] text-gray-400 mt-1">Belum ada laporan darurat untuk filter ini.</p>
                    </div>
                </Card>
            ) : (
                <div className="grid grid-cols-1 gap-3">
                    {items.map((sos) => (
                        <Link
                            key={sos.id}
                            to={`/sos/${sos.id}`}
                            className={`rounded-2xl bg-white border p-4 shadow-sm hover:shadow-md transition ${
                                SOS_OPEN_STATUSES.includes(sos.status) ? 'border-red-200' : 'border-gray-200'
                            }`}
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex items-start gap-3 min-w-0">
                                    <span className="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-lg shrink-0">
                                        {CATEGORY_ICONS[sos.category] ?? '🆘'}
                                    </span>
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <span className="text-[14px] font-extrabold text-gray-900 truncate">
                                                {sos.user?.name ?? `#${sos.id}`}
                                            </span>
                                            <Badge color={SOS_STATUS_COLORS[sos.status] ?? 'gray'}>{sos.status_label}</Badge>
                                            <span className="text-[11px] font-bold text-gray-400">{sos.category_label}</span>
                                        </div>
                                        <p className="text-[12px] text-gray-500 mt-1 line-clamp-2">{sos.message || '—'}</p>
                                        <span className="text-[11px] text-gray-400 mt-1.5 block">{formatDateTime(sos.created_at)}</span>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2 shrink-0">
                                    {responder && sos.status === 'active' ? (
                                        <button
                                            type="button"
                                            onClick={(event) => {
                                                event.preventDefault();
                                                quickAccept(sos.id);
                                            }}
                                            className="rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold px-3 py-2 transition"
                                        >
                                            Terima
                                        </button>
                                    ) : null}
                                </div>
                            </div>
                        </Link>
                    ))}
                </div>
            )}

            {action.isPending ? (
                <div className="fixed inset-0 z-40 flex items-center justify-center bg-black/30">
                    <div className="rounded-xl bg-white px-5 py-4 text-[13px] font-bold text-gray-700 shadow-xl">Memproses…</div>
                </div>
            ) : null}
        </div>
    );
}