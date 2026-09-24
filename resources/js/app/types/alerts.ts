export type AlertSeverity = 'critical' | 'warning' | 'info';

export type AlertStatus = 'baru' | 'ditinjau' | 'ditangani' | 'selesai';

export interface CommandAlertItem {
    id: number;
    rule: string;
    severity: AlertSeverity;
    severity_label: string;
    sector: string;
    sector_key: string;
    title: string;
    description: string;
    status: AlertStatus;
    status_label: string;
    is_open: boolean;
    kecamatan_id: number | null;
    kecamatan_name: string | null;
    latitude: number | null;
    longitude: number | null;
    resource_type: string | null;
    resource_id: number | null;
    opened_at: string | null;
    last_seen_at: string | null;
    resolved_at: string | null;
}

export interface AlertCounts {
    critical: number;
    warning: number;
    info: number;
    open: number;
}

export interface CommandAlertsResponse {
    alerts: CommandAlertItem[];
    counts: AlertCounts;
    meta?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    } | null;
}

export interface AlertFilters {
    severity: AlertSeverity | '';
    status: AlertStatus | '';
    kecamatan: number | '';
    search: string;
}