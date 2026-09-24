import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { api } from '../lib/api';
import { formatNumber } from '../lib/format';
import { Badge } from '../components/Badge';
import { Card } from '../components/Card';

interface SearchResult {
    id: number;
    type: string;
    slug: string;
    title: string;
    subtitle: string;
    kecamatan_name?: string | null;
    icon: string;
}

interface SearchResponse {
    query: string;
    results: SearchResult[];
    suggestions: string[];
}

const TYPE_COLORS: Record<string, string> = {
    Sekolah: 'green',
    Faskes: 'red',
    Poskamling: 'blue',
    Tipkamtikmas: 'amber',
    Polsek: 'violet',
    Pasar: 'teal',
    Kecamatan: 'emerald',
    'Kelurahan/Desa': 'gray',
};

export function SearchPage(): React.JSX.Element {
    const navigate = useNavigate();
    const [input, setInput] = useState('');
    const [submitted, setSubmitted] = useState(false);

    const query = useQuery<SearchResponse>({
        queryKey: ['search', submitted ? input.trim() : ''],
        queryFn: () => api.get<SearchResponse>(`/search?q=${encodeURIComponent(input.trim())}`),
        enabled: submitted && input.trim().length > 0,
        staleTime: 60_000,
    });

    const submit = (value: string): void => {
        setInput(value);
        setSubmitted(true);
        void query.refetch({ cancelRefetch: true });
    };

    const results = query.data?.results ?? [];
    const grouped = new Map<string, SearchResult[]>();

    for (const result of results) {
        const list = grouped.get(result.type) ?? [];

        list.push(result);
        grouped.set(result.type, list);
    }

    return (
        <div className="space-y-5 p-5 page-transition">
            <div>
                <h1 className="text-xl sm:text-2xl font-extrabold text-gray-900">Pencarian Intel</h1>
                <p className="text-[12px] text-gray-500 mt-0.5">Cari lintas data master: sekolah, faskes, poskamling, polsek, dan lainnya</p>
            </div>

            <Card>
                <form
                    className="flex items-center gap-2 flex-wrap"
                    onSubmit={(event) => {
                        event.preventDefault();
                        submit(input);
                    }}
                >
                    <input
                        type="search"
                        value={input}
                        onChange={(event) => {
                            setInput(event.target.value);
                            setSubmitted(false);
                        }}
                        placeholder="Ketik nama, NPSN, atau lokasi…"
                        className="flex-1 min-w-[240px] rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-[13px] font-semibold text-gray-800"
                        aria-label="Kata kunci pencarian"
                    />
                    <button
                        type="submit"
                        className="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[12px] font-bold px-5 py-2.5 transition"
                    >
                        🔎 Cari
                    </button>
                </form>

                {query.data && query.data.suggestions.length > 0 ? (
                    <div className="mt-3 flex items-center gap-2 flex-wrap">
                        <span className="text-[11px] text-gray-400 font-bold">Saran:</span>
                        {query.data.suggestions.map((suggestion) => (
                            <button
                                key={suggestion}
                                type="button"
                                onClick={() => submit(suggestion)}
                                className="text-[11px] font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-full px-3 py-1 transition"
                            >
                                {suggestion}
                            </button>
                        ))}
                    </div>
                ) : null}
            </Card>

            {!submitted || !input.trim() ? (
                <Card>
                    <div className="py-8 text-center">
                        <div className="text-4xl mb-2">🔎</div>
                        <h3 className="text-[14px] font-extrabold text-gray-700">Mulai pencarian</h3>
                        <p className="text-[12px] text-gray-400 mt-1">Masukkan kata kunci untuk menemukan data intelijen.</p>
                    </div>
                </Card>
            ) : query.isPending ? (
                <Card>
                    <div className="space-y-3">
                        {Array.from({ length: 4 }).map((_, index) => (
                            <div key={index} className="rounded-xl bg-gray-100 h-12 animate-pulse" />
                        ))}
                    </div>
                </Card>
            ) : query.isError ? (
                <Card>
                    <div className="py-6 text-center text-[12px] text-gray-500">Terjadi kesalahan saat mencari data. Silakan coba lagi.</div>
                </Card>
            ) : query.data.results.length === 0 ? (
                <Card>
                    <div className="py-6 text-center">
                        <div className="text-4xl mb-2">🤷</div>
                        <h4 className="text-[14px] font-extrabold text-gray-700">Tidak ditemukan</h4>
                        <p className="text-[12px] text-gray-400 mt-1">Tidak ada data yang cocok dengan «{query.data.query}».</p>
                    </div>
                </Card>
            ) : (
                <div className="space-y-5">
                    <p className="text-[12px] text-gray-500">
                        <strong className="text-gray-800">{formatNumber(query.data.results.length)}</strong> hasil untuk «{query.data.query}»
                    </p>
                    {[...grouped.entries()].map(([type, items]) => (
                        <Card key={type} title={type} icon={items[0]?.icon} actions={<Badge color={TYPE_COLORS[type] ?? 'gray'}>{items.length}</Badge>}>
                            <div className="divide-y divide-gray-50">
                                {items.map((item) => (
                                    <button
                                        key={`${item.slug}-${item.id}`}
                                        type="button"
                                        onClick={() => navigate(`/peta`)}
                                        className="w-full text-left flex items-center gap-3 py-3 hover:bg-gray-50 rounded-lg px-2 -mx-2 transition"
                                    >
                                        <span className="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center text-base">{item.icon}</span>
                                        <span className="min-w-0">
                                            <span className="block text-[13px] font-bold text-gray-800 truncate">{item.title}</span>
                                            <span className="block text-[11px] text-gray-400 truncate">{item.subtitle}</span>
                                        </span>
                                    </button>
                                ))}
                            </div>
                        </Card>
                    ))}
                </div>
            )}
        </div>
    );
}