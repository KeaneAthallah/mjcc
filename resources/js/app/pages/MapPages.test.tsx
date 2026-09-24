import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { MapPage } from './MapPage';
import { KecamatanIntelligencePage } from './KecamatanIntelligencePage';

const marker = (id: number, type: string, name: string, kecamatan: string, lat: number, lng: number): Record<string, unknown> => ({
    id,
    type,
    sector: type === 'health_facility' ? 'kesehatan' : 'ketertiban',
    name,
    latitude: lat,
    longitude: lng,
    status: 'aktif',
    kecamatan,
    kelurahan: null,
});

const mapsFixture = {
    markers: [
        marker(1, 'school', 'SDN Bungku', 'Bungku', -2.5, 121.5),
        marker(2, 'health_facility', 'Puskesmas Menui', 'Menui', -2.6, 121.9),
        marker(3, 'polsek', 'Polsek Bahodopi', 'Bahodopi', -2.2, 121.1),
    ],
    kecamatans: [
        { id: 1, name: 'Bahodopi' },
        { id: 2, name: 'Bungku' },
        { id: 3, name: 'Menui' },
    ],
};

beforeEach(() => {
    localStorage.clear();
    vi.stubGlobal(
        'fetch',
        vi.fn(async (input: RequestInfo | URL) => {
            const url = String(input);

            if (url.includes('/maps')) {
                return new Response(JSON.stringify({ success: true, message: 'Ok', data: mapsFixture }), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' },
                });
            }

            if (url.includes('/kecamatans')) {
                return new Response(JSON.stringify({ success: true, message: 'Ok', data: mapsFixture.kecamatans }), {
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

function renderPage(page: React.JSX.Element): void {
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

    render(
        <MemoryRouter>
            <QueryClientProvider client={queryClient}>{page}</QueryClientProvider>
        </MemoryRouter>,
    );
}

describe('MapPage', () => {
    it('renders marker counts with type labels and the kecamatan filter', async () => {
        renderPage(<MapPage />);

        expect(await screen.findByText('Peta Gabungan')).toBeInTheDocument();
        expect(await screen.findByText('3 titik')).toBeInTheDocument();
        expect(screen.getByText('Sekolah 1')).toBeInTheDocument();
        expect(screen.getByText('Faskes 1')).toBeInTheDocument();
        expect(screen.getByText('Polsek 1')).toBeInTheDocument();
        expect(await screen.findByLabelText('Filter kecamatan')).toBeInTheDocument();
        expect(screen.getByText('Bahodopi')).toBeInTheDocument();
    });
});

describe('KecamatanIntelligencePage', () => {
    it('lists per-kecamatan summaries sorted by marker count', async () => {
        renderPage(<KecamatanIntelligencePage />);

        expect(await screen.findByText('Intelijen Kecamatan')).toBeInTheDocument();
        expect(await screen.findByText('Bungku')).toBeInTheDocument();
        expect(screen.getByText('Menui')).toBeInTheDocument();
        expect(screen.getByText('3 titik')).toBeInTheDocument();
        expect(screen.getByText('Sekolah 1')).toBeInTheDocument();
    });
});