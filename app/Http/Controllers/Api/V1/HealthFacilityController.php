<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ManagesApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHealthFacilityRequest;
use App\Http\Requests\UpdateHealthFacilityRequest;
use App\Http\Resources\HealthFacilityResource;
use App\Http\Responses\ApiResponse;
use App\Models\HealthFacility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthFacilityController extends Controller
{
    use ManagesApiResource;

    protected string $apiModel = HealthFacility::class;

    protected string $apiResource = HealthFacilityResource::class;

    protected array $apiSearchable = ['name', 'phone'];

    /** @var string[] */
    protected array $apiFilterable = ['kecamatan_id', 'facility_type', 'status'];

    /** @var string[] */
    protected array $apiSortable = ['name', 'facility_type', 'created_at', 'updated_at'];

    protected string $apiDefaultSort = 'name';

    /** @var string[] */
    protected array $apiWith = ['kecamatan:id,name'];

    public function index(Request $request): JsonResponse
    {
        return $this->indexResource($request);
    }

    public function trash(Request $request): JsonResponse
    {
        return $this->trashResource($request);
    }

    public function show(HealthFacility $health_facility): JsonResponse
    {
        $this->authorize('view', $health_facility);

        $health_facility->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($health_facility));
    }

    public function store(StoreHealthFacilityRequest $request): JsonResponse
    {
        $this->authorize('create', HealthFacility::class);

        $health_facility = HealthFacility::create($request->validated());
        $health_facility->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($health_facility), 'Data fasilitas kesehatan berhasil ditambahkan.', 201);
    }

    public function update(UpdateHealthFacilityRequest $request, HealthFacility $health_facility): JsonResponse
    {
        $this->authorize('update', $health_facility);

        $health_facility->update($request->validated());
        $health_facility->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($health_facility), 'Data fasilitas kesehatan berhasil diperbarui.');
    }

    public function destroy(HealthFacility $health_facility): JsonResponse
    {
        $this->authorize('delete', $health_facility);

        $health_facility->delete();

        return ApiResponse::success(null, 'Data fasilitas kesehatan berhasil dihapus.');
    }

    public function restore(int $id): JsonResponse
    {
        return $this->restoreResource($id);
    }

    public function forceDestroy(int $id): JsonResponse
    {
        return $this->forceDestroyResource($id);
    }
}
