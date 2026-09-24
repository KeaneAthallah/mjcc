import { useState } from 'react';
import { Link } from 'react-router-dom';
import { usePublicCategories, usePublicSources } from '../hooks/usePublicData';
import { compactNumber } from '../lib/format';
import { Badge } from '../components/Badge';
import { Card } from '../components/Card';
import { DashboardError } from '../components/DashboardError';

export function DataPublikPage(): React.JSX.Element {
    const [category, setCategory] = useState('');
    const categories = usePublicCategories();
    const sources = usePublicSources(category);

    const activeCategory = categories.data?.find((item) => item.key === category);

    return (
        <div className="space-y-5 p-5 page-transition">
            <div>
                <h1 className="text-xl sm:text-2xl font-extrabold text-gray-900">Data Publik</h1>
                <p className="text-[12px] text-gray-500 mt-0.5">Sumber data terbuka & sinkronisasi data publik Morowali</p>
            </div>

            {categories.isPending ? (
                <Card>
                    <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        {Array.from({ length: 4 }).map((_, index) => (
                            <div key={index} className="rounded-xl bg-gray-100 h-28 animate-pulse" />
                        ))}
                    </div>
                </Card>
            ) : categories.isError || !categories.data ? (
                <DashboardError onRetry={() => void categories.refetch()} />
            ) : (
                <Card>
                    <div className="flex items-center gap-2 flex-wrap">
                        <CategoryChip label="Semua" active={category === ''} onClick={() => setCategory('')} />
                        {categories.data.map((item) => (
                            <CategoryChip
                                key={item.key}
                                label={item.label}
                                count={item.source_count}
                                active={category === item.key}
                                onClick={() => setCategory(category === item.key ? '' : item.key)}
                            />
                        ))}
                    </div>
                </Card>
            )}

            {activeCategory ? (
                <p className="text-[12px] text-gray-500">
                    {activeCategory.icon} {activeCategory.description}
                </p>
            ) : null}

            {sources.isPending ? (
                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {Array.from({ length: 6 }).map((_, index) => (
                        <div key={index} className="rounded-2xl bg-white border border-gray-200 p-5 h-36 animate-pulse" />
                    ))}
                </div>
            ) : sources.isError || !sources.data ? (
                <DashboardError onRetry={() => void sources.refetch()} />
            ) : sources.data.length === 0 ? (
                <Card>
                    <div className="py-6 text-center">
                        <div className="text-4xl mb-2">🗂️</div>
                        <h4 className="text-[14px] font-extrabold text-gray-700">Belum ada sumber data</h4>
                        <p className="text-[12px] text-gray-400 mt-1">Sinkronisasi data publik belum menghasilkan rekaman.</p>
                    </div>
                </Card>
            ) : (
                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {sources.data.map((source) => (
                        <Link
                            key={source.key}
                            to={`/data-publik/sumber/${source.key}`}
                            className="rounded-2xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md hover:border-violet-300 transition"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <h3 className="text-[14px] font-extrabold text-gray-800">{source.name}</h3>
                                <SourceStatus status={source.status} />
                            </div>
                            <p className="text-[12px] text-gray-500 mt-1.5 line-clamp-2">{source.description}</p>
                            <div className="mt-4 flex items-center justify-between text-[11px] text-gray-400">
                                <span>
                                    {compactNumber(source.record_count)} rekaman · {source.freshness}
                                </span>
                            </div>
                        </Link>
                    ))}
                </div>
            )}
        </div>
    );
}

function CategoryChip({
    label,
    count,
    active,
    onClick,
}: {
    label: string;
    count?: number;
    active: boolean;
    onClick: () => void;
}): React.JSX.Element {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-full px-4 py-1.5 text-[12px] font-bold transition border ${
                active ? 'bg-violet-600 text-white border-violet-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'
            }`}
        >
            {label}
            {count !== undefined ? ` (${count})` : null}
        </button>
    );
}

function SourceStatus({ status }: { status: string }): React.JSX.Element {
    const color = status === 'active' ? 'green' : status === 'error' ? 'red' : 'amber';

    return <Badge color={color}>{status}</Badge>;
}