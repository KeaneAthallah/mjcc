<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ManagesApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Http\Responses\ApiResponse;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    use ManagesApiResource;

    protected string $apiModel = Subject::class;

    protected string $apiResource = SubjectResource::class;

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

    public function show(Subject $subject): JsonResponse
    {
        $this->authorize('view', $subject);

        return ApiResponse::success($this->resourceFor($subject));
    }

    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $this->authorize('create', Subject::class);

        $subject = Subject::create($request->validated());

        return ApiResponse::success($this->resourceFor($subject), 'Data mata pelajaran berhasil ditambahkan.', 201);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        $this->authorize('update', $subject);

        $subject->update($request->validated());

        return ApiResponse::success($this->resourceFor($subject), 'Data mata pelajaran berhasil diperbarui.');
    }

    public function destroy(Subject $subject): JsonResponse
    {
        $this->authorize('delete', $subject);

        $subject->delete();

        return ApiResponse::success(null, 'Data mata pelajaran berhasil dihapus.');
    }
}
