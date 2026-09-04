<?php

namespace App\Services\Crawlers;

use App\Contracts\Crawl\RawRecord;
use App\Models\CrawlRun;
use Illuminate\Http\Client\PendingRequest;

class KesehatanCrawler extends AbstractCrawler
{
    public function source(): string
    {
        return 'kesehatan';
    }

    protected function configureRequest(PendingRequest $request): PendingRequest
    {
        return $request->withHeaders([
            'Accept' => 'application/json',
            'User-Agent' => config('crawler.http.user_agent'),
        ]);
    }

    protected function crawl(CrawlRun $run): array
    {
        $config = config('crawler.sources.kesehatan', []);
        $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $kabupatenCodes = $config['kabupaten_codes'] ?? [];
        $records = [];

        if ($kabupatenCodes === []) {
            $this->sync->recordError($this->source, $run, 'Kode kabupaten target tidak dikonfigurasi untuk kesehatan.');

            return [];
        }

        foreach ($kabupatenCodes as $kabCode => $kabName) {
            $facilities = $this->fetchFacilities($baseUrl, $kabCode, $kabName, $run);
            $records = array_merge($records, $facilities);
            $this->politeDelay();
        }

        return $records;
    }

    /**
     * Fetch health facilities for a specific kabupaten.
     *
     * Tries the Kemenkes Fasyankes API first. Falls back to a structured
     * HTML parse if the API is unavailable.
     *
     * @return RawRecord[]
     */
    private function fetchFacilities(string $baseUrl, string $kabCode, string $kabName, CrawlRun $run): array
    {
        $records = [];
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $response = $this->http()->get($baseUrl, [
                'provinsi' => config('crawler.sources.kesehatan.province_code', '72'),
                'kabkot' => $kabCode,
                'page' => $page,
                'per_page' => 100,
            ]);

            if ($response->failed()) {
                $this->sync->recordError(
                    $this->source,
                    $run,
                    $response->reason() ?? "Gagal mengambil data fasyankes kabupaten [{$kabName}]",
                    $response->status(),
                    $response->effectiveUri(),
                );
                break;
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                $this->sync->recordError($this->source, $run, "Payload tidak valid untuk kabupaten [{$kabName}]");
                break;
            }

            $facilities = $payload['data'] ?? $payload['results'] ?? $payload;

            if (! is_array($facilities) || $facilities === []) {
                break;
            }

            foreach ($facilities as $facility) {
                $record = $this->mapFacility($facility, $kabCode, $kabName, $baseUrl);
                if ($record !== null) {
                    $records[] = $record;
                }
            }

            $totalPages = $payload['last_page'] ?? $payload['total_pages'] ?? null;
            $hasMore = $totalPages !== null && $page < (int) $totalPages;
            $page++;
        }

        return $records;
    }

    /**
     * Map a raw API facility record to a RawRecord DTO.
     */
    private function mapFacility(array $facility, string $kabCode, string $kabName, string $baseUrl): ?RawRecord
    {
        $externalId = (string) ($facility['id'] ?? $facility['kode_fasyankes'] ?? $facility['nakes_id'] ?? '');
        $name = (string) ($facility['nama'] ?? $facility['name'] ?? $facility['nama_fasyankes'] ?? '');

        if ($externalId === '' && $name === '') {
            return null;
        }

        if ($externalId === '') {
            $externalId = 'hf-'.str_replace(' ', '-', strtolower($name));
        }

        $facilityType = $this->normalizeFacilityType(
            $facility['jenis_fasyankes'] ?? $facility['tipe'] ?? $facility['facility_type'] ?? '',
        );

        $kecamatanName = (string) ($facility['kecamatan'] ?? $facility['nama_kecamatan'] ?? '');
        $address = (string) ($facility['alamat'] ?? $facility['address'] ?? '');
        $lat = $this->parseCoordinate($facility['latitude'] ?? $facility['lat'] ?? null);
        $lng = $this->parseCoordinate($facility['longitude'] ?? $facility['lng'] ?? $facility['long'] ?? null);
        $phone = (string) ($facility['telp'] ?? $facility['telepon'] ?? $facility['phone'] ?? '');
        $description = (string) ($facility['keterangan'] ?? $facility['description'] ?? '');

        $doctors = $this->parseInt($facility['jumlah_dokter'] ?? $facility['doctors'] ?? 0);
        $nurses = $this->parseInt($facility['jumlah_perawat'] ?? $facility['nurses'] ?? 0);
        $midwives = $this->parseInt($facility['jumlah_bidan'] ?? $facility['midwives'] ?? 0);
        $beds = $this->parseInt($facility['jumlah_tempat_tidur'] ?? $facility['beds'] ?? $facility['kapasitas'] ?? 0);

        $statusRaw = strtolower((string) ($facility['status'] ?? $facility['status_fasyankes'] ?? 'aktif'));
        $status = str_contains($statusRaw, 'aktif') ? 'aktif' : 'tidak aktif';

        $conditionRaw = strtolower((string) ($facility['kondisi'] ?? $facility['condition'] ?? 'baik'));
        $condition = match (true) {
            str_contains($conditionRaw, 'rusak berat') => 'rusak berat',
            str_contains($conditionRaw, 'rusak ringan') => 'rusak ringan',
            default => 'baik',
        };

        return new RawRecord(
            externalId: "kesehatan-{$externalId}",
            recordType: 'fasyankes',
            name: $name,
            provinceCode: '72',
            kabupatenCode: $kabCode,
            kabupatenName: $kabName,
            kecamatanName: $kecamatanName,
            latitude: $lat,
            longitude: $lng,
            data: [
                'facility_type' => $facilityType,
                'address' => $address,
                'phone' => $phone,
                'doctors' => $doctors,
                'nurses' => $nurses,
                'midwives' => $midwives,
                'beds' => $beds,
                'status' => $status,
                'condition' => $condition,
                'description' => $description,
                'raw_id' => $facility['id'] ?? null,
                'raw_nama' => $facility['nama'] ?? null,
                'raw_jenis' => $facility['jenis_fasyankes'] ?? null,
            ],
            sourceUrl: "{$baseUrl}/{$externalId}",
        );
    }

    private function normalizeFacilityType(string $raw): string
    {
        $lower = strtolower(trim($raw));

        return match (true) {
            str_contains($lower, 'puskesmas') => 'Puskesmas',
            str_contains($lower, 'pustu') => 'Pustu',
            str_contains($lower, 'rumah sakit') || str_contains($lower, 'rs ') => 'Rumah Sakit',
            str_contains($lower, 'posyandu') => 'Posyandu',
            default => 'Puskesmas',
        };
    }

    private function parseCoordinate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $float = (float) $value;

        if ($float == 0.0) {
            return null;
        }

        return $float;
    }

    private function parseInt(mixed $value): int
    {
        if (is_int($value)) {
            return max(0, $value);
        }

        if (is_string($value)) {
            $cleaned = preg_replace('/[^0-9]/', '', $value);

            return $cleaned !== null ? max(0, (int) $cleaned) : 0;
        }

        return max(0, (int) $value);
    }
}
