import { Navigate } from 'react-router-dom';
import { getToken } from '../lib/auth';

export function RequireAuth({ children }: { children: React.JSX.Element }): React.JSX.Element {
    if (!getToken()) {
        return <Navigate to="/login" replace />;
    }

    return children;
}

export function GuestOnly({ children }: { children: React.JSX.Element }): React.JSX.Element {
    if (getToken()) {
        return <Navigate to="/" replace />;
    }

    return children;
}