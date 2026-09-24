import { useState } from 'react';
import { Bar, Doughnut } from 'react-chartjs-2';
import type { ChartData, ChartOptions } from 'chart.js';
import { useDomainDashboard } from '../hooks/useDomainDashboard';
import { formatNumber } from '../lib/format';
import { Badge } from '../components/Badge';
import { Card } from '../components/Card';
import { ChartCard } from '../components/ChartCard';
import { MetricCard } from '../components/MetricCard';
import { DashboardError } from '../components/DashboardError';
import { DashboardHeader } from '../components/DashboardHeader';
import { DashboardSkeleton } from '../components/DashboardSkeleton';
import type { SecurityDashboard } from '../types/dashboards';

export function SecurityDashboardPage(): React.JSX.Element {
    const [kecamatanId, setKecamatanId] = useState<number | null>(null);
    const { data, isPending, isError, refetch } = useDomainDashboard<SecurityDashboard>('security', kecamatanId);

    return (
        <div className="space-y-5 p-5 page-transition">
            <DashboardHeader
                title="Dashboard Ketertiban"
                subtitle="Pemantauan poskamling, polsek, dan kamtibmas"
                kecamatanId={kecamatanId}
                onKecamatanChange={setKecamatanId}
                onReload={() => void refetch()}
            />

            {isPending ? (
                <DashboardSkeleton cards={5} />
            ) : isError || !data ? (
                <DashboardError onRetry={() => void refetch()} />
            ) : (
                <>
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                        <MetricCard label="Kelurahan / Desa" value={formatNumber(data.statistics.kelurahan)} icon="🏘️" tone="teal" />
                        <MetricCard label="Polsek" value={formatNumber(data.statistics.polsek)} icon="🚓" tone="amber" />
                        <MetricCard label="Tipkamtikmas" value={formatNumber(data.statistics.tipkamtikmas)} icon="🪖" tone="green" />
                        <MetricCard
                            label="Poskamling Aktif"
                            value={formatNumber(data.statistics.poskamling)}
                            icon="🛡️"
                            tone="blue"
                        />
                        <MetricCard label="Pasar" value={formatNumber(data.statistics.pasar)} icon="🏪" tone="violet" />
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        <ChartCard title="Tipkamtikmas & Poskamling" subtitle="Per kecamatan" icon="📊" className="lg:col-span-2">
                            <Bar data={data.compare_chart as ChartData<'bar'>} options={stackedOptions()} />
                        </ChartCard>
                        <ChartCard title="Distribusi Poskamling" subtitle="Poskamling aktif per kecamatan" icon="🛡️">
                            <Doughnut
                                data={{
                                    labels: data.poskamling_distribution.labels,
                                    datasets: [{ data: data.poskamling_distribution.data, backgroundColor: ['#10b981', '#3b82f6', '#fbbf24', '#8b5cf6', '#14b8a6', '#f43f5e'] }],
                                }}
                                options={{ responsive: true, maintainAspectRatio: false }}
                            />
                        </ChartCard>
                    </div>

                    <ChartCard title="Kelurahan per Kecamatan" subtitle="Jumlah kelurahan/desa tiap kecamatan" icon="🏘️">
                        <Bar
                            data={{ labels: data.kelurahan_per_kecamatan.labels, datasets: [{ label: 'Kelurahan', data: data.kelurahan_per_kecamatan.data, backgroundColor: 'rgba(59,130,246,0.8)' }] }}
                            options={{ indexAxis: 'y', responsive: true, maintainAspectRatio: false }}
                        />
                    </ChartCard>

                    <Card title="Daftar Polsek" subtitle="Lokasi dan status" icon="🚓">
                        <div className="overflow-x-auto -mx-2">
                            <table className="w-full text-left text-[12px] min-w-[560px]">
                                <thead>
                                    <tr className="text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                                        <th className="px-2 py-2 font-bold">Polsek</th>
                                        <th className="px-2 py-2 font-bold">Kecamatan</th>
                                        <th className="px-2 py-2 font-bold text-right">Personel</th>
                                        <th className="px-2 py-2 font-bold text-right">Poskamling</th>
                                        <th className="px-2 py-2 font-bold">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {data.polseks.map((polsek) => (
                                        <tr key={polsek.id} className="hover:bg-gray-50">
                                            <td className="px-2 py-2 font-semibold text-gray-800">{polsek.name}</td>
                                            <td className="px-2 py-2">{polsek.kecamatan?.name ?? '-'}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(polsek.personnel_count ?? 0)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(polsek.poskamling_count ?? 0)}</td>
                                            <td className="px-2 py-2">
                                                <StatusBadge status={polsek.status} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </>
            )}
        </div>
    );
}

function stackedOptions(): ChartOptions<'bar'> {
    return {
        responsive: true,
        maintainAspectRatio: false,
        scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
    };
}

function StatusBadge({ status }: { status?: string | null }): React.JSX.Element {
    if (!status) {
        return <Badge color="gray">belum diisi</Badge>;
    }

    const color = status.toLowerCase() === 'nonaktif' ? 'red' : 'green';

    return <Badge color={color}>{status}</Badge>;
}