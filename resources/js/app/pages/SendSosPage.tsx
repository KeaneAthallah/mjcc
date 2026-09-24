import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import { api } from '../lib/api';
import { Card } from '../components/Card';
import type { SosAlert } from '../types/sos';

const CATEGORIES = [
    { value: 'general', label: 'Umum', icon: '🆘' },
    { value: 'medical', label: 'Medis', icon: '🚑' },
    { value: 'fire', label: 'Pemadam Kebakaran', icon: '🚒' },
    { value: 'police', label: 'Polisi', icon: '🚓' },
];

export function SendSosPage(): React.JSX.Element {
    const navigate = useNavigate();
    const [category, setCategory] = useState('general');
    const [message, setMessage] = useState('');
    const [latitude, setLatitude] = useState('');
    const [longitude, setLongitude] = useState('');
    const [locating, setLocating] = useState(false);
    const [locationError, setLocationError] = useState<string | null>(null);

    const mutation = useMutation({
        mutationFn: () =>
            api.post<SosAlert>('/sos', {
                latitude: Number(latitude),
                longitude: Number(longitude),
                category,
                message: message.trim() || null,
            }),
        onSuccess: (sos) => {
            navigate(`/sos/${sos.id}`, { replace: true });
        },
    });

    const useMyLocation = (): void => {
        if (!navigator.geolocation) {
            setLocationError('Geolokasi tidak didukung di perangkat ini.');

            return;
        }

        setLocating(true);
        setLocationError(null);

        navigator.geolocation.getCurrentPosition(
            (position) => {
                setLatitude(position.coords.latitude.toFixed(6));
                setLongitude(position.coords.longitude.toFixed(6));
                setLocating(false);
            },
            () => {
                setLocationError('Gagal mengambil lokasi. Masukkan koordinat secara manual.');
                setLocating(false);
            },
            { timeout: 10_000 },
        );
    };

    const valid = latitude !== '' && longitude !== '' && !Number.isNaN(Number(latitude)) && !Number.isNaN(Number(longitude));

    return (
        <div className="space-y-5 p-5 page-transition max-w-xl mx-auto">
            <Link to="/sos" className="text-[12px] font-bold text-emerald-700 hover:underline">
                ← Kembali ke Papan SOS
            </Link>

            <div>
                <h1 className="text-xl sm:text-2xl font-extrabold text-gray-900">Kirim SOS</h1>
                <p className="text-[12px] text-gray-500 mt-0.5">Lokasi Anda akan dikirimkan kepada petugas.</p>
            </div>

            <Card>
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        if (valid) {
                            mutation.mutate();
                        }
                    }}
                >
                    <div>
                        <label className="text-[12px] font-bold text-gray-600">Jenis kejadian</label>
                        <div className="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2">
                            {CATEGORIES.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    onClick={() => setCategory(option.value)}
                                    className={`rounded-xl border px-2 py-3 text-center transition ${
                                        category === option.value
                                            ? 'border-red-400 bg-red-50'
                                            : 'border-gray-200 bg-white hover:bg-gray-50'
                                    }`}
                                >
                                    <span className="block text-xl">{option.icon}</span>
                                    <span className={`block text-[11px] font-bold mt-1 ${category === option.value ? 'text-red-700' : 'text-gray-600'}`}>
                                        {option.label}
                                    </span>
                                </button>
                            ))}
                        </div>
                    </div>

                    <div>
                        <label htmlFor="sos-message" className="text-[12px] font-bold text-gray-600">
                            Pesan tambahan
                        </label>
                        <textarea
                            id="sos-message"
                            value={message}
                            onChange={(event) => setMessage(event.target.value)}
                            rows={3}
                            maxLength={500}
                            placeholder="Deskripsikan situasi darurat…"
                            className="mt-2 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-[12px] font-semibold text-gray-700"
                        />
                    </div>

                    <div>
                        <div className="flex items-center justify-between">
                            <label className="text-[12px] font-bold text-gray-600">Koordinat lokasi</label>
                            <button
                                type="button"
                                onClick={useMyLocation}
                                disabled={locating}
                                className="text-[12px] font-bold text-emerald-700 hover:underline disabled:opacity-50"
                            >
                                {locating ? 'Mengambil lokasi…' : '📍 Pakai lokasi saya'}
                            </button>
                        </div>
                        <div className="mt-2 grid grid-cols-2 gap-3">
                            <input
                                type="number"
                                step="any"
                                value={latitude}
                                onChange={(event) => setLatitude(event.target.value)}
                                placeholder="Latitude (-2.7..)"
                                aria-label="Latitude"
                                className="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[12px] font-semibold text-gray-700"
                            />
                            <input
                                type="number"
                                step="any"
                                value={longitude}
                                onChange={(event) => setLongitude(event.target.value)}
                                placeholder="Longitude (121.5..)"
                                aria-label="Longitude"
                                className="rounded-xl border border-gray-300 bg-white px-3 py-2 text-[12px] font-semibold text-gray-700"
                            />
                        </div>
                        {locationError ? <p className="text-[11px] text-red-600 mt-1">{locationError}</p> : null}
                    </div>

                    <button
                        type="submit"
                        disabled={!valid || mutation.isPending}
                        className="w-full rounded-xl bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-[13px] font-bold px-4 py-3 transition"
                    >
                        {mutation.isPending ? 'Mengirim…' : '🆘 Kirim SOS'}
                    </button>

                    {mutation.isError ? (
                        <p className="text-[12px] text-red-600">Gagal mengirim SOS. Silakan coba lagi.</p>
                    ) : null}
                </form>
            </Card>
        </div>
    );
}