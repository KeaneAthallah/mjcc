import { useState } from 'react';
import { Bar, Doughnut } from 'react-chartjs-2';
import type { ChartData } from 'chart.js';
import { useDomainDashboard } from '../hooks/useDomainDashboard';
import { formatNumber } from '../lib/format';
import { Card } from '../components/Card';
import { ChartCard } from '../components/ChartCard';
import { MetricCard } from '../components/MetricCard';
import { DashboardError } from '../components/DashboardError';
import { DashboardHeader } from '../components/DashboardHeader';
import { DashboardSkeleton } from '../components/DashboardSkeleton';
import type { HealthDashboard } from '../types/dashboards';

export function HealthDashboardPage(): React.JSX.Element {
    const [kecamatanId, setKecamatanId] = useState<number | null>(null);
    const { data, isPending, isError, refetch } = useDomainDashboard<HealthDashboard>('health', kecamatanId);

    return (
        <div className="space-y-5 p-5 page-transition">
            <DashboardHeader
                title="Dashboard Kesehatan"
                subtitle="Pemantauan fasilitas dan tenaga kesehatan"
                kecamatanId={kecamatanId}
                onKecamatanChange={setKecamatanId}
                onReload={() => void refetch()}
            />

            {isPending ? (
                <DashboardSkeleton cards={8} />
            ) : isError || !data ? (
                <DashboardError onRetry={() => void refetch()} />
            ) : (
                <>
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                        <MetricCard label="Puskesmas" value={formatNumber(data.statistics.puskesmas)} icon="🏥" tone="green" />
                        <MetricCard label="Pustu" value={formatNumber(data.statistics.pustu)} icon="🏥" tone="blue" />
                        <MetricCard label="Rumah Sakit" value={formatNumber(data.statistics.rs)} icon="🏨" tone="red" />
                        <MetricCard label="Posyandu" value={formatNumber(data.statistics.posyandu)} icon="🤱" tone="amber" />
                        <MetricCard label="Tenaga Kesehatan" value={formatNumber(data.statistics.dokter + data.statistics.perawat + data.statistics.bidan)} icon="🩺" tone="teal" />
                        <MetricCard label="Tempat Tidur" value={formatNumber(data.statistics.bed)} icon="🛏️" tone="violet" />
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        <ChartCard title="Tenaga Kesehatan per Kecamatan" subtitle="Dokter · Perawat · Bidan" icon="👩‍⚕️" className="lg:col-span-2">
                            <Bar data={data.workforce_per_kecamatan as ChartData<'bar'>} options={{ responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }} />
                        </ChartCard>
                        <ChartCard title="Komposisi Fasilitas" subtitle="Puskesmas · Pustu · RS · Posyandu" icon="🏥">
                            <Doughnut
                                data={{
                                    labels: data.facility_proportion.labels,
                                    datasets: [{ data: data.facility_proportion.data, backgroundColor: ['#10b981', '#3b82f6', '#f43f5e', '#fbbf24'] }],
                                }}
                                options={{ responsive: true, maintainAspectRatio: false }}
                            />
                        </ChartCard>
                    </div>

                    <ChartCard title="Kapasitas Tempat Tidur" subtitle="Bed per kecamatan" icon="🛏️">
                        <Bar
                            data={{ labels: data.capacity_per_kecamatan.labels, datasets: [{ label: 'Bed', data: data.capacity_per_kecamatan.data, backgroundColor: 'rgba(139,92,246,0.8)' }] }}
                            options={{ responsive: true, maintainAspectRatio: false }}
                        />
                    </ChartCard>

                    <Card title="Rekap Kesehatan per Kecamatan" subtitle="Fasilitas, tenaga, dan kapasitas bed" icon="🗂️">
                        <div className="overflow-x-auto -mx-2">
                            <table className="w-full text-left text-[12px] min-w-[640px]">
                                <thead>
                                    <tr className="text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                                        <th className="px-2 py-2 font-bold">Kecamatan</th>
                                        <th className="px-2 py-2 font-bold text-right">Puskesmas</th>
                                        <th className="px-2 py-2 font-bold text-right">Pustu</th>
                                        <th className="px-2 py-2 font-bold text-right">RS</th>
                                        <th className="px-2 py-2 font-bold text-right">Posyandu</th>
                                        <th className="px-2 py-2 font-bold text-right">Dokter</th>
                                        <th className="px-2 py-2 font-bold text-right">Perawat</th>
                                        <th className="px-2 py-2 font-bold text-right">Bidan</th>
                                        <th className="px-2 py-2 font-bold text-right">Bed</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {data.table.map((row) => (
                                        <tr key={row.id} className="hover:bg-gray-50">
                                            <td className="px-2 py-2 font-semibold text-gray-800">{row.name}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.puskesmas_count)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.pustu_count)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.rs_count)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.posyandu_count)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.dok)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.per)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.bid)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.bed)}</td>
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