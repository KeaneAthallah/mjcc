import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { DashboardPage } from './DashboardPage';
import { RealtimeProvider } from '../lib/realtime';
import type { DashboardOverview } from '../types/dashboard';

vi.mock('react-chartjs-2', () => ({
    Bar: () => <div data-testid="chart-bar" />,
    Doughnut: () => <div data-testid="chart-doughnut" />,
    PolarArea: () => <div data-testid="chart-polar" />,
}));

function overviewFixture(): DashboardOverview {
    return {
        stats: {
            kecamatan: 2,
            kelurahan: 4,
            population: 245000,
            total_sekolah: 12,
            sekolah_baik: 9,
            total_sd: 8,
            total_smp: 4,
            total_siswa: 3000,
            total_guru: 150,
            total_polsek: 2,
            total_tipkamtikmas: 5,
            total_poskamling: 20,
            poskamling_aktif: 18,
            total_pasar: 3,
            total_faskes: 6,
            faskes_aktif: 5,
            total_puskesmas: 2,
            total_rs: 1,
            total_pustu: 1,
            total_posyandu: 2,
            total_dokter: 10,
            total_perawat: 20,
            total_bidan: 15,
            total_bed: 40,
        },
        comparison: {
            labels: ['Bungku'],
            datasets: [
                { label: 'Sekolah', data: [12] },
                { label: 'Tipkamtikmas', data: [5] },
                { label: 'Fasilitas Kesehatan', data: [6] },
            ],
        },
        infra_composition: { labels: ['Pendidikan', 'Ketertiban', 'Kesehatan'], data: [12, 25, 6] },
        student_chart: {
            labels: ['Bungku'],
            datasets: [
                { label: 'Laki-laki', data: [1600] },
                { label: 'Perempuan', data: [1400] },
            ],
        },
        health_workforce_chart: { labels: ['Dokter', 'Perawat', 'Bidan'], data: [10, 20, 15] },
        top_schools: [{ kecamatan_id: 1, name: 'Bungku', count: 12 }],
        top_poskamling: [{ kecamatan_id: 1, name: 'Bungku', count: 18 }],
        top_health: [{ kecamatan_id: 1, name: 'Bungku', count: 45 }],
        per_kecamatan: [
            {
                kecamatan_id: 1,
                name: 'Bungku',
                schools: 12,
                tipkamtikmas: 5,
                health_facilities: 6,
                kelurahans: 4,
                poskamlings: 20,
                markets: 3,
            },
        ],
        alerts: {
            critical: [
                {
                    rule: 'faskes_tanpa_tenaga_medis',
                    severity: 'critical',
                    sector: 'Kesehatan',
                    sector_key: 'kesehatan',
                    title: 'Faskes tanpa tenaga medis',
                    detail: 'Puskesmas Tanpa Dokter tidak memiliki dokter, perawat, maupun bidan.',
                    resource_type: 'health_facility',
                    resource_class: null,
                    resource_id: 1,
                    kecamatan_id: 1,
                    kecamatan_name: 'Bungku',
                    latitude: -1.5,
                    longitude: 122.4,
                    detail_route: 'health.facilities.show',
                    detail_params: { id: 1 },
                    created_at: '2026-01-01 08:00:00',
                },
            ],
            warning: [],
            info: [],
        },
    };
}

function renderPage(): void {
    const queryClient = new QueryClient({
        defaultOptions: { queries: { retry: false } },
    });

    render(
        <MemoryRouter>
            <QueryClientProvider client={queryClient}>
                <RealtimeProvider>
                    <DashboardPage />
                </RealtimeProvider>
            </QueryClientProvider>
        </MemoryRouter>,
    );
}

beforeEach(() => {
    localStorage.clear();
    vi.stubGlobal(
        'fetch',
        vi.fn(async () =>
            new Response(JSON.stringify({ success: true, message: 'Ok', data: overviewFixture() }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' },
            }),
        ),
    );
});

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('DashboardPage', () => {
    it('renders KPI metrics, alert, rankings and the per-kecamatan table', async () => {
        renderPage();

        expect(await screen.findByText('Total Sekolah')).toBeInTheDocument();
        expect(screen.getAllByText('12').some((el) => el.classList.contains('tabular-nums'))).toBe(true);
        expect(screen.getByText('245.000', { exact: false })).toBeInTheDocument();
        expect(screen.getByText('Faskes tanpa tenaga medis')).toBeInTheDocument();

        expect(screen.getByText('Top Kecamatan · Sekolah')).toBeInTheDocument();
        expect(screen.getByText('Top Kecamatan · Poskamling')).toBeInTheDocument();
        expect(screen.getByText('Top Kecamatan · Tenaga Medis')).toBeInTheDocument();

        expect(screen.getByRole('table')).toBeInTheDocument();
        expect(screen.getAllByText('Kecamatan').length).toBeGreaterThan(0);
        expect(screen.getByText('🎓 Pendidikan')).toBeInTheDocument();
    });

    it('shows the all-clear state when there are no alerts', async () => {
        const fixture = overviewFixture();
        fixture.alerts = { critical: [], warning: [], info: [] };
        vi.stubGlobal(
            'fetch',
            vi.fn(async () =>
                new Response(JSON.stringify({ success: true, message: 'Ok', data: fixture }), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' },
                }),
            ),
        );

        renderPage();

        expect(await screen.findByText('Semua aman')).toBeInTheDocument();
    });

    it('renders an error state with a retry button on failure', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () =>
                new Response(JSON.stringify({ success: false, message: 'Server bermasalah.', errors: null }), {
                    status: 500,
                    headers: { 'Content-Type': 'application/json' },
                }),
            ),
        );

        renderPage();

        expect(await screen.findByText('Coba Lagi')).toBeInTheDocument();
    });
});