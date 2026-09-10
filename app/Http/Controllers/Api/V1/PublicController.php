<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HealthFacilityResource;
use App\Http\Resources\KelurahanResource;
use App\Http\Resources\MarketResource;
use App\Http\Resources\PolsekResource;
use App\Http\Resources\PoskamlingResource;
use App\Http\Resources\SchoolResource;
use App\Http\Resources\TipkamtikmasResource;
use App\Http\Responses\ApiResponse;
use App\Models\HealthFacility;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;
use App\Services\DashboardService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public, unauthenticated "Data Publik" endpoints.
 *
 * These expose read-only sector data (Pendidikan, Kesehatan, Ketertiban,
 * Fasilitas Publik) using the same paginated/search/filter/sort contract as
 * the internal `apiResource` endpoints, so the mobile app can browse public
 * information without a token and without exposing administrative fields.
 */
class PublicController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    /**
     * Sector statistics for the Data Publik hub.
     */
    public function overview(): JsonResponse
    {
        $stats = $this->dashboard->overviewStats();

        return ApiResponse::success([
            'pendidikan' => [
                'sekolah' => (int) $stats['total_sekolah'],
                'siswa' => (int) $stats['total_siswa'],
                'guru' => (int) $stats['total_guru'],
                'sd' => (int) $stats['total_sd'],
                'smp' => (int) $stats['total_smp'],
            ],
            'kesehatan' => [
                'faskes' => (int) $stats['total_faskes'],
                'puskesmas' => (int) $stats['total_puskesmas'],
                'pustu' => (int) $stats['total_pustu'],
                'rs' => (int) $stats['total_rs'],
                'posyandu' => (int) $stats['total_posyandu'],
                'dokter' => (int) $stats['total_dokter'],
                'perawat' => (int) $stats['total_perawat'],
                'bidan' => (int) $stats['total_bidan'],
            ],
            'ketertiban' => [
                'polsek' => (int) $stats['total_polsek'],
                'poskamling' => (int) $stats['total_poskamling'],
                'poskamling_aktif' => (int) $stats['poskamling_aktif'],
                'tipkamtikmas' => (int) $stats['total_tipkamtikmas'],
            ],
            'fasilitas' => [
                'pasar' => (int) $stats['total_pasar'],
                'kecamatan' => (int) $stats['kecamatan'],
                'kelurahan' => (int) $stats['kelurahan'],
                'penduduk' => (int) $stats['population'],
            ],
        ]);
    }

    public function schools(Request $request): JsonResponse
    {
        return $this->paginate($request, SchoolConfig::get());
    }

    public function school(int $id): JsonResponse
    {
        return $this->show($id, SchoolConfig::get());
    }

    public function healthFacilities(Request $request): JsonResponse
    {
        return $this->paginate($request, HealthConfig::get());
    }

    public function healthFacility(int $id): JsonResponse
    {
        return $this->show($id, HealthConfig::get());
    }

    public function polseks(Request $request): JsonResponse
    {
        return $this->paginate($request, PolsekConfig::get());
    }

    public function polsek(int $id): JsonResponse
    {
        return $this->show($id, PolsekConfig::get());
    }

    public function poskamlings(Request $request): JsonResponse
    {
        return $this->paginate($request, PoskamlingConfig::get());
    }

    public function poskamling(int $id): JsonResponse
    {
        return $this->show($id, PoskamlingConfig::get());
    }

    public function tipkamtikmas(Request $request): JsonResponse
    {
        return $this->paginate($request, TipkamtikmasConfig::get());
    }

    public function tipkamtikmasShow(int $id): JsonResponse
    {
        return $this->show($id, TipkamtikmasConfig::get());
    }

    public function markets(Request $request): JsonResponse
    {
        return $this->paginate($request, MarketConfig::get());
    }

    public function market(int $id): JsonResponse
    {
        return $this->show($id, MarketConfig::get());
    }

    public function kelurahans(Request $request): JsonResponse
    {
        return $this->paginate($request, KelurahanConfig::get());
    }

    public function kelurahan(int $id): JsonResponse
    {
        return $this->show($id, KelurahanConfig::get());
    }

    /**
     * Paginated list mirroring `ManagesApiResource::indexResource` but
     * without authorization, using per-entity config.
     *
     * @param  array{class: class-string<Model>, resource: class-string<JsonResource>,
     *               searchable: string[], filterable: string[], sortable: string[],
     *               default_sort: string, with: string[]}  $config
     */
    private function paginate(Request $request, array $config): JsonResponse
    {
        /** @var Builder<Model> $query */
        $query = $config['class']::query()->with($config['with']);

        if ($request->filled('search') && $config['searchable']) {
            $term = $request->string('search')->toString();
            $query->where(function (Builder $sub) use ($config, $term) {
                foreach ($config['searchable'] as $column) {
                    $sub->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        }

        foreach ($config['filterable'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        $sort = $request->string('sort')->toString();
        if (! in_array($sort, $config['sortable'], true)) {
            $sort = $config['default_sort'];
        }

        $direction = strtolower($request->string('sort_direction', 'asc')->toString()) === 'desc' ? 'desc' : 'asc';

        $perPage = max(1, min(100, $request->integer('per_page', 20)));

        $paginator = $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();

        $items = array_map(
            fn (Model $model) => $config['resource']::make($model)->resolve(),
            $paginator->items(),
        );

        return ApiResponse::paginate($items, $paginator, 'Data berhasil diambil');
    }

    /**
     * Single record detail.
     *
     * @param  array{class: class-string<Model>, resource: class-string<JsonResource>,
     *               searchable: string[], filterable: string[], sortable: string[],
     *               default_sort: string, with: string[]}  $config
     */
    private function show(int $id, array $config): JsonResponse
    {
        /** @var Model $model */
        $model = $config['class']::with($config['with'])->findOrFail($id);

        return ApiResponse::success($config['resource']::make($model)->resolve());
    }
}

/**
 * Read-only per-entity config for public resource endpoints.
 */
final class SchoolConfig
{
    /** @return array{
     *   class: class-string<Model>, resource: class-string<JsonResource>,
     *   searchable: string[], filterable: string[], sortable: string[],
     *   default_sort: string, with: string[]
     * } */
    public static function get(): array
    {
        return [
            'class' => School::class,
            'resource' => SchoolResource::class,
            'searchable' => ['name', 'npsn'],
            'filterable' => ['kecamatan_id', 'school_type', 'is_active'],
            'sortable' => ['name', 'school_type', 'created_at'],
            'default_sort' => 'name',
            'with' => ['kecamatan:id,name', 'kelurahan:id,name', 'subjects:id,name'],
        ];
    }
}

/**
 * @see SchoolConfig
 */
final class HealthConfig
{
    /** @return array{
     *   class: class-string<Model>, resource: class-string<JsonResource>,
     *   searchable: string[], filterable: string[], sortable: string[],
     *   default_sort: string, with: string[]
     * } */
    public static function get(): array
    {
        return [
            'class' => HealthFacility::class,
            'resource' => HealthFacilityResource::class,
            'searchable' => ['name', 'address'],
            'filterable' => ['kecamatan_id', 'facility_type', 'status'],
            'sortable' => ['name', 'facility_type', 'created_at'],
            'default_sort' => 'name',
            'with' => ['kecamatan:id,name'],
        ];
    }
}

/**
 * @see SchoolConfig
 */
final class PolsekConfig
{
    /** @return array{
     *   class: class-string<Model>, resource: class-string<JsonResource>,
     *   searchable: string[], filterable: string[], sortable: string[],
     *   default_sort: string, with: string[]
     * } */
    public static function get(): array
    {
        return [
            'class' => Polsek::class,
            'resource' => PolsekResource::class,
            'searchable' => ['name', 'address'],
            'filterable' => ['kecamatan_id', 'status'],
            'sortable' => ['name', 'created_at'],
            'default_sort' => 'name',
            'with' => ['kecamatan:id,name'],
        ];
    }
}

/**
 * @see SchoolConfig
 */
final class PoskamlingConfig
{
    /** @return array{
     *   class: class-string<Model>, resource: class-string<JsonResource>,
     *   searchable: string[], filterable: string[], sortable: string[],
     *   default_sort: string, with: string[]
     * } */
    public static function get(): array
    {
        return [
            'class' => Poskamling::class,
            'resource' => PoskamlingResource::class,
            'searchable' => ['name'],
            'filterable' => ['kecamatan_id', 'is_active', 'status'],
            'sortable' => ['name', 'created_at'],
            'default_sort' => 'name',
            'with' => ['kecamatan:id,name', 'kelurahan:id,name'],
        ];
    }
}

/**
 * @see SchoolConfig
 */
final class TipkamtikmasConfig
{
    /** @return array{
     *   class: class-string<Model>, resource: class-string<JsonResource>,
     *   searchable: string[], filterable: string[], sortable: string[],
     *   default_sort: string, with: string[]
     * } */
    public static function get(): array
    {
        return [
            'class' => Tipkamtikmas::class,
            'resource' => TipkamtikmasResource::class,
            'searchable' => ['title', 'description'],
            'filterable' => ['kecamatan_id', 'status'],
            'sortable' => ['title', 'created_at'],
            'default_sort' => 'title',
            'with' => ['kecamatan:id,name', 'kelurahan:id,name'],
        ];
    }
}

/**
 * @see SchoolConfig
 */
final class MarketConfig
{
    /** @return array{
     *   class: class-string<Model>, resource: class-string<JsonResource>,
     *   searchable: string[], filterable: string[], sortable: string[],
     *   default_sort: string, with: string[]
     * } */
    public static function get(): array
    {
        return [
            'class' => Market::class,
            'resource' => MarketResource::class,
            'searchable' => ['name', 'address'],
            'filterable' => ['kecamatan_id', 'status'],
            'sortable' => ['name', 'created_at'],
            'default_sort' => 'name',
            'with' => ['kecamatan:id,name'],
        ];
    }
}

/**
 * @see SchoolConfig
 */
final class KelurahanConfig
{
    /** @return array{
     *   class: class-string<Model>, resource: class-string<JsonResource>,
     *   searchable: string[], filterable: string[], sortable: string[],
     *   default_sort: string, with: string[]
     * } */
    public static function get(): array
    {
        return [
            'class' => Kelurahan::class,
            'resource' => KelurahanResource::class,
            'searchable' => ['name', 'code'],
            'filterable' => ['kecamatan_id', 'status'],
            'sortable' => ['name', 'created_at'],
            'default_sort' => 'name',
            'with' => ['kecamatan:id,name'],
        ];
    }
}
