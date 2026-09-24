import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { useMarkNotificationRead, useNotifications } from '../hooks/useNotifications';
import { formatDateTime } from '../lib/format';

export function NotificationBell(): React.JSX.Element {
    const [open, setOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);
    const { data, unreadCount } = useNotifications();
    const markRead = useMarkNotificationRead();

    const items = (data ?? []).slice(0, 6);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onClickAway = (event: MouseEvent): void => {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onClickAway);

        return () => document.removeEventListener('mousedown', onClickAway);
    }, [open]);

    return (
        <div ref={containerRef} className="relative">
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                aria-label="Notifikasi"
                className="relative rounded-full p-2 text-gray-600 hover:bg-gray-100 transition"
            >
                🔔
                {unreadCount > 0 ? (
                    <span className="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">
                        {unreadCount}
                    </span>
                ) : null}
            </button>

            {open ? (
                <div className="absolute right-0 top-11 z-50 w-80 rounded-xl bg-white border border-gray-200 shadow-xl overflow-hidden">
                    <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                        <span className="text-[13px] font-extrabold text-gray-800">Notifikasi</span>
                        <span className="text-[11px] text-gray-400 font-bold">{unreadCount} belum dibaca</span>
                    </div>

                    <div className="max-h-96 overflow-y-auto divide-y divide-gray-50">
                        {items.length === 0 ? (
                            <div className="px-4 py-6 text-center text-[12px] text-gray-400">Belum ada notifikasi.</div>
                        ) : (
                            items.map((notification) => (
                                <button
                                    key={notification.id}
                                    type="button"
                                    onClick={() => {
                                        if (notification.read_at === null) {
                                            markRead.mutate(notification.id);
                                        }
                                        setOpen(false);
                                    }}
                                    className={`w-full text-left px-4 py-3 hover:bg-gray-50 transition ${
                                        notification.read_at === null ? 'bg-emerald-50/60' : ''
                                    }`}
                                >
                                    <div className="flex items-center gap-2">
                                        <span className={`w-2 h-2 rounded-full ${notification.read_at === null ? 'bg-emerald-500' : 'bg-transparent'}`} />
                                        <span className="text-[13px] font-bold text-gray-800 truncate">{notification.title}</span>
                                    </div>
                                    <p className="text-[12px] text-gray-500 mt-1 line-clamp-2">{notification.body}</p>
                                    <span className="text-[11px] text-gray-400">{formatDateTime(notification.created_at)}</span>
                                </button>
                            ))
                        )}
                    </div>

                    <div className="px-4 py-3 border-t border-gray-100 text-right">
                        <Link
                            to="/notifications"
                            onClick={() => setOpen(false)}
                            className="text-[12px] font-bold text-emerald-700 hover:underline"
                        >
                            Lihat semua →
                        </Link>
                    </div>
                </div>
            ) : null}
        </div>
    );
}