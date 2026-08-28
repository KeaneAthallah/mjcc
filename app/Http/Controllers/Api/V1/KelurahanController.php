<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ManagesApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKelurahanRequest;
use App\Http\Requests\UpdateKelurahanRequest;
use App\Http\Resources\KelurahanResource;
use App\Http\Responses\ApiResponse;
use App\Models\Kelurahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KelurahanController extends Controller
{
    use ManagesApiResource;

    protected string $apiModel = Kelurahan::class;

    protected string $apiResource = KelurahanResource::class;

    protected array $apiSearchable = ['name', 'code'];

    /** @var string[] */
    protected array $apiFilterable = ['kecamatan_id', 'status'];

    /** @var string[] */
    protected array $apiSortable = ['name', 'code', 'created_at', 'updated_at'];

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

    public function show(Kelurahan $kelurahan): JsonResponse
    {
        $this->authorize('view', $kelurahan);

        $kelurahan->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($kelurahan));
    }

    public function store(StoreKelurahanRequest $request): JsonResponse
    {
        $this->authorize('create', Kelurahan::class);

        $kelurahan = Kelurahan::create($request->validated());
        $kelurahan->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($kelurahan), 'Data kelurahan berhasil ditambahkan.', 201);
    }

    public function update(UpdateKelurahanRequest $request, Kelurahan $kelurahan): JsonResponse
    {
        $this->authorize('update', $kelurahan);

        $kelurahan->update($request->validated());
        $kelurahan->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($kelurahan), 'Data kelurahan berhasil diperbarui.');
    }

    public function destroy(Kelurahan $kelurahan): JsonResponse
    {
        $this->authorize('delete', $kelurahan);

        $kelurahan->delete();

        return ApiResponse::success(null, 'Data kelurahan berhasil dihapus.');
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
