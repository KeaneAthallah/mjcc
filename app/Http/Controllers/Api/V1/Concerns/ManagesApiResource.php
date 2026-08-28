<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Http\Responses\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shared index/trash/restore/force-delete logic for API resource controllers.
 *
 * The using controller must declare:
 *
 *     protected string $apiModel = School::class;    // model class
 *     protected string $apiResource = SchoolResource::class;
 *     protected array  $apiSearchable = ['name'];
 *     protected array  $apiFilterable = ['kecamatan_id'];
 *     protected array  $apiSortable = ['name'];
 *     protected string $apiDefaultSort = 'name';
 *     protected array  $apiWith = ['kecamatan:id,name'];
 */
trait ManagesApiResource
{
    /**
     * Paginated, searchable, filterable, sortable list.
     */
    protected function indexResource(Request $request): JsonResponse
    {
        $this->authorize('viewAny', $this->apiModel);

        $query = $this->buildIndexQuery($request);

        $perPage = max(1, min(100, $request->integer('per_page', 20)));

        $paginator = $query->paginate($perPage)->withQueryString();

        return ApiResponse::paginate($this->resourceMap($paginator->items()), $paginator);
    }

    /**
     * Paginated list of trashed (soft-deleted) records.
     */
    protected function trashResource(Request $request): JsonResponse
    {
        $this->authorize('viewAny', $this->apiModel);

        $query = $this->apiModel::onlyTrashed()->with($this->apiWith);

        if ($request->filled('search') && $this->apiSearchable) {
            $this->applySearch($query, $request->string('search')->toString());
        }

        $perPage = max(1, min(100, $request->integer('per_page', 20)));

        $paginator = $query->orderByDesc('deleted_at')->paginate($perPage)->withQueryString();

        return ApiResponse::paginate($this->resourceMap($paginator->items()), $paginator, 'Data sampah berhasil diambil');
    }

    /**
     * Restore a soft-deleted record by primary key.
     */
    protected function restoreResource(int $id): JsonResponse
    {
        $model = $this->apiModel::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $model);

        $model->restore();

        return ApiResponse::success($this->resourceFor($model), 'Data berhasil dipulihkan.');
    }

    /**
     * Permanently delete a soft-deleted record (admin only via policy).
     */
    protected function forceDestroyResource(int $id): JsonResponse
    {
        $model = $this->apiModel::onlyTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $model);

        $model->forceDelete();

        return ApiResponse::success(null, 'Data dihapus permanen.');
    }

    protected function buildIndexQuery(Request $request): Builder
    {
        /** @var Builder $query */
        $query = $this->apiModel::query()->with($this->apiWith);

        if ($request->filled('search') && $this->apiSearchable) {
            $this->applySearch($query, $request->string('search')->toString());
        }

        foreach ($this->apiFilterable as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        $sort = $request->string('sort')->toString();
        if (! in_array($sort, $this->apiSortable, true)) {
            $sort = $this->apiDefaultSort;
        }

        $direction = strtolower($request->string('sort_direction', 'asc')->toString()) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction);
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $columns = $this->apiSearchable;

        $query->where(function (Builder $sub) use ($columns, $term) {
            foreach ($columns as $column) {
                $sub->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }

    protected function resourceFor(Model $model): JsonResource
    {
        return new $this->apiResource($model);
    }

    /**
     * @param  Model[]  $items
     * @return mixed[]
     */
    protected function resourceMap(array $items): array
    {
        /** @var class-string<JsonResource> $resource */
        $resource = $this->apiResource;

        return $resource::collection($items)->resolve();
    }
}
