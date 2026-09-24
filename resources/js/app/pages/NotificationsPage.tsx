import { useMarkNotificationRead, useNotifications } from '../hooks/useNotifications';
import { formatDateTime } from '../lib/format';
import { Badge } from '../components/Badge';
import { Card } from '../components/Card';
import { DashboardError } from '../components/DashboardError';

export function NotificationsPage(): React.JSX.Element {
    const { data, isPending, isError, refetch, unreadCount } = useNotifications();
    const markRead = useMarkNotificationRead();

    return (
        <div className="space-y-5 p-5 page-transition">
            <div className="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 className="text-xl sm:text-2xl font-extrabold text-gray-900">Notifikasi</h1>
                    <p className="text-[12px] text-gray-500 mt-0.5">Peringatan dan pemberitahuan untuk Anda</p>
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
                    <div className="space-y-3">
                        {Array.from({ length: 5 }).map((_, index) => (
                            <div key={index} className="rounded-xl bg-gray-100 h-16 animate-pulse" />
                        ))}
                    </div>
                </Card>
            ) : isError || !data ? (
                <DashboardError onRetry={() => void refetch()} />
            ) : data.length === 0 ? (
                <Card>
                    <div className="py-6 text-center">
                        <div className="text-4xl mb-2">🔕</div>
                        <h4 className="text-[14px] font-extrabold text-gray-700">Tidak ada notifikasi</h4>
                        <p className="text-[12px] text-gray-400 mt-1">Belum ada pemberitahuan masuk.</p>
                    </div>
                </Card>
            ) : (
                <>
                    <div className="flex items-center gap-2">
                        <Badge color="green">{unreadCount} belum dibaca</Badge>
                        <Badge color="gray">{data.length} total</Badge>
                    </div>
                    <div className="space-y-3">
                        {data.map((notification) => (
                            <div
                                key={notification.id}
                                className={`rounded-2xl bg-white border p-4 shadow-sm ${
                                    notification.read_at === null ? 'border-emerald-200' : 'border-gray-200'
                                }`}
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <h3 className="text-[14px] font-extrabold text-gray-800">{notification.title}</h3>
                                        <p className="text-[12px] text-gray-500 mt-1">{notification.body}</p>
                                        <span className="text-[11px] text-gray-400 mt-2 block">{formatDateTime(notification.created_at)}</span>
                                    </div>
                                    {notification.read_at === null ? (
                                        <button
                                            type="button"
                                            onClick={() => markRead.mutate(notification.id)}
                                            className="shrink-0 rounded-lg border border-emerald-200 px-3 py-1.5 text-[11px] font-bold text-emerald-700 hover:bg-emerald-50 transition"
                                        >
                                            Tandai dibaca
                                        </button>
                                    ) : (
                                        <Badge color="gray">dibaca</Badge>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}