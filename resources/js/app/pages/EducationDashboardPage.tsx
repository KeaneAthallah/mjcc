import { useState } from 'react';
import { Bar, Doughnut } from 'react-chartjs-2';
import type { ChartData } from 'chart.js';
import { useDomainDashboard } from '../hooks/useDomainDashboard';
import { formatNumber } from '../lib/format';
import { Badge } from '../components/Badge';
import { Card } from '../components/Card';
import { ChartCard } from '../components/ChartCard';
import { MetricCard } from '../components/MetricCard';
import { DashboardError } from '../components/DashboardError';
import { DashboardHeader } from '../components/DashboardHeader';
import { DashboardSkeleton } from '../components/DashboardSkeleton';
import type { EducationDashboard } from '../types/dashboards';

export function EducationDashboardPage(): React.JSX.Element {
    const [kecamatanId, setKecamatanId] = useState<number | null>(null);
    const { data, isPending, isError, refetch } = useDomainDashboard<EducationDashboard>('education', kecamatanId);

    return (
        <div className="space-y-5 p-5 page-transition">
            <DashboardHeader
                title="Dashboard Pendidikan"
                subtitle="Pemantauan sekolah, siswa, dan tenaga pendidik"
                kecamatanId={kecamatanId}
                onKecamatanChange={setKecamatanId}
                onReload={() => void refetch()}
            />

            {isPending ? (
                <DashboardSkeleton cards={6} />
            ) : isError || !data ? (
                <DashboardError onRetry={() => void refetch()} />
            ) : (
                <>
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                        <MetricCard label="SD" value={formatNumber(data.statistics.total_sd)} icon="🏫" tone="green" />
                        <MetricCard label="SMP" value={formatNumber(data.statistics.total_smp)} icon="🏫" tone="blue" />
                        <MetricCard label="Siswa Laki-laki" value={formatNumber(data.statistics.siswa_laki)} icon="👦" tone="emerald" />
                        <MetricCard label="Siswa Perempuan" value={formatNumber(data.statistics.siswa_perempuan)} icon="👧" tone="teal" />
                        <MetricCard label="Guru" value={formatNumber(data.statistics.guru)} icon="👨‍🏫" tone="blue" />
                        <MetricCard label="Kelas" value={formatNumber(data.statistics.kelas)} icon="📚" tone="violet" />
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        <ChartCard title="Siswa per Kecamatan" subtitle="Laki-laki & Perempuan" icon="🎓" className="lg:col-span-2">
                            <StudentBar data={data.student_per_kecamatan} />
                        </ChartCard>
                        <ChartCard title="Rasio Siswa per Kecamatan" subtitle="Distribusi jumlah guru" icon="👩‍🏫">
                            <Doughnut
                                data={{
                                    labels: data.teacher_ratio.labels,
                                    datasets: [{ data: data.teacher_ratio.data, backgroundColor: ['#10b981', '#3b82f6', '#fbbf24', '#8b5cf6', '#14b8a6', '#f43f5e'] }],
                                }}
                                options={{ responsive: true, maintainAspectRatio: false }}
                            />
                        </ChartCard>
                    </div>

                    <Card title="Progres Fasilitas Sekolah" subtitle="Rata-rata kelengkapan fasilitas" icon="🛠️">
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            {data.facility_progress.map((row) => (
                                <div key={row.meta}>
                                    <div className="flex items-center justify-between text-[12px]">
                                        <span className="font-semibold text-gray-700">{row.name}</span>
                                        <Badge color="green">{row.pct}%</Badge>
                                    </div>
                                    <div className="mt-1.5 h-2.5 rounded-full bg-gray-100 overflow-hidden">
                                        <div
                                            className="h-full rounded-full bg-emerald-500 transition-all"
                                            style={{ width: `${Math.min(100, Math.max(0, row.pct))}%` }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>

                    <Card title="Rekap Pendidikan per Kecamatan" subtitle="Sekolah, siswa, guru, dan kapasitas" icon="🗂️">
                        <div className="overflow-x-auto -mx-2">
                            <table className="w-full text-left text-[12px] min-w-[640px]">
                                <thead>
                                    <tr className="text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                                        <th className="px-2 py-2 font-bold">Kecamatan</th>
                                        <th className="px-2 py-2 font-bold text-right">SD</th>
                                        <th className="px-2 py-2 font-bold text-right">SMP</th>
                                        <th className="px-2 py-2 font-bold text-right">Siswa (L/P)</th>
                                        <th className="px-2 py-2 font-bold text-right">Guru</th>
                                        <th className="px-2 py-2 font-bold text-right">Kelas</th>
                                        <th className="px-2 py-2 font-bold text-right">Kapasitas</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {data.table.map((row) => (
                                        <tr key={row.id} className="hover:bg-gray-50">
                                            <td className="px-2 py-2 font-semibold text-gray-800">{row.name}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.sd_count)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.smp_count)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">
                                                {formatNumber(row.siswa_l)} / {formatNumber(row.siswa_p)}
                                            </td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.guru)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.kelas)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.kapasitas)}</td>
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

function StudentBar({
    data,
}: {
    data: { labels: string[]; datasets: { label: string; data: number[]; backgroundColor?: string | string[]; borderColor?: string; borderWidth?: number }[] };
}): React.JSX.Element {
    return <Bar data={data as ChartData<'bar'>} options={{ responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }} />;
}