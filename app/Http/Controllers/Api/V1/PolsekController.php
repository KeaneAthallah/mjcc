<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ManagesApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePolsekRequest;
use App\Http\Requests\UpdatePolsekRequest;
use App\Http\Resources\PolsekResource;
use App\Http\Responses\ApiResponse;
use App\Models\Polsek;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PolsekController extends Controller
{
    use ManagesApiResource;

    protected string $apiModel = Polsek::class;

    protected string $apiResource = PolsekResource::class;

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

    public function show(Polsek $polsek): JsonResponse
    {
        $this->authorize('view', $polsek);

        $polsek->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($polsek));
    }

    public function store(StorePolsekRequest $request): JsonResponse
    {
        $this->authorize('create', Polsek::class);

        $polsek = Polsek::create($request->validated());
        $polsek->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($polsek), 'Data polsek berhasil ditambahkan.', 201);
    }

    public function update(UpdatePolsekRequest $request, Polsek $polsek): JsonResponse
    {
        $this->authorize('update', $polsek);

        $polsek->update($request->validated());
        $polsek->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($polsek), 'Data polsek berhasil diperbarui.');
    }

    public function destroy(Polsek $polsek): JsonResponse
    {
        $this->authorize('delete', $polsek);

        $polsek->delete();

        return ApiResponse::success(null, 'Data polsek berhasil dihapus.');
    }
}
