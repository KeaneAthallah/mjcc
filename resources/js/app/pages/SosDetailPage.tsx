import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useSosAction, useSosDetail } from '../hooks/useSos';
import type { SosAction } from '../hooks/useSos';
import { formatDateTime } from '../lib/format';
import { Badge } from '../components/Badge';
import { Card } from '../components/Card';
import { DashboardError } from '../components/DashboardError';
import { MapView } from '../components/MapView';
import { SOS_CATEGORY_COLORS, SOS_OPEN_STATUSES, SOS_STATUS_COLORS } from '../types/sos';
import type { MapMarker } from '../types/maps';
import type { SosAlert } from '../types/sos';

const CONSTRAINT_TYPES = [
    { value: 'traffic', label: 'Kemacetan' },
    { value: 'access', label: 'Akses terhalang' },
    { value: 'distance', label: 'Terlalu jauh' },
    { value: 'danger', label: 'Situasi berbahaya' },
    { value: 'other', label: 'Lainnya' },
];

function timelineStep(active: boolean, done: boolean): string {
    const base = 'w-2 h-2 rounded-full shrink-0';

    return `${base} ${active ? 'bg-emerald-500 ring-4 ring-emerald-100' : done ? 'bg-emerald-500' : 'bg-gray-300'}`;
}

function toMarker(sos: SosAlert): MapMarker {
    return {
        id: sos.id,
        type: 'sos',
        sector: 'sos',
        name: sos.user?.name ?? `SOS #${sos.id}`,
        latitude: sos.latitude,
        longitude: sos.longitude,
        status: sos.status_label,
        kecamatan: null,
        kelurahan: null,
    };
}

