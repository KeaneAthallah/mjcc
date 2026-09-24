import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { DataPublikPage } from './DataPublikPage';

const categoriesFixture = [
    { key: 'pendidikan', label: 'Pendidikan', description: 'Data satuan pendidikan', icon: '🎓', source_count: 2 },
    { key: 'pemantauan', label: 'Pemantauan', description: 'Data pemantauan', icon: '🛰️', source_count: 1 },
];

const sourcesFixture = [
    {
        key: 'pokok-pendidikan',
        name: 'Pokok Pendidikan',
        category: 'pendidikan',
        description: 'Statistik pokok pendidikan.',
        status: 'active',
        record_count: 1230,
        freshness: 'Hari ini',
        last_success_at: '2026-09-24T09:00:00+08:00',
    },
    {
        key: 'sp2kp',
        name: 'SP2KP',
        category: 'pendidikan',
        description: 'Sarana prasarana.',
        status: 'error',
        record_count: 340,
        freshness: '3 hari lalu',
        last_success_at: null,
    },
];

beforeEach(() => {
    localStorage.clear();
    vi.stubGlobal(
        'fetch',
        vi.fn(async (input: RequestInfo | URL) => {
            const url = String(input);

            if (url.includes('/public-data/sources')) {
                return new Response(JSON.stringify({ success: true, message: 'Ok', data: sourcesFixture }), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' },
                });
            }

            return new Response(JSON.stringify({ success: true, message: 'Ok', data: categoriesFixture }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' },
            });
        }),
    );
});

function renderPage(): void {
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

    render(
        <MemoryRouter>
            <QueryClientProvider client={queryClient}>
                <DataPublikPage />
            </QueryClientProvider>
        </MemoryRouter>,
    );
}

describe('DataPublikPage', () => {
    it('renders category chips with counts', async () => {
        renderPage();

        expect(await screen.findByText('Semua')).toBeInTheDocument();
        expect(await screen.findByText('Pendidikan (2)')).toBeInTheDocument();
        expect(screen.getByText('Pemantauan (1)')).toBeInTheDocument();
    });

    it('renders the source grid with status badges', async () => {
        renderPage();

        expect(await screen.findByText('Pokok Pendidikan')).toBeInTheDocument();
        expect(screen.getByText('SP2KP')).toBeInTheDocument();
        expect(screen.getByText('active')).toBeInTheDocument();
        expect(screen.getByText('error')).toBeInTheDocument();
        expect(screen.getByText('1,2 rb rekaman · Hari ini')).toBeInTheDocument();
    });

    it('shows the empty state when no sources exist', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                const url = String(input);

                if (url.includes('/public-data/sources')) {
                    return new Response(JSON.stringify({ success: true, message: 'Ok', data: [] }), {
                        status: 200,
                        headers: { 'Content-Type': 'application/json' },
                    });
                }

                return new Response(JSON.stringify({ success: true, message: 'Ok', data: categoriesFixture }), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' },
                });
            }),
        );

        renderPage();

        expect(await screen.findByText('Belum ada sumber data')).toBeInTheDocument();
    });
});