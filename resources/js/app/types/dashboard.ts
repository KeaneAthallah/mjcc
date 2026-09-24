export interface OverviewStats {
    kecamatan: number;
    kelurahan: number;
    population: number;
    total_sekolah: number;
    sekolah_baik: number;
    total_sd: number;
    total_smp: number;
    total_siswa: number;
    total_guru: number;
    total_polsek: number;
    total_tipkamtikmas: number;
    total_poskamling: number;
    poskamling_aktif: number;
    total_pasar: number;
    total_faskes: number;
    faskes_aktif: number;
    total_puskesmas: number;
    total_rs: number;
    total_pustu: number;
    total_posyandu: number;
    total_dokter: number;
    total_perawat: number;
    total_bidan: number;
    total_bed: number;
}

export interface ChartDataset {
    label: string;
    data: number[];
    backgroundColor?: string | string[];
    borderColor?: string;
    borderWidth?: number;
}

export interface LabeledChart {
    labels: string[];
    datasets: ChartDataset[];
}

export interface SegmentChart {
    labels: string[];
    data: number[];
}

export type Severity = 'critical' | 'warning' | 'info';

export interface CommandAlert {
    rule: string;
    severity: Severity;
    sector: string;
    sector_key: string;
    title: string;
    detail: string;
    resource_type: string | null;
    resource_class: string | null;
    resource_id: number | string | null;
    kecamatan_id: number | null;
    kecamatan_name: string | null;
    latitude: number | null;
    longitude: number | null;
    detail_route: string | null;
    detail_params: Record<string, string | number> | null;
    created_at: string | null;
}

export interface AlertGroups {
    critical: CommandAlert[];
    warning: CommandAlert[];
    info: CommandAlert[];
}

export interface TopRow {
    kecamatan_id: number;
    name: string;
    count: number;
}

export interface PerKecamatanRow {
    kecamatan_id: number;
    name: string;
    schools: number;
    tipkamtikmas: number;
    health_facilities: number;
    kelurahans: number;
    poskamlings: number;
    markets: number;
}

export interface DashboardOverview {
    stats: OverviewStats;
    comparison: LabeledChart;
    infra_composition: SegmentChart;
    student_chart: LabeledChart;
    health_workforce_chart: SegmentChart;
    top_schools: TopRow[];
    top_poskamling: TopRow[];
    top_health: TopRow[];
    per_kecamatan: PerKecamatanRow[];
    alerts: AlertGroups;
}