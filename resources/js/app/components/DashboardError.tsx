import { useRealtime } from '../lib/realtime';

export function DashboardError({ onRetry }: { onRetry: () => void }): React.JSX.Element {
    const { connection } = useRealtime();

    return (
        <div className="rounded-2xl bg-white border border-gray-200 p-10 text-center">
            <div className="text-4xl mb-2">⚠️</div>
            <h3 className="text-[14px] font-extrabold text-gray-700">Gagal memuat data</h3>
            <p className="text-[12px] text-gray-400 mt-1">
                Periksa koneksi Anda{connection === 'connecting' ? ' dan server realtime' : ''} lalu coba lagi.
            </p>
            <button
                type="button"
                onClick={onRetry}
                className="mt-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[12px] font-bold px-4 py-2 transition"
            >
                Coba Lagi
            </button>
        </div>
    );
}