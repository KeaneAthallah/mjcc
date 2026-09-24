import type { CommandAlert } from '../types/dashboard';
import { formatDateTime } from '../lib/format';
import { Badge } from './Badge';

const SEVERITY_THEME: Record<
    CommandAlert['severity'],
    { badge: string; label: string; dot: string }
> = {
    critical: { badge: 'red', label: 'Kritis', dot: 'bg-red-500' },
    warning: { badge: 'amber', label: 'Perhatian', dot: 'bg-amber-500' },
    info: { badge: 'blue', label: 'Info', dot: 'bg-blue-500' },
};

const SECTOR_ICONS: Record<string, string> = {
    pendidikan: '🎓',
    kesehatan: '🏥',
    ketertiban: '🛡️',
    sistem: '🖥️',
};

export function AlertItem({ alert }: { alert: CommandAlert }): React.JSX.Element {
    const theme = SEVERITY_THEME[alert.severity];

    return (
        <div className="flex items-start gap-3 p-3">
            <span className={`mt-1 w-2 h-2 rounded-full shrink-0 ${theme.dot}`} />
            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2 flex-wrap">
                    <span>{SECTOR_ICONS[alert.sector_key] ?? '📌'}</span>
                    <h4 className="text-[13px] font-bold text-gray-800">{alert.title}</h4>
                    <Badge color={theme.badge}>{theme.label}</Badge>
                </div>
                <p className="text-[12px] text-gray-500 mt-0.5">{alert.detail}</p>
                <div className="mt-1 flex items-center gap-2 text-[10px] text-gray-400">
                    {alert.kecamatan_name ? <span>📍 {alert.kecamatan_name}</span> : null}
                    {alert.created_at ? <span>{formatDateTime(alert.created_at)}</span> : null}
                </div>
            </div>
        </div>
    );
}