export function SosDetailPage(): React.JSX.Element {
    const { id } = useParams<{ id: string }>();
    const sosId = id ? Number(id) : null;
    const { data: sos, isPending, isError, refetch } = useSosDetail(sosId);
    const action = useSosAction();

    const [constraining, setConstraining] = useState(false);
    const [constraintType, setConstraintType] = useState('traffic');
    const [constraintReason, setConstraintReason] = useState('');

    const run = (sosAction: SosAction, payload?: Record<string, unknown>): void => {
        if (sosId !== null) {
            action.mutate({ sosId, action: sosAction, payload });
        }
    };

    const open = sos ? SOS_OPEN_STATUSES.includes(sos.status) : false;

    return (
        <div className="space-y-5 p-5 page-transition">
            <Link to="/sos" className="text-[12px] font-bold text-emerald-700 hover:underline">
                ← Kembali ke Papan SOS
            </Link>

            {sosId === null ? (
                <Card>
                    <div className="py-6 text-center text-[12px] text-gray-500">ID SOS tidak valid.</div>
                </Card>
            ) : isPending ? (
                <Card>
                    <div className="space-y-3">
                        {Array.from({ length: 4 }).map((_, index) => (
                            <div key={index} className="rounded-xl bg-gray-100 h-12 animate-pulse" />
                        ))}
                    </div>
                </Card>
            ) : isError || !sos ? (
                <DashboardError onRetry={() => void refetch()} />
            ) : (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div className="lg:col-span-2 space-y-4 min-w-0">
                        <div className="rounded-2xl bg-white border border-gray-200 shadow-sm p-5">
                            <div className="flex items-center justify-between flex-wrap gap-3">
                                <div className="flex items-center gap-3">
                                    <Badge color={SOS_CATEGORY_COLORS[sos.category] ?? 'gray'}>{sos.category_label}</Badge>
                                    <Badge color={SOS_STATUS_COLORS[sos.status] ?? 'gray'}>{sos.status_label}</Badge>
                                </div>
                                <span className="text-[11px] text-gray-400">{formatDateTime(sos.created_at)}</span>
                            </div>

                            <h2 className="text-lg font-extrabold text-gray-900 mt-4">{sos.user?.name ?? `Pengguna #${sos.user_id}`}</h2>
                            <p className="text-[13px] text-gray-600 mt-2 leading-relaxed">{sos.message || 'Tanpa pesan tambahan.'}</p>

                            <div className="mt-5 space-y-2.5">
                                <div className="flex items-center gap-3">
                                    {timelineStep(sos.status === 'active', sos.status !== 'active')}
                                    <span className="text-[12px] font-bold text-gray-700">SOS dikirim</span>
                                    {sos.accuracy !== null ? <span className="text-[11px] text-gray-400">±{sos.accuracy}m</span> : null}
                                </div>
                                <div className="flex items-center gap-3">
                                    {timelineStep(sos.status === 'acknowledged', ['acknowledged', 'responding', 'accepted', 'on_the_way', 'arrived', 'constrained', 'resolved'].includes(sos.status))}
                                    <span className="text-[12px] font-bold text-gray-700">
                                        Diterima operator{sos.responded_at ? ` · ${formatDateTime(sos.responded_at)}` : ''}
                                    </span>
                                </div>
                                <div className="flex items-center gap-3">
                                    {timelineStep(sos.status === 'responding', ['responding', 'accepted', 'on_the_way', 'arrived', 'constrained', 'resolved'].includes(sos.status))}
                                    <span className="text-[12px] font-bold text-gray-700">
                                        Petugas menuju lokasi{sos.responded_by_user ? ` · ${sos.responded_by_user.name}` : ''}
                                    </span>
                                </div>
                                <div className="flex items-center gap-3">
                                    {timelineStep(sos.status === 'arrived', ['arrived', 'constrained', 'resolved'].includes(sos.status))}
                                    <span className="text-[12px] font-bold text-gray-700">
                                        Petugas tiba{sos.accepted_at ? ` · ${formatDateTime(sos.accepted_at)}` : ''}
                                    </span>
                                </div>
                                <div className="flex items-center gap-3">
                                    {timelineStep(sos.status === 'resolved', sos.status === 'resolved')}
                                    <span className="text-[12px] font-bold text-gray-700">
                                        Selesai{sos.resolved_at ? ` · ${formatDateTime(sos.resolved_at)}` : ''}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {constraining ? (
                            <Card title="Laporkan kendala">
                                <div className="space-y-3">
                                    <select
                                        value={constraintType}
                                        onChange={(event) => setConstraintType(event.target.value)}
                                        className="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-[12px] font-semibold text-gray-700"
                                        aria-label="Jenis kendala"
                                    >
                                        {CONSTRAINT_TYPES.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <textarea
                                        value={constraintReason}
                                        onChange={(event) => setConstraintReason(event.target.value)}
                                        placeholder="Keterangan kendala…"
                                        rows={2}
                                        className="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-[12px] font-semibold text-gray-700"
                                    />
                                    <div className="flex items-center gap-2">
                                        <button
                                            type="button"
                                            onClick={() => run('constraint', { constraint_type: constraintType, constraint_reason: constraintReason })}
                                            className="rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-[12px] font-bold px-4 py-2 transition"
                                        >
                                            Kirim kendala
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setConstraining(false)}
                                            className="rounded-xl border px-3 py-2 text-[12px] font-bold bg-white text-gray-600 border-gray-300 hover:bg-gray-50 transition"
                                        >
                                            Batal
                                        </button>
                                    </div>
                                </div>
                            </Card>
                        ) : null}
                    </div>

                    <div className="space-y-4 min-w-0">
                        {open ? (
                            <Card title="Tindakan">
                                <div className="grid grid-cols-1 gap-2">
                                    {sos.can_manage && sos.status === 'active' ? (
                                        <button
                                            type="button"
                                            onClick={() => run('acknowledge', { response_message: 'Terima kasih, kami segera bertindak.' })}
                                            className="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[12px] font-bold px-3 py-2.5 transition"
                                        >
                                            ✔ Terima SOS
                                        </button>
                                    ) : null}
                                    {sos.can_manage && (sos.status === 'active' || sos.status === 'acknowledged') ? (
                                        <button
                                            type="button"
                                            onClick={() => run('respond', { response_message: 'Petugas sedang menuju lokasi.' })}
                                            className="rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-[12px] font-bold px-3 py-2.5 transition"
                                        >
                                            🚨 Respond / Menuju lokasi
                                        </button>
                                    ) : null}
                                    {sos.can_manage && (sos.status === 'active' || sos.status === 'acknowledged') ? (
                                        <button
                                            type="button"
                                            onClick={() => run('accept')}
                                            className="rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-[12px] font-bold px-3 py-2.5 transition"
                                        >
                                            ✅ Terima sebagai petugas
                                        </button>
                                    ) : null}
                                    {sos.can_manage && (sos.status === 'accepted' || sos.status === 'constrained') ? (
                                        <button
                                            type="button"
                                            onClick={() => run('on-the-way')}
                                            className="rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-[12px] font-bold px-3 py-2.5 transition"
                                        >
                                            🚗 Menuju lokasi
                                        </button>
                                    ) : null}
                                    {sos.can_manage && (sos.status === 'on_the_way' || sos.status === 'constrained') ? (
                                        <button
                                            type="button"
                                            onClick={() => run('arrived')}
                                            className="rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-[12px] font-bold px-3 py-2.5 transition"
                                        >
                                            📍 Tiba di lokasi
                                        </button>
                                    ) : null}
                                    {sos.can_manage && (sos.status === 'accepted' || sos.status === 'on_the_way' || sos.status === 'constrained') ? (
                                        <button
                                            type="button"
                                            onClick={() => setConstraining(true)}
                                            className="rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-[12px] font-bold px-3 py-2.5 transition"
                                        >
                                            ⚠️ Laporkan kendala
                                        </button>
                                    ) : null}
                                    {sos.can_manage &&
                                    ['acknowledged', 'responding', 'accepted', 'on_the_way', 'arrived'].includes(sos.status) ? (
                                        <button
                                            type="button"
                                            onClick={() => run('resolve', { response_message: 'Situasi telah tertangani.' })}
                                            className="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-[12px] font-bold px-3 py-2.5 transition"
                                        >
                                            🎯 Tandai selesai
                                        </button>
                                    ) : null}
                                    {(sos.is_owner || sos.can_manage) && ['active', 'accepted', 'on_the_way', 'constrained', 'arrived'].includes(sos.status) ? (
                                        <button
                                            type="button"
                                            onClick={() => run('cancel')}
                                            className="rounded-xl border border-red-200 bg-white text-red-600 text-[12px] font-bold px-3 py-2.5 hover:bg-red-50 transition"
                                        >
                                            ✕ Batalkan SOS
                                        </button>
                                    ) : null}
                                </div>
                            </Card>
                        ) : null}

                        <Card title="Lokasi">
                            <MapView markers={[toMarker(sos)]} center={[sos.latitude, sos.longitude]} zoom={15} className="h-[220px]" />
                        </Card>

                        <Card title="Detail respons">
                            <div className="space-y-2 text-[12px] text-gray-600">
                                <div className="flex justify-between">
                                    <span className="text-gray-400">Direspons</span>
                                    <span className="font-bold">{sos.responded_by_user?.name ?? '—'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-400">Waktu respons</span>
                                    <span className="font-bold">{sos.responded_at ? formatDateTime(sos.responded_at) : '—'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-400">Diselesaikan</span>
                                    <span className="font-bold">{sos.resolved_by_user?.name ?? '—'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-400">Waktu selesai</span>
                                    <span className="font-bold">{sos.resolved_at ? formatDateTime(sos.resolved_at) : '—'}</span>
                                </div>
                                {sos.constraint_type_label ? (
                                    <div className="flex justify-between">
                                        <span className="text-gray-400">Kendala</span>
                                        <span className="font-bold">{sos.constraint_type_label}</span>
                                    </div>
                                ) : null}
                            </div>
                        </Card>
                    </div>
                </div>
            )}
        </div>
    );
}
