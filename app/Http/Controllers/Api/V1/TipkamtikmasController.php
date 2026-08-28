<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ManagesApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTipkamtikmasRequest;
use App\Http\Requests\UpdateTipkamtikmasRequest;
use App\Http\Resources\TipkamtikmasResource;
use App\Http\Responses\ApiResponse;
use App\Models\Tipkamtikmas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TipkamtikmasController extends Controller
{
    use ManagesApiResource;

    protected string $apiModel = Tipkamtikmas::class;

    protected string $apiResource = TipkamtikmasResource::class;

    protected array $apiSearchable = ['title', 'description'];

    /** @var string[] */
    protected array $apiFilterable = ['kecamatan_id', 'kelurahan_id', 'status'];

    /** @var string[] */
    protected array $apiSortable = ['title', 'created_at', 'updated_at'];

    protected string $apiDefaultSort = 'title';

    /** @var string[] */
    protected array $apiWith = ['kecamatan:id,name', 'kelurahan:id,name'];

    public function index(Request $request): JsonResponse
    {
        return $this->indexResource($request);
    }

    public function trash(Request $request): JsonResponse
    {
        return $this->trashResource($request);
    }

    public function show(Tipkamtikmas $tipkamtikma): JsonResponse
    {
        $this->authorize('view', $tipkamtikma);

        $tipkamtikma->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($tipkamtikma));
    }

    public function store(StoreTipkamtikmasRequest $request): JsonResponse
    {
        $this->authorize('create', Tipkamtikmas::class);

        $tipkamtikma = Tipkamtikmas::create($request->validated());
        $tipkamtikma->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($tipkamtikma), 'Data tipkamtikmas berhasil ditambahkan.', 201);
    }

    public function update(UpdateTipkamtikmasRequest $request, Tipkamtikmas $tipkamtikma): JsonResponse
    {
        $this->authorize('update', $tipkamtikma);

        $tipkamtikma->update($request->validated());
        $tipkamtikma->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($tipkamtikma), 'Data tipkamtikmas berhasil diperbarui.');
    }

    public function destroy(Tipkamtikmas $tipkamtikma): JsonResponse
    {
        $this->authorize('delete', $tipkamtikma);

        $tipkamtikma->delete();

        return ApiResponse::success(null, 'Data tipkamtikmas berhasil dihapus.');
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
