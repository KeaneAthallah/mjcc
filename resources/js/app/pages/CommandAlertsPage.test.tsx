import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { RealtimeProvider } from '../lib/realtime';
import { CommandAlertsPage } from './CommandAlertsPage';
import type { CommandAlertsResponse } from '../types/alerts';

const alertsFixture: CommandAlertsResponse = {
    alerts: [
        {
            id: 1,
            rule: 'faskes_tanpa_tenaga_medis',
            severity: 'critical',
            severity_label: 'KRITIS',
            sector: 'Kesehatan',
            sector_key: 'kesehatan',
            title: 'Faskes tanpa tenaga medis',
            description: 'Puskesmas Tanpa Dokter tidak memiliki tenaga medis.',
            status: 'baru',
            status_label: 'Baru',
            is_open: true,
            kecamatan_id: 1,
            kecamatan_name: 'Bungku',
            latitude: -1.5,
            longitude: 122.4,
            resource_type: 'health_facility',
            resource_id: 3,
            opened_at: '2026-09-24 08:00:00',
            last_seen_at: '2026-09-24 08:00:00',
            resolved_at: null,
        },
    ],
    counts: { critical: 1, warning: 2, info: 0, open: 3 },
    meta: { current_page: 1, last_page: 1, per_page: 50, total: 1 },
};

beforeEach(() => {
    localStorage.clear();
    vi.stubGlobal(
        'fetch',
        vi.fn(async (input: RequestInfo | URL) => {
            const url = String(input);

            if (url.includes('/alerts?')) {
                return new Response(JSON.stringify({ success: true, message: 'Ok', data: alertsFixture }), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' },
                });
            }

            return new Response(JSON.stringify({ success: true, message: 'Ok', data: [{ id: 1, name: 'Bungku' }] }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' },
            });
        }),
    );
});

afterEach(() => {
    vi.unstubAllGlobals();
});

function renderPage(): void {
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

    render(
        <MemoryRouter>
            <QueryClientProvider client={queryClient}>
                <RealtimeProvider>
                    <CommandAlertsPage />
                </RealtimeProvider>
            </QueryClientProvider>
        </MemoryRouter>,
    );
}

describe('CommandAlertsPage', () => {
    it('renders alerts with counts and detail rows', async () => {
        renderPage();

        expect(await screen.findByText('Command Alerts')).toBeInTheDocument();
        expect(await screen.findByText('Kritis 1')).toBeInTheDocument();
        expect(screen.getByText('Peringatan 2')).toBeInTheDocument();
        expect(await screen.findByText('Faskes tanpa tenaga medis')).toBeInTheDocument();
        expect(screen.getByText('Puskesmas Tanpa Dokter tidak memiliki tenaga medis.')).toBeInTheDocument();
        expect(screen.getByText('📍 Bungku')).toBeInTheDocument();
    });

    it('filters by severity and refetches the list', async () => {
        renderPage();

        const severitySelect = await screen.findByLabelText('Filter tingkat keparahan');
        await userEvent.selectOptions(severitySelect, 'critical');

        await waitFor(() => expect(severitySelect).toHaveValue('critical'));
        expect(await screen.findByText('Faskes tanpa tenaga medis')).toBeInTheDocument();
    });

    it('shows an empty state when there are no alerts', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                const url = String(input);

                if (url.includes('/alerts?')) {
                    return new Response(
                        JSON.stringify({ success: true, message: 'Ok', data: { alerts: [], counts: { critical: 0, warning: 0, info: 0, open: 0 } } }),
                        { status: 200, headers: { 'Content-Type': 'application/json' } },
                    );
                }

                return new Response(JSON.stringify({ success: true, message: 'Ok', data: [{ id: 1, name: 'Bungku' }] }), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' },
                });
            }),
        );

        renderPage();

        expect(await screen.findByText('Tidak ada alert')).toBeInTheDocument();
    });
});