import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { SearchPage } from './SearchPage';

const searchFixture = {
    query: 'SMP',
    results: [
        { id: 1, type: 'Sekolah', slug: 'smp-negeri-1', title: 'SMP Negeri 1', subtitle: 'NPSN 1234', kecamatan_name: 'Bungku', icon: '🎓' },
        { id: 2, type: 'Sekolah', slug: 'smp-negeri-2', title: 'SMP Negeri 2', subtitle: 'NPSN 1235', kecamatan_name: 'Menui', icon: '🎓' },
    ],
    suggestions: ['SMP Negeri', 'Puskesmas'],
};

beforeEach(() => {
    localStorage.clear();
    vi.stubGlobal(
        'fetch',
        vi.fn(async (input: RequestInfo | URL) => {
            const url = String(input);

            if (url.includes('/search?')) {
                return new Response(JSON.stringify({ success: true, message: 'Ok', data: searchFixture }), {
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
});

function renderPage(): void {
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

    render(
        <MemoryRouter initialEntries={['/search']}>
            <QueryClientProvider client={queryClient}>
                <Routes>
                    <Route path="/search" element={<SearchPage />} />
                    <Route path="/peta" element={<div>Peta Gabungan</div>} />
                </Routes>
            </QueryClientProvider>
        </MemoryRouter>,
    );
}

describe('SearchPage', () => {
    it('runs a search and groups results by type', async () => {
        renderPage();

        await userEvent.type(screen.getByLabelText('Kata kunci pencarian'), 'SMP');
        await userEvent.click(screen.getByRole('button', { name: '🔎 Cari' }));

        expect(await screen.findByText('SMP Negeri 1')).toBeInTheDocument();
        expect(screen.getByText('SMP Negeri 2')).toBeInTheDocument();
        expect(screen.getByText('Saran:')).toBeInTheDocument();
        expect(screen.getByText('SMP Negeri')).toBeInTheDocument();
        expect(await screen.findByText('Sekolah')).toBeInTheDocument();
        expect(screen.getAllByText('2')).toHaveLength(2);
    });

    it('shows the empty state when a search yields nothing', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                const url = String(input);

                if (url.includes('/search?')) {
                    return new Response(
                        JSON.stringify({ success: true, message: 'Ok', data: { query: 'xyz', results: [], suggestions: [] } }),
                        { status: 200, headers: { 'Content-Type': 'application/json' } },
                    );
                }

                return new Response(JSON.stringify({ success: true, message: 'Ok', data: [] }), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' },
                });
            }),
        );

        renderPage();

        await userEvent.type(screen.getByLabelText('Kata kunci pencarian'), 'xyz');
        await userEvent.click(screen.getByRole('button', { name: '🔎 Cari' }));

        expect(await screen.findByText('Tidak ditemukan')).toBeInTheDocument();
    });
});