import { KecamatanFilter } from './KecamatanFilter';

export function DashboardHeader({
    title,
    subtitle,
    kecamatanId,
    onKecamatanChange,
    onReload,
    additional,
}: {
    title: string;
    subtitle: string;
    kecamatanId: number | null;
    onKecamatanChange: (kecamatanId: number | null) => void;
    onReload: () => void;
    additional?: React.ReactNode;
}): React.JSX.Element {
    return (
        <div className="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 className="text-xl sm:text-2xl font-extrabold text-gray-900">{title}</h1>
                <p className="text-[12px] text-gray-500 mt-0.5">{subtitle}</p>
            </div>
            <div className="flex items-center gap-2 flex-wrap">
                <KecamatanFilter value={kecamatanId} onChange={onKecamatanChange} />
                {additional}
                <button
                    type="button"
                    onClick={onReload}
                    className="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-[12px] font-bold bg-white text-gray-600 border-gray-300 hover:bg-gray-50 transition"
                    title="Muat ulang data dashboard"
                >
                    🔄 Muat Ulang
                </button>
            </div>
        </div>
    );
}