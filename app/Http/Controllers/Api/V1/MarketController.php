<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ManagesApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMarketRequest;
use App\Http\Requests\UpdateMarketRequest;
use App\Http\Resources\MarketResource;
use App\Http\Responses\ApiResponse;
use App\Models\Market;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    use ManagesApiResource;

    protected string $apiModel = Market::class;

    protected string $apiResource = MarketResource::class;

    protected array $apiSearchable = ['name'];

    /** @var string[] */
    protected array $apiFilterable = ['kecamatan_id', 'status'];

    /** @var string[] */
    protected array $apiSortable = ['name', 'created_at', 'updated_at'];

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

    public function show(Market $market): JsonResponse
    {
        $this->authorize('view', $market);

        $market->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($market));
    }

    public function store(StoreMarketRequest $request): JsonResponse
    {
        $this->authorize('create', Market::class);

        $market = Market::create($request->validated());
        $market->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($market), 'Data pasar berhasil ditambahkan.', 201);
    }

    public function update(UpdateMarketRequest $request, Market $market): JsonResponse
    {
        $this->authorize('update', $market);

        $market->update($request->validated());
        $market->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($market), 'Data pasar berhasil diperbarui.');
    }

    public function destroy(Market $market): JsonResponse
    {
        $this->authorize('delete', $market);

        $market->delete();

        return ApiResponse::success(null, 'Data pasar berhasil dihapus.');
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
