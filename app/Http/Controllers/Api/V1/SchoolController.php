<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ManagesApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\UpdateSchoolRequest;
use App\Http\Resources\SchoolResource;
use App\Http\Responses\ApiResponse;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    use ManagesApiResource;

    protected string $apiModel = School::class;

    protected string $apiResource = SchoolResource::class;

    protected array $apiSearchable = ['name', 'npsn'];

    /** @var string[] */
    protected array $apiFilterable = ['kecamatan_id', 'kelurahan_id', 'school_type', 'is_active'];

    /** @var string[] */
    protected array $apiSortable = ['name', 'school_type', 'created_at', 'updated_at'];

    protected string $apiDefaultSort = 'name';

    /** @var string[] */
    protected array $apiWith = ['kecamatan:id,name', 'kelurahan:id,name', 'subjects:id,name'];

    public function index(Request $request): JsonResponse
    {
        return $this->indexResource($request);
    }

    public function trash(Request $request): JsonResponse
    {
        return $this->trashResource($request);
    }

    public function show(School $school): JsonResponse
    {
        $this->authorize('view', $school);

        $school->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($school));
    }

    public function store(StoreSchoolRequest $request): JsonResponse
    {
        $this->authorize('create', School::class);

        $data = $request->validated();
        $subjects = $data['subjects'] ?? [];
        unset($data['subjects']);

        $school = School::create($data);
        $school->subjects()->sync($subjects);
        $school->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($school), 'Data sekolah berhasil ditambahkan.', 201);
    }

    public function update(UpdateSchoolRequest $request, School $school): JsonResponse
    {
        $this->authorize('update', $school);

        $data = $request->validated();
        $subjects = $data['subjects'] ?? [];
        unset($data['subjects']);

        $school->update($data);
        $school->subjects()->sync($subjects);
        $school->load($this->apiWith);

        return ApiResponse::success($this->resourceFor($school), 'Data sekolah berhasil diperbarui.');
    }

    public function destroy(School $school): JsonResponse
    {
        $this->authorize('delete', $school);

        $school->delete();

        return ApiResponse::success(null, 'Data sekolah berhasil dihapus.');
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
