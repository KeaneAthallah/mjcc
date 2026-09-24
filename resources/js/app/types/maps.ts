export interface MapMarker {
    id: number;
    type: string;
    sector: string;
    name: string;
    latitude: number;
    longitude: number;
    status: string | null;
    kecamatan: string | null;
    kelurahan: string | null;
}

export interface MapKecamatan {
    id: number;
    name: string;
}

export interface MapResponse {
    markers: MapMarker[];
    kecamatans: MapKecamatan[];
}

export interface MapFilters {
    sector: string;
    type: string;
    kecamatanId: number | null;
}

export const MARKER_TYPE_LABELS: Record<string, string> = {
    school: 'Sekolah',
    health_facility: 'Faskes',
    polsek: 'Polsek',
    tipkamtikmas: 'Tipkamtikmas',
    poskamling: 'Poskamling',
    market: 'Pasar',
};

export const MARKER_TYPE_COLORS: Record<string, string> = {
    school: '#10b981',
    health_facility: '#ef4444',
    polsek: '#8b5cf6',
    tipkamtikmas: '#f59e0b',
    poskamling: '#3b82f6',
    market: '#14b8a6',
};

export const SECTOR_LABELS: Record<string, string> = {
    pendidikan: 'Pendidikan',
    ketertiban: 'Ketertiban',
    kesehatan: 'Kesehatan',
};