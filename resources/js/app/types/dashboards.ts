export interface ChartDataset {
    label: string;
    data: number[];
    backgroundColor?: string | string[];
    borderColor?: string;
    borderWidth?: number;
}

export interface DatasetChart {
    labels: string[];
    datasets: ChartDataset[];
}

export interface LabeledChart {
    labels: string[];
    data: number[];
}

export interface DomainStatistics {}

export interface EducationStatistics {
    total_sd: number;
    total_smp: number;
    siswa_laki: number;
    siswa_perempuan: number;
    guru: number;
    kelas: number;
    mapel: number;
}

export interface FacilityProgress {
    name: string;
    pct: number;
    meta: string;
}

export interface EducationTableRow {
    id: number;
    name: string;
    sd_count: number;
    smp_count: number;
    siswa_l: number;
    siswa_p: number;
    guru: number;
    kelas: number;
    kapasitas: number;
}

export interface EducationDashboard {
    statistics: EducationStatistics;
    student_per_kecamatan: DatasetChart;
    teacher_ratio: LabeledChart;
    facility_progress: FacilityProgress[];
    table: EducationTableRow[];
}

export interface SecurityStatistics {
    kelurahan: number;
    polsek: number;
    tipkamtikmas: number;
    poskamling: number;
    pasar: number;
}

export interface PolsekRow {
    id: number;
    name: string;
    status?: string | null;
    poskamling_count?: number;
    personnel_count?: number;
    kecamatan?: { id: number; name: string } | null;
}

export interface SecurityDashboard {
    statistics: SecurityStatistics;
    compare_chart: DatasetChart;
    poskamling_distribution: LabeledChart;
    kelurahan_per_kecamatan: LabeledChart;
    polseks: PolsekRow[];
}

export interface HealthStatistics {
    puskesmas: number;
    pustu: number;
    rs: number;
    posyandu: number;
    dokter: number;
    perawat: number;
    bidan: number;
    bed: number;
}

export interface HealthTableRow {
    id: number;
    name: string;
    puskesmas_count: number;
    pustu_count: number;
    rs_count: number;
    posyandu_count: number;
    dok: number;
    per: number;
    bid: number;
    bed: number;
}

export interface HealthDashboard {
    statistics: HealthStatistics;
    workforce_per_kecamatan: DatasetChart;
    facility_proportion: LabeledChart;
    capacity_per_kecamatan: LabeledChart;
    table: HealthTableRow[];
}

export interface KecamatanOption {
    id: number;
    name: string;
}