import { useState, type FormEvent } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import { api, ApiError } from '../lib/api';
import { setSession } from '../lib/auth';

interface LoginResponse {
    token: string;
    user: {
        id: number;
        name: string;
        email: string;
        role: string;
        responder_type: string | null;
        responder_type_label: string | null;
        email_verified: boolean;
        created_at: string | null;
        updated_at: string | null;
    };
}

export function LoginPage(): React.JSX.Element {
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [verificationRequired, setVerificationRequired] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = async (event: FormEvent): Promise<void> => {
        event.preventDefault();
        setError(null);
        setVerificationRequired(false);
        setSubmitting(true);

        try {
            const { token, user } = await api.post<LoginResponse>('/login', { email, password });

            setSession(token, user);
            queryClient.clear();
            navigate('/', { replace: true });
        } catch (cause) {
            if (cause instanceof ApiError) {
                setVerificationRequired(Boolean(cause.meta?.verification_required));
                setError(cause.errors?.email?.[0] ?? cause.message);
            } else {
                setError('Tidak dapat terhubung ke server.');
            }
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="min-h-dvh flex items-center justify-center bg-gradient-to-br from-gray-900 via-gray-900 to-emerald-800 p-4">
            <div className="w-full max-w-md">
                <div className="text-center mb-6">
                    <div className="w-20 h-20 mx-auto rounded-full bg-white flex items-center justify-center overflow-hidden shadow-lg mb-3">
                        <img src="/logo.png" alt="Logo Morowali Juara" className="w-full h-full object-contain p-2" />
                    </div>
                    <h1 className="text-lg tracking-widest uppercase font-extrabold text-white leading-tight">
                        Morowali Juara
                        <br />
                        Command Center
                    </h1>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="bg-white rounded-2xl shadow-2xl p-6 space-y-4"
                >
                    <div>
                        <label htmlFor="email" className="block text-[12px] font-bold text-gray-700 mb-1">
                            Email
                        </label>
                        <input
                            id="email"
                            type="email"
                            required
                            autoComplete="email"
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div>
                        <label htmlFor="password" className="block text-[12px] font-bold text-gray-700 mb-1">
                            Kata Sandi
                        </label>
                        <input
                            id="password"
                            type="password"
                            required
                            autoComplete="current-password"
                            value={password}
                            onChange={(event) => setPassword(event.target.value)}
                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    {error ? (
                        <div className="rounded-lg bg-red-50 border border-red-200 text-red-700 text-[12px] px-3 py-2">
                            {verificationRequired
                                ? 'Akun Anda belum diverifikasi. Periksa email Anda untuk tautan verifikasi.'
                                : error}
                        </div>
                    ) : null}

                    <button
                        type="submit"
                        disabled={submitting}
                        className="w-full rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-bold py-2.5 transition"
                    >
                        {submitting ? 'Memproses…' : 'Masuk'}
                    </button>

                    <p className="text-center text-[12px] text-gray-500">
                        Belum punya akun?{' '}
                        <Link to="/register" className="font-semibold text-emerald-700 hover:underline">
                            Daftar
                        </Link>
                    </p>
                </form>
            </div>
        </div>
    );
}