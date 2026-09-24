import { render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter, Route, Routes, useParams } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import userEvent from '@testing-library/user-event';
import { RealtimeProvider } from '../lib/realtime';
import { NotificationBell } from '../components/NotificationBell';
import { NotificationsPage } from './NotificationsPage';

const operatorUser = {
    id: 1,
    name: 'Operator',
    email: 'op@mjcc.test',
    role: 'operator',
    responder_type: null,
    responder_type_label: null,
    email_verified: true,
};

const notificationsFixture = [
    {
        id: 11,
        user_id: 1,
        title: 'SOS baru dari Warga',
        body: 'Ada SOS medis baru di Bungku.',
        type: 'sos_created',
        data: null,
        read_at: null,
        created_at: '2026-09-24T09:00:00+08:00',
        updated_at: '2026-09-24T09:00:00+08:00',
    },
    {
        id: 12,
        user_id: 1,
        title: 'Sinkronisasi selesai',
        body: 'Data publik diperbarui.',
        type: 'public_data',
        data: null,
        read_at: '2026-09-23T10:00:00+08:00',
        created_at: '2026-09-23T10:00:00+08:00',
        updated_at: '2026-09-23T10:00:00+08:00',
    },
];

beforeEach(() => {
    localStorage.clear();
    localStorage.setItem('mjcc:token', 'token');
    localStorage.setItem('mjcc:user', JSON.stringify(operatorUser));
    vi.stubGlobal(
        'fetch',
        vi.fn(async (input: RequestInfo | URL) => {
            const url = String(input);

            if (url.includes('/notifications?per_page=25')) {
                return new Response(JSON.stringify({ success: true, message: 'Ok', data: notificationsFixture }), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' },
                });
            }

            return new Response(JSON.stringify({ success: true, message: 'Ok', data: null }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' },
            });
        }),
    );
});

function renderPage(): void {
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

    render(
        <MemoryRouter initialEntries={['/notifications']}>
            <QueryClientProvider client={queryClient}>
                <RealtimeProvider>
                    <Routes>
                        <Route path="/notifications" element={<NotificationsPage />} />
                    </Routes>
                </RealtimeProvider>
            </QueryClientProvider>
        </MemoryRouter>,
    );
}

describe('NotificationsPage', () => {
    it('renders notifications with a live unread badge', async () => {
        renderPage();

        expect(await screen.findByText('SOS baru dari Warga')).toBeInTheDocument();
        expect(screen.getByText('Ada SOS medis baru di Bungku.')).toBeInTheDocument();
        expect(screen.getByText('1 belum dibaca')).toBeInTheDocument();
        expect(screen.getByText('2 total')).toBeInTheDocument();
        expect(screen.getByText('dibaca')).toBeInTheDocument();
    });

    it('marks a notification as read', async () => {
        renderPage();

        const markButton = await screen.findByRole('button', { name: 'Tandai dibaca' });
        await userEvent.click(markButton);

        await waitFor(() => {
            expect(fetch).toHaveBeenCalledWith(
                expect.stringContaining('/notifications/11/read'),
                expect.objectContaining({ method: 'POST' }),
            );
        });
    });

    it('shows the empty state', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                const url = String(input);

                return new Response(JSON.stringify({ success: true, message: 'Ok', data: url.includes('/notifications?') ? [] : null }), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' },
                });
            }),
        );

        renderPage();

        expect(await screen.findByText('Tidak ada notifikasi')).toBeInTheDocument();
    });
});

function BellHarness(): React.JSX.Element {
    useParams();

    return <NotificationBell />;
}

describe('NotificationBell', () => {
    it('shows the unread count on the bell and a dropdown with recent items', async () => {
        const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

        render(
            <MemoryRouter initialEntries={['/']}>
                <QueryClientProvider client={queryClient}>
                    <RealtimeProvider>
                        <Routes>
                            <Route path="/" element={<BellHarness />} />
                        </Routes>
                    </RealtimeProvider>
                </QueryClientProvider>
            </MemoryRouter>,
        );

        const bell = await screen.findByRole('button', { name: 'Notifikasi' });
        expect(await screen.findByText('1')).toBeInTheDocument();

        await userEvent.click(bell);

        expect(await screen.findByText('SOS baru dari Warga')).toBeInTheDocument();
        expect(screen.getByText('Lihat semua →')).toBeInTheDocument();
    });
});