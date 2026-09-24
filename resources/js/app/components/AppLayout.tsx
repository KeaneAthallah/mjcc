import { useEffect, useState } from 'react';
import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { clearSession, getStoredUser, type AuthUser } from '../lib/auth';
import { onUnauthorized } from '../lib/api';
import { useRealtime } from '../lib/realtime';
import { ConnectionIndicator } from './ConnectionIndicator';
import { NotificationBell } from './NotificationBell';

interface NavItem {
    to: string;
    label: string;
    icon: React.ReactNode;
    border: string;
    end?: boolean;
}

const NAV_SECTIONS: { title: string; items: NavItem[] }[] = [
    {
        title: 'Menu Utama',
        items: [
            {
                to: '/',
                label: 'Beranda',
                icon: '▧',
                border: 'hover:border-emerald-400',
                end: true,
            },
        ],
    },
    {
        title: 'Pemantauan',
        items: [
            {
                to: '/pendidikan',
                label: 'Pendidikan',
                icon: '🎓',
                border: 'hover:border-emerald-400',
            },
            {
                to: '/ketertiban',
                label: 'Ketertiban',
                icon: '🛡️',
                border: 'hover:border-emerald-400',
            },
            {
                to: '/kesehatan',
                label: 'Kesehatan',
                icon: '🏥',
                border: 'hover:border-emerald-400',
            },
        ],
    },
    {
        title: 'Command Center',
        items: [
            {
                to: '/sos',
                label: 'Papan SOS',
                icon: '🆘',
                border: 'hover:border-amber-400',
            },
            {
                to: '/kecamatan',
                label: 'Intelijen Kecamatan',
                icon: '🧭',
                border: 'hover:border-amber-400',
            },
            {
                to: '/alerts',
                label: 'Command Alerts',
                icon: '🚨',
                border: 'hover:border-amber-400',
            },
            {
                to: '/search',
                label: 'Pencarian Intel',
                icon: '🔎',
                border: 'hover:border-amber-400',
            },
            {
                to: '/notifications',
                label: 'Notifikasi',
                icon: '🔔',
                border: 'hover:border-amber-400',
            },
        ],
    },
    {
        title: 'Informasi',
        items: [
            {
                to: '/peta',
                label: 'Peta Gabungan',
                icon: '🗺️',
                border: 'hover:border-emerald-400',
            },
            {
                to: '/data-publik',
                label: 'Data Publik',
                icon: '📊',
                border: 'hover:border-violet-400',
            },
        ],
    },
    {
        title: 'Pelaporan',
        items: [
            {
                to: '/sos',
                label: 'Papan SOS',
                icon: '🆘',
                border: 'hover:border-red-400',
            },
            {
                to: '/sos/kirim',
                label: 'Kirim SOS',
                icon: '📢',
                border: 'hover:border-red-400',
            },
            {
                to: '/notifications',
                label: 'Notifikasi',
                icon: '🔔',
                border: 'hover:border-red-400',
            },
        ],
    },
];

function LiveClock(): React.JSX.Element {
    const [now, setNow] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => setNow(new Date()), 1000);

        return () => clearInterval(timer);
    }, []);

    return (
        <div className="text-right leading-tight">
            <div className="font-mono text-sm font-bold text-gray-800 tabular-nums">
                {now.toLocaleTimeString('id-ID')}
            </div>
            <div className="text-[10px] uppercase tracking-widest text-gray-500">
                {now.toLocaleDateString('id-ID', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                })}
            </div>
        </div>
    );
}

function Sidebar({
    user,
    open,
    onNavigate,
}: {
    user: AuthUser | null;
    open: boolean;
    onNavigate: () => void;
}): React.JSX.Element {
    return (
        <aside
            className={`fixed inset-y-0 left-0 z-40 w-64 flex flex-col bg-gradient-to-b from-gray-900 via-gray-900 to-emerald-700 text-white shadow-2xl transition-transform duration-300 lg:static lg:translate-x-0 ${
                open ? 'translate-x-0' : '-translate-x-full'
            }`}
        >
            <div className="px-6 py-5 text-center border-b border-white/10">
                <div className="w-[72px] h-[72px] mx-auto rounded-full bg-white flex items-center justify-center overflow-hidden shadow-lg mb-2">
                    <img src="/logo.png" alt="Logo Morowali Juara" className="w-full h-full object-contain p-1.5" />
                </div>
                <h2 className="text-[13px] tracking-widest uppercase font-extrabold leading-tight">
                    Morowali Juara
                    <br />
                    Command Center
                </h2>
                <small className="text-[10px] opacity-60 tracking-widest">Dashboard Monitoring</small>
            </div>

            <nav className="flex-1 overflow-y-auto py-3">
                {NAV_SECTIONS.map((section) => (
                    <div key={section.title}>
                        <div className="px-5 py-2 text-[10px] uppercase tracking-widest opacity-40 font-bold">
                            {section.title}
                        </div>
                        {section.items.map((item) => (
                            <NavLink
                                key={item.to}
                                to={item.to}
                                end={item.end}
                                onClick={onNavigate}
                                className={({ isActive }) =>
                                    `flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white ${item.border} ${
                                        isActive
                                            ? 'bg-white/10 text-white border-current'
                                            : 'text-white/65 border-transparent'
                                    }`
                                }
                            >
                                <span className="w-[22px] text-center text-base">{item.icon}</span>
                                {item.label}
                            </NavLink>
                        ))}
                    </div>
                ))}
            </nav>

            <div className="p-5 border-t border-white/10">
                <div className="flex items-center gap-3">
                    <span className="w-9 h-9 rounded-full bg-emerald-500 flex items-center justify-center text-sm font-bold">
                        {user?.name?.charAt(0) ?? '?'}
                    </span>
                    <div className="min-w-0">
                        <div className="text-[12px] font-bold truncate">{user?.name ?? 'Pengguna'}</div>
                        <div className="text-[10px] opacity-60 lowercase">{user?.role ?? '-'}</div>
                    </div>
                </div>
                <button
                    type="button"
                    onClick={() => {
                        clearSession();
                        onNavigate();
                    }}
                    className="mt-3 w-full text-left text-[11px] font-semibold text-white/60 hover:text-white transition"
                >
                    Keluar
                </button>
            </div>
        </aside>
    );
}

export function AppLayout(): React.JSX.Element {
    const navigate = useNavigate();
    const { connection } = useRealtime();
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const user = getStoredUser();

    useEffect(
        () =>
            onUnauthorized(() => {
                navigate('/login', { replace: true });
            }),
        [navigate],
    );

    return (
        <div className="flex h-full">
            <Sidebar user={user} open={sidebarOpen} onNavigate={() => setSidebarOpen(false)} />

            <div className="flex-1 h-full flex flex-col min-w-0">
                <header className="h-14 shrink-0 flex items-center gap-3 bg-white border-b border-gray-200 px-4">
                    <button
                        type="button"
                        onClick={() => setSidebarOpen((open) => !open)}
                        className="lg:hidden rounded-lg p-2 text-gray-600 hover:bg-gray-100"
                        aria-label="Buka menu"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div className="flex-1" />
                    <NotificationBell />
                    <ConnectionIndicator state={connection} />
                    <span className="w-px h-6 bg-gray-200" />
                    <LiveClock />
                </header>

                <main className="flex-1 min-h-0 overflow-y-auto">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}