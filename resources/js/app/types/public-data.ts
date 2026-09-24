export interface PublicCategory {
    key: string;
    label: string;
    icon: string;
    description: string;
    source_count: number;
}

export interface PublicSource {
    key: string;
    name: string;
    category: string;
    description: string;
    status: string;
    status_label: string;
    last_success_at: string | null;
    record_count: number;
    freshness: string;
    source_url: string | null;
    supports_map: boolean;
}

export interface PublicSourceHeader {
    key: string;
    name: string;
    status: string;
    last_success_at: string | null;
    record_count: number;
}

export interface PublicSourceDetail {
    source: PublicSourceHeader;
    total: number;
    latest_date?: string | null;
    year?: number;
    total_pendapatan?: number;
    total_belanja?: number;
    risk_counts?: Record<string, number>;
    records: Record<string, unknown>[];
}