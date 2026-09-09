<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ManagesApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ManagesApiResource;

    protected string $apiModel = User::class;

    protected string $apiResource = UserResource::class;

    protected array $apiSearchable = ['name', 'email'];

    /** @var string[] */
    protected array $apiFilterable = ['role'];

    /** @var string[] */
    protected array $apiSortable = ['name', 'email', 'created_at', 'updated_at'];

    protected string $apiDefaultSort = 'name';

    /** @var string[] */
    protected array $apiWith = [];

    public function __construct(private readonly ActivityLogService $activityLog) {}

    public function index(Request $request): JsonResponse
    {
        return $this->indexResource($request);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return ApiResponse::success($this->resourceFor($user));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = User::create([...$request->validated(), 'email_verified_at' => now()]);

        return ApiResponse::success($this->resourceFor($user), 'Pengguna berhasil ditambahkan.', 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $roleChanged = $request->filled('role') && $request->role !== $user->role;

        $user->update($data);

        if ($roleChanged) {
            $this->activityLog->record(
                action: ActivityLog::ACTION_ROLE_CHANGE,
                resourceType: 'User',
                resourceId: $user->id,
                oldValues: ['role' => $request->role],
                newValues: ['role' => $user->role],
                user: $request->user(),
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            );
        }

        return ApiResponse::success($this->resourceFor($user), 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        if ($user->id === $request->user()->id) {
            return ApiResponse::error('Anda tidak dapat menghapus akun sendiri.', 422);
        }

        $user->delete();

        return ApiResponse::success(null, 'Pengguna berhasil dihapus.');
    }

    public function verifyEmail(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $verified = filter_var($request->input('verified', true), FILTER_VALIDATE_BOOL);

        if ($user->hasVerifiedEmail() === $verified) {
            $message = $verified ? 'Email sudah terverifikasi.' : 'Email sudah ditandai belum terverifikasi.';
        } else {
            if ($verified) {
                $user->markEmailAsVerified();
            } else {
                $user->forceFill(['email_verified_at' => null])->save();
            }
            $message = $verified ? 'Email berhasil diverifikasi.' : 'Email berhasil ditandai belum terverifikasi.';
        }

        return ApiResponse::success($this->resourceFor($user), $message);
    }
}
