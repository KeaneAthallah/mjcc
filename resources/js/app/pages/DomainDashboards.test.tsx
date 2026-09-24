import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { RealtimeProvider } from '../lib/realtime';
import { EducationDashboardPage } from './EducationDashboardPage';
import { SecurityDashboardPage } from './SecurityDashboardPage';
import { HealthDashboardPage } from './HealthDashboardPage';
import type { EducationDashboard, HealthDashboard, SecurityDashboard } from '../types/dashboards';

vi.mock('react-chartjs-2', () => ({
    Bar: () => <div data-testid="chart-bar" />,
    Doughnut: () => <div data-testid="chart-doughnut" />,
}));

function jsonResponse<T>(data: T): Response {
    return new Response(JSON.stringify({ success: true, message: 'Ok', data }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
    });
}

const educationFixture: EducationDashboard = {
    statistics: { total_sd: 8, total_smp: 4, siswa_laki: 1600, siswa_perempuan: 1400, guru: 150, kelas: 60, mapel: 12 },
    student_per_kecamatan: {
        labels: ['Bungku'],
        datasets: [
            { label: 'Siswa Laki-laki', data: [1600] },
            { label: 'Siswa Perempuan', data: [1400] },
        ],
    },
    teacher_ratio: { labels: ['Bungku'], data: [150] },
    facility_progress: [
        { name: 'Perpustakaan', pct: 80, meta: 'library_percentage' },
        { name: 'Laboratorium IPA', pct: 50, meta: 'science_lab_percentage' },
    ],
    table: [
        { id: 1, name: 'Bungku', sd_count: 8, smp_count: 4, siswa_l: 1600, siswa_p: 1400, guru: 150, kelas: 60, kapasitas: 5000 },
    ],
};

const securityFixture: SecurityDashboard = {
    statistics: { kelurahan: 4, polsek: 2, tipkamtikmas: 5, poskamling: 18, pasar: 3 },
    compare_chart: {
        labels: ['Bungku'],
        datasets: [
            { label: 'Tipkamtikmas', data: [5] },
            { label: 'Poskamling Aktif', data: [18] },
        ],
    },
    poskamling_distribution: { labels: ['Bungku'], data: [18] },
    kelurahan_per_kecamatan: { labels: ['Bungku'], data: [4] },
    polseks: [{ id: 1, name: 'Polsek Bungku', status: 'aktif', personnel_count: 25, poskamling_count: 9, kecamatan: { id: 1, name: 'Bungku' } }],
};

const healthFixture: HealthDashboard = {
    statistics: { puskesmas: 2, pustu: 1, rs: 1, posyandu: 4, dokter: 10, perawat: 20, bidan: 15, bed: 40 },
    workforce_per_kecamatan: {
        labels: ['Bungku'],
        datasets: [
            { label: 'Dokter', data: [10] },
            { label: 'Perawat', data: [20] },
            { label: 'Bidan', data: [15] },
        ],
    },
    facility_proportion: { labels: ['Puskesmas', 'Pustu', 'Rumah Sakit', 'Posyandu'], data: [2, 1, 1, 4] },
    capacity_per_kecamatan: { labels: ['Bungku'], data: [40] },
    table: [{ id: 1, name: 'Bungku', puskesmas_count: 2, pustu_count: 1, rs_count: 1, posyandu_count: 4, dok: 10, per: 20, bid: 15, bed: 40 }],
};

beforeEach(() => {
    localStorage.clear();
});

afterEach(() => {
    vi.unstubAllGlobals();
});

function renderWith(queryClient: QueryClient, page: React.ReactNode): void {
    render(
        <MemoryRouter>
            <QueryClientProvider client={queryClient}>
                <RealtimeProvider>{page}</RealtimeProvider>
            </QueryClientProvider>
        </MemoryRouter>,
    );
}

function renderPage(page: React.ReactNode): void {
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    renderWith(queryClient, page);
}

describe('domain dashboards', () => {
    it.each([
        ['Pendidikan', 'Dashboard Pendidikan', '/dashboard/education', educationFixture, 'Rekap Pendidikan per Kecamatan'],
        ['Ketertiban', 'Dashboard Ketertiban', '/dashboard/security', securityFixture, 'Daftar Polsek'],
        ['Kesehatan', 'Dashboard Kesehatan', '/dashboard/health', healthFixture, 'Rekap Kesehatan per Kecamatan'],
    ] as const)('%s page renders KPIs and the summary table', async (_label, title, endpoint, fixture, tableTitle) => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                const url = String(input);

                return url.includes('/dashboard/') ? jsonResponse(fixture) : jsonResponse([{ id: 1, name: 'Bungku' }]);
            }),
        );

        const pages = {
            '/dashboard/education': <EducationDashboardPage />,
            '/dashboard/security': <SecurityDashboardPage />,
            '/dashboard/health': <HealthDashboardPage />,
        } as Record<string, React.ReactNode>;

        renderPage(pages[endpoint]);

        expect(await screen.findByText(title)).toBeInTheDocument();
        expect(screen.getByText('Semua Kecamatan')).toBeInTheDocument();
        expect(await screen.findByText(tableTitle)).toBeInTheDocument();
    });

    it('education page renders facility progress percentages', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) =>
                String(input).includes('/dashboard/') ? jsonResponse(educationFixture) : jsonResponse([{ id: 1, name: 'Bungku' }]),
            ),
        );
        renderPage(<EducationDashboardPage />);

        expect(await screen.findByText('Progres Fasilitas Sekolah')).toBeInTheDocument();
        expect(screen.getByText('Perpustakaan')).toBeInTheDocument();
    });

    it('renders an error state with retry when the API fails', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                const url = String(input);

                if (url.includes('/dashboard/')) {
                    return new Response(JSON.stringify({ success: false, message: 'Server bermasalah.', errors: null }), {
                        status: 500,
                        headers: { 'Content-Type': 'application/json' },
                    });
                }

                return jsonResponse([{ id: 1, name: 'Bungku' }]);
            }),
        );

        renderPage(<HealthDashboardPage />);

        expect(await screen.findByText('Coba Lagi')).toBeInTheDocument();
    });
});