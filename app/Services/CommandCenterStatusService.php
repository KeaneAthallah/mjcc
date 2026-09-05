<?php

namespace App\Services;

use App\Models\HealthFacility;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Menghitung "Status Morowali" dari agregasi data nyata.
 *
 * Skor (0–100) dihitung per sektor dari aturan-aturan yang terpusat dan dapat
 * dikonfigurasi di config/command-center.php. Status keseluruhan adalah
 * rata-rata terbobot skor sektor. Tidak ada nilai yang dikarang — setiap angka
 * berasal dari agregasi database.
 */
class CommandCenterStatusService
{
    private const CACHE_TTL = 60;

    private const SCOPES = [
        'schools' => School::class,
        'poskamlings' => Poskamling::class,
        'tipkamtikmas' => Tipkamtikmas::class,
        'polseks' => Polsek::class,
        'markets' => Market::class,
        'health_facilities' => HealthFacility::class,
    ];

    /**
     * Rangkuman status keseluruhan + per sektor.
     *
     * @return array<string, mixed>
     */
    public function overall(bool $force = false): array
    {
        $callback = function () {
            $sectors = collect(config('command-center.sectors'))
                ->map(fn ($cfg, $key) => $this->sector($key, $cfg));

            $scored = $sectors->filter(fn ($s) => $s['score'] !== null);
            $weights = collect(config('command-center.overall.weights'));

            if ($scored->isEmpty()) {
                $status = 'tidak_ada_data';

                return [
                    'status' => $status,
                    'label' => config('command-center.status_labels.'.($status)),
                    'score' => null,
                    'sectors' => $sectors->map(fn ($s) => $s['status'])->all(),
                    'sector_details' => $sectors->all(),
                    'no_data' => true,
                ];
            }

            $score = $scored->sum(fn ($s) => $s['score'] * $weights->get($s['key'], 0))
                / max($scored->sum(fn ($s) => $weights->get($s['key'], 0)), 1);

            $score = round($score, 1);

            return [
                'status' => $this->statusFor($score),
                'label' => $this->labelFor($this->statusFor($score)),
                'score' => $score,
                'sectors' => $sectors->map(fn ($s) => $s['status'])->all(),
                'sector_details' => $sectors->all(),
                'no_data' => false,
            ];
        };

        if (! $force) {
            $result = Cache::get('command-center.status.overall');

            if ($result !== null) {
                return $result;
            }
        }

        $payload = $callback();

        Cache::put('command-center.status.overall', $payload, self::CACHE_TTL);

        return $payload;
    }

    /**
     * Rangkuman status untuk satu kecamatan (aturan dibatasi by kecamatan_id).
     *
     * @return array<string, mixed>
     */
    public function forKecamatan(int $kecamatanId, bool $force = false): array
    {
        $cacheKey = "command-center.status.kecamatan.{$kecamatanId}";

        $callback = function () use ($kecamatanId) {
            $sectors = collect(config('command-center.sectors'))
                ->map(fn ($cfg, $key) => $this->sector($key, $cfg, $kecamatanId));

            $scored = $sectors->filter(fn ($s) => $s['score'] !== null);
            $weights = collect(config('command-center.overall.weights'));

            $status = $scored->isEmpty() ? 'tidak_ada_data' : null;

            $score = $status === null
                ? round($scored->sum(fn ($s) => $s['score'] * $weights->get($s['key'], 0))
                    / max($scored->sum(fn ($s) => $weights->get($s['key'], 0)), 1), 1)
                : null;

            return [
                'kecamatan_id' => $kecamatanId,
                'status' => $status ?? $this->statusFor($score),
                'label' => $status ? config('command-center.status_labels.tidak_ada_data') : $this->labelFor($this->statusFor($score)),
                'score' => $score,
                'sectors' => $sectors->map(fn ($s) => $s['status'])->all(),
                'sector_details' => $sectors->all(),
                'no_data' => $status === 'tidak_ada_data',
            ];
        };

        if (! $force) {
            $result = Cache::get($cacheKey);

            if ($result !== null) {
                return $result;
            }
        }

        $payload = $callback();

        Cache::put($cacheKey, $payload, self::CACHE_TTL);

        return $payload;
    }

