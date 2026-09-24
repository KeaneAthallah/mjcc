import { Link } from 'react-router-dom';

export function NotFoundPage(): React.JSX.Element {
    return (
        <div className="h-full flex flex-col items-center justify-center text-center p-6">
            <div className="text-6xl font-extrabold text-gray-300">404</div>
            <h1 className="text-lg font-bold text-gray-800 mt-2">Halaman tidak ditemukan</h1>
            <p className="text-[13px] text-gray-500 mt-1">Alamat yang Anda buka tidak tersedia.</p>
            <Link to="/" className="mt-4 text-sm font-semibold text-emerald-700 hover:underline">
                Kembali ke Beranda
            </Link>
        </div>
    );
}