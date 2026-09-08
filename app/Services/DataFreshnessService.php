<?php

namespace App\Services;

use App\Models\ExternalData;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Menghitung waktu pembaruan data terakhir dari data nyata (updated_at setiap
 * tabel operasional + data publik eksternal). Tidak ada nilai yang dikarang —
 * status kesegaran diturunkan langsung dari timestamp aktual.
 */
class DataFreshnessService
{
    private const CACHE_TTL = 60;

    private const TRACKED_TABLES = [
        School::class,
        HealthFacility::class,
        Polsek::class,
        Poskamling::class,
        Tipkamtikmas::class,
        Market::class,
        Kecamatan::class,
        Kelurahan::class,
        ExternalData::class,
    ];

    /**
     * @return array<string, mixed>
     */
    public function snapshot(bool $fresh = false): array
    {
        $key = 'command-center.data-freshness';

        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, self::CACHE_TTL, function () {
            $updatedAt = null;
            $tables = [];

            foreach (self::TRACKED_TABLES as $model) {
                $plural = Str::snake(Str::plural(class_basename($model)));

                $tables[$plural] = [
                    'model' => $model,
                    'updated_at' => null,
                    'age_days' => null,
                ];
            }

            foreach (self::TRACKED_TABLES as $model) {
                $plural = Str::plural(class_basename($model));
                $max = $model::query()->max('updated_at');

                $tables[$plural]['updated_at'] = $max ? (string) Carbon::parse($max) : null;

                if ($max && ($updatedAt === null || $max > $updatedAt)) {
                    $updatedAt = $max;
                }
            }

            if ($updatedAt === null) {
                foreach ($tables as &$table) {
                    $table['age_days'] = null;
                }

                return [
                    'last_update' => null,
                    'age_days' => null,
                    'status' => 'tidak_ada_data',
                    'label' => config('command-center.freshness.labels.tidak_ada_data', 'BELUM ADA DATA'),
                    'tables' => $tables,
                ];
            }

            $lastUpdate = Carbon::parse($updatedAt);
            $ageDays = (int) $lastUpdate->diffInDays(now());

            foreach ($tables as &$table) {
                $table['age_days'] = $table['updated_at'] !== null
                    ? (int) Carbon::parse($table['updated_at'])->diffInDays(now())
                    : null;
            }

            $status = match (true) {
                $ageDays < (int) config('command-center.freshness.terbaru_days', 7) => 'terbaru',
                $ageDays < (int) config('command-center.freshness.perlu_diperbarui_days', 30) => 'perlu_diperbarui',
                default => 'data_lama',
            };

            return [
                'last_update' => $lastUpdate->toDateTimeString(),
                'age_days' => $ageDays,
                'status' => $status,
                'label' => config("command-center.freshness.labels.{$status}", strtoupper($status)),
                'tables' => $tables,
            ];
        });
    }
}