    /**
     * Skor dan rincian aturan untuk satu sektor (dapat dibatasi kecamatan).
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function sector(string $key, array $config, ?int $kecamatanId = null): array
    {
        $rules = collect($config['rules'])
            ->map(fn ($rule, $ruleKey) => [
                'key' => $ruleKey,
                'label' => $rule['label'] ?? ucfirst($ruleKey),
                'weight' => (float) $rule['weight'],
                'score' => $this->evaluateRule($rule, $kecamatanId),
            ]);

        $scored = $rules->filter(fn ($r) => $r['score'] !== null);
        $totalWeight = $scored->sum('weight');

        $score = $totalWeight > 0
            ? round($scored->sum(fn ($r) => $r['score'] * $r['weight']) / $totalWeight, 1)
            : null;

        return [
            'key' => $key,
            'label' => $config['label'] ?? ucfirst($key),
            'score' => $score,
            'status' => $score === null ? 'tidak_ada_data' : $this->statusFor($score),
            'rules' => $rules->values()->all(),
            'kecamatan_id' => $kecamatanId,
        ];
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function evaluateRule(array $rule, ?int $kecamatanId = null): ?float
    {
        $scope = $rule['scope'] ?? null;
        $query = $this->scopeQuery((string) $scope, $kecamatanId);

        if ($query === null) {
            return null;
        }

        return match ($rule['type'] ?? '') {
            'good_conditions' => $this->goodConditionsPercentage($query, $rule['column'] ?? 'condition'),
            'has_staff' => $this->hasStaffPercentage($query),
            'non_zero' => $this->nonZeroPercentage($query, $rule['column'] ?? ''),
            'active_status' => $this->activeStatusPercentage($query, $rule['column'] ?? 'status'),
            'active_boolean' => $this->activeBooleanPercentage($query, $rule['column'] ?? 'is_active'),
            'average_percentages' => $this->averagePercentages($query, $rule['columns'] ?? []),
            default => null,
        };
    }

    private function scopeQuery(?string $scope, ?int $kecamatanId = null): ?Builder
    {
        $model = self::SCOPES[$scope] ?? null;

        if ($model === null) {
            return null;
        }

        $query = $model::query();

        if ($kecamatanId !== null) {
            $query->where('kecamatan_id', $kecamatanId);
        }

        return $query;
    }

    private function percentage(int $numerator, int $denominator): ?float
    {
        if ($denominator <= 0) {
            return null;
        }

        return round(($numerator / $denominator) * 100, 1);
    }

    private function goodConditionsPercentage(Builder $query, string $column): ?float
    {
        $conditions = config('command-center.good_conditions', ['baik']);

        return $this->percentage(
            (clone $query)->whereIn($column, $conditions)->count(),
            (clone $query)->count(),
        );
    }

    private function hasStaffPercentage(Builder $query): ?float
    {
        return $this->percentage(
            (clone $query)->where(function (Builder $q) {
                $q->where('doctors', '>', 0)
                    ->orWhere('nurses', '>', 0)
                    ->orWhere('midwives', '>', 0);
            })->count(),
            (clone $query)->count(),
        );
    }

    private function nonZeroPercentage(Builder $query, string $column): ?float
    {
        if ($column === '') {
            return null;
        }

        return $this->percentage(
            (clone $query)->where($column, '>', 0)->count(),
            (clone $query)->count(),
        );
    }

    private function activeStatusPercentage(Builder $query, string $column): ?float
    {
        return $this->percentage(
            (clone $query)->where($column, 'aktif')->count(),
            (clone $query)->count(),
        );
    }

    private function activeBooleanPercentage(Builder $query, string $column): ?float
    {
        return $this->percentage(
            (clone $query)->where($column, true)->count(),
            (clone $query)->count(),
        );
    }

    /**
     * Rata-rata dari rata-rata kolom persentase sarana.
     *
     * @param  string[]  $columns
     */
    private function averagePercentages(Builder $query, array $columns): ?float
    {
        $averages = [];

        foreach ($columns as $column) {
            $avg = (clone $query)->avg($column);

            if ($avg !== null) {
                $averages[] = $avg;
            }
        }

        if (count($averages) === 0) {
            return null;
        }

        return round(array_sum($averages) / count($averages), 1);
    }

    private function statusFor(float $score): string
    {
        foreach (config('command-center.status_order', []) as $status) {
            $min = (float) config("command-center.status_thresholds.{$status}", 0);

            if ($score >= $min) {
                return $status;
            }
        }

        return 'kritis';
    }

    private function labelFor(string $status): string
    {
        return (string) config("command-center.status_labels.{$status}", strtoupper($status));
    }
}
