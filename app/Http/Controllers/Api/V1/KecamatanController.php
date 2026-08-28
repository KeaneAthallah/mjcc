<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ManagesApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKecamatanRequest;
use App\Http\Requests\UpdateKecamatanRequest;
use App\Http\Resources\KecamatanResource;
use App\Http\Resources\KelurahanResource;
use App\Http\Responses\ApiResponse;
use App\Models\Kecamatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KecamatanController extends Controller
{
    use ManagesApiResource;

    protected string $apiModel = Kecamatan::class;

    protected string $apiResource = KecamatanResource::class;

    protected array $apiSearchable = ['name', 'code'];

    /** @var string[] */
    protected array $apiFilterable = ['is_active'];

    /** @var string[] */
    protected array $apiSortable = ['name', 'code', 'created_at', 'updated_at'];

    protected string $apiDefaultSort = 'name';

    /** @var string[] */
    protected array $apiWith = [];

    public function index(Request $request): JsonResponse
    {
        return $this->indexResource($request);
    }

    public function trash(Request $request): JsonResponse
    {
        return $this->trashResource($request);
    }

    public function show(Kecamatan $kecamatan): JsonResponse
    {
        $this->authorize('view', $kecamatan);

        return ApiResponse::success($this->resourceFor($kecamatan));
    }

    public function store(StoreKecamatanRequest $request): JsonResponse
    {
        $this->authorize('create', Kecamatan::class);

        $kecamatan = Kecamatan::create($request->validated());

        return ApiResponse::success($this->resourceFor($kecamatan), 'Data kecamatan berhasil ditambahkan.', 201);
    }

    public function update(UpdateKecamatanRequest $request, Kecamatan $kecamatan): JsonResponse
    {
        $this->authorize('update', $kecamatan);

        $kecamatan->update($request->validated());

        return ApiResponse::success($this->resourceFor($kecamatan), 'Data kecamatan berhasil diperbarui.');
    }

    public function destroy(Kecamatan $kecamatan): JsonResponse
    {
        $this->authorize('delete', $kecamatan);

        $kecamatan->delete();

        return ApiResponse::success(null, 'Data kecamatan berhasil dihapus.');
    }

    public function restore(int $id): JsonResponse
    {
        return $this->restoreResource($id);
    }

    public function forceDestroy(int $id): JsonResponse
    {
        return $this->forceDestroyResource($id);
    }

    public function kelurahans(Kecamatan $kecamatan): JsonResponse
    {
        $this->authorize('view', $kecamatan);

        $kelurahans = $kecamatan->kelurahans()->orderBy('name')->get();

        return ApiResponse::success(KelurahanResource::collection($kelurahans), 'Data kelurahan berhasil diambil.');
    }
}
