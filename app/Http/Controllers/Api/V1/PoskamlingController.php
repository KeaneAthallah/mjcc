<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ManagesApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePoskamlingRequest;
use App\Http\Requests\UpdatePoskamlingRequest;
use App\Http\Resources\PoskamlingResource;
use App\Http\Responses\ApiResponse;
use App\Models\Poskamling;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PoskamlingController extends Controller
{
    use ManagesApiResource;

    protected string $apiModel = Poskamling::class;

    protected string $apiResource = PoskamlingResource::class;

    protected array $apiSearchable = ['name'];

    /** @var string[] */
    protected array $apiFilterable = ['kecamatan_id', 'kelurahan_id', 'status', 'is_active'];

    /** @var string[] */
    protected array $apiSortable = ['name', 'created_at', 'updated_at'];

    protected string $apiDefaultSort = 'name';

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

    public function show(Poskamling $poskamling): JsonResponse
    {
        $this->authorize('view', $poskamling);

        $poskamling->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($poskamling));
    }

    public function store(StorePoskamlingRequest $request): JsonResponse
    {
        $this->authorize('create', Poskamling::class);

        $poskamling = Poskamling::create($request->validated());
        $poskamling->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($poskamling), 'Data poskamling berhasil ditambahkan.', 201);
    }

    public function update(UpdatePoskamlingRequest $request, Poskamling $poskamling): JsonResponse
    {
        $this->authorize('update', $poskamling);

        $poskamling->update($request->validated());
        $poskamling->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($poskamling), 'Data poskamling berhasil diperbarui.');
    }

    public function destroy(Poskamling $poskamling): JsonResponse
    {
        $this->authorize('delete', $poskamling);

        $poskamling->delete();

        return ApiResponse::success(null, 'Data poskamling berhasil dihapus.');
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
