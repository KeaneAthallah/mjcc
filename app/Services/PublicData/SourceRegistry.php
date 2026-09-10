<?php

namespace App\Services\PublicData;

use App\Models\PublicDataSource;
use App\Services\PublicData\Contracts\PublicDataSourceAdapter;

/**
 * Central registry for all public data sources. Maps source keys to their
 * adapter classes and default configurations.
 */
class SourceRegistry
{
    /**
     * Default source definitions. These are used to seed the database registry
     * and to provide fallback configuration.
     *
     * @var array<string, array{adapter: class-string<PublicDataSourceAdapter>, name: string, category: string, description: string, source_url: string|null}>
     */
    private const SOURCES = [
        'satudata' => [
            'adapter' => SatuDataMorowaliAdapter::class,
            'name' => 'Satu Data Morowali',
            'category' => 'pemantauan',
            'description' => 'Statistik terbuka dari portal Satu Data Morowali',
            'source_url' => 'https://data.morowalikab.go.id',
        ],
        'ats' => [
            'adapter' => AnakTidakSekolahAdapter::class,
            'name' => 'Anak Tidak Sekolah',
            'category' => 'pendidikan',
            'description' => 'Pemantauan anak tidak bersekolah dari Kemendikdasmen',
            'source_url' => 'https://ats.data.kemendikdasmen.go.id',
        ],
        'dapodik' => [
            'adapter' => DapodikAdapter::class,
            'name' => 'Dapodik',
            'category' => 'pendidikan',
            'description' => 'Data Pokok Pendidikan dari Kemendikdasmen',
            'source_url' => 'https://dapo.kemendikdasmen.go.id',
        ],
        'sp2kp' => [
            'adapter' => Sp2kpAdapter::class,
            'name' => 'Pasar & Kebutuhan Pokok',
            'category' => 'pemantauan',
            'description' => 'Harga dan ketersediaan kebutuhan pokok dari Kemendag',
            'source_url' => 'https://sp2kp.kemendag.go.id',
        ],
        'bps' => [
            'adapter' => BpsApiAdapter::class,
            'name' => 'BPS',
            'category' => 'pemantauan',
            'description' => 'Data statistik dari Badan Pusat Statistik',
            'source_url' => 'https://webapi.bps.go.id',
        ],
        'irbi' => [
            'adapter' => InariskAdapter::class,
            'name' => 'Indeks Risiko Bencana',
            'category' => 'kebencanaan',
            'description' => 'Indeks Risiko Bencana Indonesia dari BNPB',
            'source_url' => 'https://inarisk.bnpb.go.id',
        ],
        'sitaba' => [
            'adapter' => SitabaAdapter::class,
            'name' => 'Bencana Terkini',
            'category' => 'kebencanaan',
            'description' => 'Informasi bencana terkini dari PUPR',
            'source_url' => 'https://sitaba.pu.go.id/bencana-terkini',
        ],
        'apbd' => [
            'adapter' => ApbdAdapter::class,
            'name' => 'Monitoring APBD',
            'category' => 'apbd',
            'description' => 'Data TKDD dari Kementerian Keuangan',
            'source_url' => 'https://djpk.kemenkeu.go.id/portal/data/tkdd',
        ],
    ];

    /**
     * Get all source definitions.
     *
     * @return array<string, array{adapter: class-string<PublicDataSourceAdapter>, name: string, category: string, description: string, source_url: string|null}>
     */
    public static function all(): array
    {
        return self::SOURCES;
    }

    /**
     * Get the adapter class for a source key.
     */
    public static function adapterClass(string $key): ?string
    {
        return self::SOURCES[$key]['adapter'] ?? null;
    }

    /**
     * Get a source definition by key.
     */
    public static function get(string $key): ?array
    {
        return self::SOURCES[$key] ?? null;
    }

    /**
     * Get all source keys for a given category.
     *
     * @return array<int, string>
     */
    public static function keysForCategory(string $category): array
    {
        return array_keys(array_filter(self::SOURCES, fn (array $s) => $s['category'] === $category));
    }

    /**
     * Resolve and instantiate the adapter for a given source.
     */
    public static function resolve(string $key): ?PublicDataSourceAdapter
    {
        $class = self::adapterClass($key);

        if ($class === null || ! class_exists($class)) {
            return null;
        }

        return new $class;
    }

    /**
     * Sync a single source by key.
     */
    public static function sync(string $key): array
    {
        $source = PublicDataSource::where('key', $key)->first();

        if ($source === null || ! $source->enabled) {
            return ['status' => 'skipped', 'message' => "Sumber '{$key}' tidak aktif."];
        }

        $adapter = self::resolve($key);

        if ($adapter === null) {
            return ['status' => 'error', 'message' => "Adapter untuk '{$key}' tidak ditemukan."];
        }

        $startMs = microtime(true);

        try {
            $source->update([
                'status' => PublicDataSource::STATUS_PENDING,
                'last_sync_at' => now(),
            ]);

            $raw = $adapter->fetch($source, $source->config ?? []);
            $records = $adapter->normalize($raw, $source);
            $result = $adapter->store($records, $source);

            $durationMs = (int) ((microtime(true) - $startMs) * 1000);

            $source->update([
                'status' => PublicDataSource::STATUS_SUCCESS,
                'last_success_at' => now(),
                'last_error' => null,
                'record_count' => $result['created'] + $result['updated'],
                'sync_duration_ms' => $durationMs,
            ]);

            return [
                'status' => 'success',
                'records' => $result,
                'duration_ms' => $durationMs,
            ];
        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startMs) * 1000);

            $source->update([
                'status' => PublicDataSource::STATUS_FAILED,
                'last_error' => $e->getMessage(),
                'sync_duration_ms' => $durationMs,
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ];
        }
    }

    /**
     * Seed the database registry with all known sources.
     */
    public static function seed(): void
    {
        foreach (self::SOURCES as $key => $def) {
            PublicDataSource::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $def['name'],
                    'category' => $def['category'],
                    'description' => $def['description'],
                    'source_url' => $def['source_url'],
                    'adapter_class' => $def['adapter'],
                    'enabled' => true,
                    'status' => PublicDataSource::STATUS_PENDING,
                ]
            );
        }
    }
}
