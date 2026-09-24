import { Bar, Doughnut, PolarArea } from 'react-chartjs-2';
import type { ChartData, ChartOptions } from 'chart.js';
import { Link } from 'react-router-dom';
import { useDashboardOverview } from '../hooks/useDashboardOverview';
import { INFRA_PALETTE, stackedBarOptions, WORKFORCE_PALETTE } from '../lib/charts';
import { formatNumber } from '../lib/format';
import { Badge } from '../components/Badge';
import { Card } from '../components/Card';
import { ChartCard } from '../components/ChartCard';
import { MetricCard } from '../components/MetricCard';
import { AlertItem } from '../components/AlertItem';
import { getStoredUser } from '../lib/auth';
import { useRealtime } from '../lib/realtime';

const SECTOR_LINKS = [
    { to: '/pendidikan', label: '🎓 Pendidikan', color: 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' },
    { to: '/ketertiban', label: '🛡️ Ketertiban', color: 'bg-blue-100 text-blue-800 hover:bg-blue-200' },
    { to: '/kesehatan', label: '🏥 Kesehatan', color: 'bg-red-100 text-red-800 hover:bg-red-200' },
];

function comparisonChartData(data: {
    labels: string[];
    datasets: { label: string; data: number[]; backgroundColor?: string | string[]; borderColor?: string; borderWidth?: number }[];
}): ChartData<'bar'> {
    return data as ChartData<'bar'>;
}

function StudentCountChart({ labels, datasets }: { labels: string[]; datasets: ChartData<'bar'>['datasets'] }): React.JSX.Element {
    const data: ChartData<'bar'> = { labels, datasets };
    return <Bar data={data} options={stackedBarOptions()} />;
}

export function DashboardPage(): React.JSX.Element {
    const { data, isPending, isError, refetch } = useDashboardOverview();
    const { connection } = useRealtime();
    const user = getStoredUser();

    return (
        <div className="space-y-5 p-5 page-transition">
            <div className="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 className="text-xl sm:text-2xl font-extrabold text-gray-900">
                        Selamat Datang, {user?.name ?? 'Pengguna'} 👋
                    </h1>
                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-[12px] text-gray-500">
                        {data ? (
                            <>
                                <span>
                                    📊 Total penduduk:{' '}
                                    <strong className="text-gray-800">{formatNumber(data.stats.population)} jiwa</strong>
                                </span>
                                <span>🏙️ {data.stats.kecamatan} Kecamatan</span>
                                <span>🏘️ {data.stats.kelurahan} Kelurahan/Desa</span>
                            </>
                        ) : (
                            <span className="text-gray-400">Memuat ringkasan…</span>
                        )}
                    </div>
                </div>
                <div className="flex items-center gap-2 flex-wrap">
                    <button
                        type="button"
                        onClick={() => void refetch()}
                        className="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-[12px] font-bold bg-white text-gray-600 border-gray-300 hover:bg-gray-50 transition"
                        title="Muat ulang data dashboard"
                    >
                        🔄 Muat Ulang
                    </button>
                    {SECTOR_LINKS.map((link) => (
                        <Link
                            key={link.to}
                            to={link.to}
                            className={`px-4 py-2 rounded-xl text-[12px] font-bold transition ${link.color}`}
                        >
                            {link.label}
                        </Link>
                    ))}
                </div>
            </div>

            {isPending ? (
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                    {Array.from({ length: 10 }).map((_, index) => (
                        <div key={index} className="rounded-xl bg-white border border-gray-200 p-4 h-28 animate-pulse" />
                    ))}
                </div>
            ) : isError || !data ? (
                <div className="rounded-2xl bg-white border border-gray-200 p-10 text-center">
                    <div className="text-4xl mb-2">⚠️</div>
                    <h3 className="text-[14px] font-extrabold text-gray-700">Gagal memuat dashboard</h3>
                    <p className="text-[12px] text-gray-400 mt-1">
                        Periksa koneksi Anda{connection === 'connecting' ? ' dan server realtime' : ''} lalu coba lagi.
                    </p>
                    <button
                        type="button"
                        onClick={() => void refetch()}
                        className="mt-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[12px] font-bold px-4 py-2 transition"
                    >
                        Coba Lagi
                    </button>
                </div>
            ) : (
                <>
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                        <MetricCard
                            label="Total Sekolah"
                            value={formatNumber(data.stats.total_sekolah)}
                            icon="🏫"
                            tone="green"
                            footer={`SD ${formatNumber(data.stats.total_sd)} · SMP ${formatNumber(data.stats.total_smp)} · Baik ${formatNumber(data.stats.sekolah_baik)}`}
                        />
                        <MetricCard
                            label="Total Siswa"
                            value={formatNumber(data.stats.total_siswa)}
                            icon="🎓"
                            tone="blue"
                            footer={`Guru: ${formatNumber(data.stats.total_guru)}`}
                        />
                        <MetricCard
                            label="Fasilitas Kesehatan"
                            value={formatNumber(data.stats.total_faskes)}
                            icon="🏥"
                            tone="green"
                            footer={`Aktif ${formatNumber(data.stats.faskes_aktif)}`}
                        />
                        <MetricCard
                            label="Tenaga Kesehatan"
                            value={formatNumber(data.stats.total_dokter + data.stats.total_perawat + data.stats.total_bidan)}
                            icon="🩺"
                            tone="blue"
                            footer={`Dokter ${formatNumber(data.stats.total_dokter)} · Perawat ${formatNumber(data.stats.total_perawat)} · Bidan ${formatNumber(data.stats.total_bidan)}`}
                        />
                        <MetricCard
                            label="Total Poskamling"
                            value={formatNumber(data.stats.total_poskamling)}
                            icon="🛡️"
                            tone={data.stats.poskamling_aktif < data.stats.total_poskamling ? 'red' : 'green'}
                            footer={`Aktif ${formatNumber(data.stats.poskamling_aktif)} · Nonaktif ${formatNumber(data.stats.total_poskamling - data.stats.poskamling_aktif)}`}
                        />
                        <MetricCard label="Total Polsek" value={formatNumber(data.stats.total_polsek)} icon="🚓" tone="amber" />
                        <MetricCard label="Kecamatan" value={formatNumber(data.stats.kecamatan)} icon="🏙️" tone="emerald" />
                        <MetricCard label="Kelurahan / Desa" value={formatNumber(data.stats.kelurahan)} icon="🏘️" tone="teal" />
                        <MetricCard label="Pasar" value={formatNumber(data.stats.total_pasar)} icon="🏪" tone="violet" />
                        <MetricCard
                            label="Tempat Tidur Faskes"
                            value={formatNumber(data.stats.total_bed)}
                            icon="🛏️"
                            tone="blue"
                        />
                    </div>

                    <Card
                        title="Perlu Perhatian"
                        subtitle="Masalah terdeteksi dari data nyata — urut sesuai tingkat keparahan"
                        icon="⚠️"
                        actions={
                            <div className="flex items-center gap-1.5">
                                <Badge color="red">{data.alerts.critical.length}</Badge>
                                <Badge color="amber">{data.alerts.warning.length}</Badge>
                                <Badge color="blue">{data.alerts.info.length}</Badge>
                            </div>
                        }
                    >
                        {data.alerts.critical.length + data.alerts.warning.length + data.alerts.info.length > 0 ? (
                            <>
                                <div className="divide-y divide-gray-50">
                                    {[...data.alerts.critical, ...data.alerts.warning, ...data.alerts.info].map((alert) => (
                                        <AlertItem key={`${alert.sector_key}-${alert.rule}-${alert.resource_id ?? 'system'}`} alert={alert} />
                                    ))}
                                </div>
                                <div className="pt-1 -mb-2 text-right">
                                    <Link to="/alerts" className="text-[12px] font-bold text-emerald-700 hover:text-emerald-900 hover:underline">
                                        Lihat semua alert →
                                    </Link>
                                </div>
                            </>
                        ) : (
                            <div className="py-6 text-center">
                                <div className="text-4xl mb-2">✅</div>
                                <h4 className="text-[14px] font-extrabold text-gray-700">Semua aman</h4>
                                <p className="text-[12px] text-gray-400 mt-1">Tidak ada masalah yang memerlukan perhatian saat ini.</p>
                            </div>
                        )}
                    </Card>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        <ChartCard title="Perbandingan Sektor per Kecamatan" subtitle="Sekolah · Tipkamtikmas · Faskes" icon="📊" className="lg:col-span-2">
                            <Bar data={comparisonChartData(data.comparison)} options={{ responsive: true, maintainAspectRatio: false }} />
                        </ChartCard>
                        <ChartCard title="Komposisi Infrastruktur" subtitle="Pendidikan · Ketertiban · Kesehatan" icon="🧮">
                            <PolarArea
                                data={{
                                    labels: data.infra_composition.labels,
                                    datasets: [{ data: data.infra_composition.data, backgroundColor: INFRA_PALETTE }],
                                }}
                                options={{ responsive: true, maintainAspectRatio: false } as ChartOptions<'polarArea'>}
                            />
                        </ChartCard>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        <ChartCard title="Jumlah Siswa per Kecamatan" subtitle="Laki-laki & Perempuan" icon="🎓" className="lg:col-span-2">
                            <StudentCountChart labels={data.student_chart.labels} datasets={data.student_chart.datasets} />
                        </ChartCard>
                        <ChartCard title="Tenaga Kesehatan" subtitle="Dokter · Perawat · Bidan" icon="🩺">
                            <Doughnut
                                data={{
                                    labels: data.health_workforce_chart.labels,
                                    datasets: [{ data: data.health_workforce_chart.data, backgroundColor: WORKFORCE_PALETTE }],
                                }}
                                options={{ responsive: true, maintainAspectRatio: false } as ChartOptions<'doughnut'>}
                            />
                        </ChartCard>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <Card title="Top Kecamatan · Sekolah" icon="🏆">
                            <ol className="space-y-2.5">
                                {data.top_schools.map((row, index) => (
                                    <RankingRow key={row.kecamatan_id} index={index} name={row.name} value={row.count} badgeColor={index === 0 ? 'amber' : 'green'} />
                                ))}
                            </ol>
                        </Card>
                        <Card title="Top Kecamatan · Poskamling" icon="🛡️">
                            <ol className="space-y-2.5">
                                {data.top_poskamling.map((row, index) => (
                                    <RankingRow key={row.kecamatan_id} index={index} name={row.name} value={row.count} badgeColor={index === 0 ? 'amber' : 'blue'} />
                                ))}
                            </ol>
                        </Card>
                        <Card title="Top Kecamatan · Tenaga Medis" icon="👨‍⚕️">
                            <ol className="space-y-2.5">
                                {data.top_health.map((row, index) => (
                                    <RankingRow key={row.kecamatan_id} index={index} name={row.name} value={row.count} badgeColor={index === 0 ? 'amber' : 'teal'} />
                                ))}
                            </ol>
                        </Card>
                    </div>

                    <Card title="Rekap Data per Kecamatan" subtitle="Jumlah aset untuk tiap kecamatan" icon="🗂️">
                        <div className="overflow-x-auto -mx-2">
                            <table className="w-full text-left text-[12px] min-w-[640px]">
                                <thead>
                                    <tr className="text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                                        <th className="px-2 py-2 font-bold">Kecamatan</th>
                                        <th className="px-2 py-2 font-bold text-right">Sekolah</th>
                                        <th className="px-2 py-2 font-bold text-right">Tipkamtikmas</th>
                                        <th className="px-2 py-2 font-bold text-right">Faskes</th>
                                        <th className="px-2 py-2 font-bold text-right">Kelurahan</th>
                                        <th className="px-2 py-2 font-bold text-right">Poskamling</th>
                                        <th className="px-2 py-2 font-bold text-right">Pasar</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {data.per_kecamatan.map((row) => (
                                        <tr key={row.kecamatan_id} className="hover:bg-gray-50">
                                            <td className="px-2 py-2 font-semibold text-gray-800">{row.name}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.schools)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.tipkamtikmas)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.health_facilities)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.kelurahans)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.poskamlings)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums">{formatNumber(row.markets)}</td>
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

function RankingRow({
    index,
    name,
    value,
    badgeColor,
}: {
    index: number;
    name: string;
    value: number;
    badgeColor: string;
}): React.JSX.Element {
    return (
        <li className="flex items-center gap-3">
            <span className={`w-6 h-6 rounded-full ${index === 0 ? 'bg-amber-100 text-amber-600' : 'bg-gray-100 text-gray-500'} flex items-center justify-center text-[11px] font-bold`}>
                {index + 1}
            </span>
            <span className="text-[13px] font-semibold text-gray-700 flex-1 truncate">{name}</span>
            <Badge color={badgeColor}>{formatNumber(value)}</Badge>
        </li>
    );
}