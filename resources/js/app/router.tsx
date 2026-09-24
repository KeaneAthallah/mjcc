import { createBrowserRouter, redirect } from 'react-router-dom';
import { AppLayout } from './components/AppLayout';
import { GuestOnly, RequireAuth } from './components/Guards';
import { LoginPage } from './pages/LoginPage';
import { DashboardPage } from './pages/DashboardPage';
import { EducationDashboardPage } from './pages/EducationDashboardPage';
import { SecurityDashboardPage } from './pages/SecurityDashboardPage';
import { HealthDashboardPage } from './pages/HealthDashboardPage';
import { CommandAlertsPage } from './pages/CommandAlertsPage';
import { DataPublikPage } from './pages/DataPublikPage';
import { DataSourcePage } from './pages/DataSourcePage';
import { SearchPage } from './pages/SearchPage';
import { NotificationsPage } from './pages/NotificationsPage';
import { MapPage } from './pages/MapPage';
import { KecamatanIntelligencePage } from './pages/KecamatanIntelligencePage';
import { SosPage } from './pages/SosPage';
import { SosDetailPage } from './pages/SosDetailPage';
import { SendSosPage } from './pages/SendSosPage';
import { NotFoundPage } from './pages/NotFoundPage';

/** Placeholder perbaikan (TODO): halaman sesungguhnya menyusul per bagian. */
function PlaceholderPage({ title, note }: { title: string; note: string }): React.JSX.Element {
    return (
        <div className="p-6">
            <h1 className="text-xl font-extrabold text-gray-800 mb-1">{title}</h1>
            <p className="text-[13px] text-gray-500">{note}</p>
            <div className="mt-4 rounded-xl bg-white border border-dashed border-gray-300 p-6 text-[12px] text-gray-400">
                Bagian ini belum dibangun.
            </div>
        </div>
    );
}

export const router = createBrowserRouter([
    {
        path: '/login',
        element: (
            <GuestOnly>
                <LoginPage />
            </GuestOnly>
        ),
    },
    {
        path: '/register',
        element: (
            <GuestOnly>
                <PlaceholderPage title="Daftar" note="Pendaftaran publik (role viewer)." />
            </GuestOnly>
        ),
    },
    {
        path: '/',
        element: (
            <RequireAuth>
                <AppLayout />
            </RequireAuth>
        ),
        children: [
            { index: true, element: <DashboardPage /> },
            { path: 'pendidikan', element: <EducationDashboardPage /> },
            { path: 'ketertiban', element: <SecurityDashboardPage /> },
            { path: 'kesehatan', element: <HealthDashboardPage /> },
            { path: 'kecamatan', element: <KecamatanIntelligencePage /> },
            { path: 'alerts', element: <CommandAlertsPage /> },
            { path: 'search', element: <SearchPage /> },
            { path: 'peta', element: <MapPage /> },
            { path: 'data-publik', element: <DataPublikPage /> },
            { path: 'data-publik/sumber/:key', element: <DataSourcePage /> },
            { path: 'notifications', element: <NotificationsPage /> },
            { path: 'sos', element: <SosPage /> },
            { path: 'sos/kirim', element: <SendSosPage /> },
            { path: 'sos/:id', element: <SosDetailPage /> },
            { path: '*', element: <NotFoundPage /> },
        ],
    },
    {
        path: '*',
        element: <NotFoundPage />,
    },
    {
        path: '/logoff',
        loader: () => redirect('/'),
    },
]);

declare global {
    interface Window {
        __MJCC_ROUTES__?: typeof router;
    }
}