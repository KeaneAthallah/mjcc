import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { RealtimeProvider } from '../lib/realtime';
import { SosPage } from './SosPage';

const responderUser = {
    id: 5,
    name: 'Petugas Medis',
    email: 'petugas@mjcc.test',
    role: 'operator',
    responder_type: 'medical',
    responder_type_label: 'Medis',
    email_verified: true,
};

const sosFixture = [
    {
        id: 1,
        user_id: 10,
        user: { id: 10, name: 'Warga Test', role: 'viewer' },
        latitude: -2.5,
        longitude: 121.5,
        accuracy: null,
        status: 'active',
        status_label: 'Aktif',
        category: 'medical',
        category_label: 'Medis',
        message: 'Tolong! butuh bantuan medis.',
        response_message: null,
        constraint_type: null,
        constraint_type_label: null,
        constraint_reason: null,
        constrained_by: null,
        constrained_by_user: null,
        constrained_at: null,
        responded_by: null,
        responded_by_user: null,
        responded_at: null,
        resolved_by: null,
        resolved_by_user: null,
        resolved_at: null,
        accepted_by: null,
        accepted_by_user: null,
        accepted_at: null,
        created_at: '2026-09-24T08:00:00+08:00',
        updated_at: '2026-09-24T08:00:00+08:00',
        is_owner: false,
        can_manage: true,
    },
];

beforeEach(() => {
    localStorage.clear();
    localStorage.setItem('mjcc:token', 'token');
    localStorage.setItem('mjcc:user', JSON.stringify(responderUser));
    vi.stubGlobal(
        'fetch',
        vi.fn(async (input: RequestInfo | URL) => {
            const url = String(input);

            if (url.includes('/sos?per_page')) {
                return new Response(JSON.stringify({ success: true, message: 'Ok', data: sosFixture }), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' },
                });
            }

            if (url.includes('/accept')) {
                return new Response(
                    JSON.stringify({ success: true, message: 'Ok', data: { ...sosFixture[0], status: 'accepted' } }),
                    { status: 200, headers: { 'Content-Type': 'application/json' } },
                );
            }

            return new Response(JSON.stringify({ success: true, message: 'Ok', data: [] }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' },
            });
        }),
    );
});

function renderBoard(): void {
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

    render(
        <MemoryRouter initialEntries={['/sos']}>
            <QueryClientProvider client={queryClient}>
                <RealtimeProvider>
                    <Routes>
                        <Route path="/sos" element={<SosPage />} />
                        <Route path="/sos/:id" element={<div>SOS detail 1</div>} />
                    </Routes>
                </RealtimeProvider>
            </QueryClientProvider>
        </MemoryRouter>,
    );
}

describe('SosPage', () => {
    it('renders the SOS inbox with requester and category', async () => {
        renderBoard();

        expect(await screen.findByText('Papan SOS')).toBeInTheDocument();
        expect(await screen.findByText('Warga Test')).toBeInTheDocument();
        expect(screen.getByText('Aktif')).toBeInTheDocument();
        expect(screen.getByText('Medis')).toBeInTheDocument();
        expect(screen.getByText('Tolong! butuh bantuan medis.')).toBeInTheDocument();
        expect(screen.getByText('1 terbuka')).toBeInTheDocument();
    });

    it('lets a responder accept an active alert directly from the board', async () => {
        renderBoard();

        const acceptButton = await screen.findByRole('button', { name: 'Terima' });
        await userEvent.click(acceptButton);

        await waitFor(() => {
            expect(fetch).toHaveBeenCalledWith(expect.stringContaining('/sos/1/accept'), expect.objectContaining({ method: 'POST' }));
        });
    });

    it('shows the empty state', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                const url = String(input);

                if (url.includes('/sos?per_page')) {
                    return new Response(JSON.stringify({ success: true, message: 'Ok', data: [] }), {
                        status: 200,
                        headers: { 'Content-Type': 'application/json' },
                    });
                }

                return new Response(JSON.stringify({ success: true, message: 'Ok', data: [] }), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' },
                });
            }),
        );

        renderBoard();

        expect(await screen.findByText('Tidak ada SOS')).toBeInTheDocument();
    });
});