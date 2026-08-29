<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AcknowledgeSosAlertRequest;
use App\Http\Requests\Api\ResolveSosAlertRequest;
use App\Http\Requests\Api\RespondSosAlertRequest;
use App\Http\Requests\Api\StoreSosAlertRequest;
use App\Http\Resources\SosAlertResource;
use App\Http\Responses\ApiResponse;
use App\Models\SosAlert;
use App\Services\SosService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SosController extends Controller
{
    public function __construct(private readonly SosService $service) {}

    /**
     * Operators/admins browse the full inbox (filters + open first). Viewers
     * only ever see their own alerts.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->can('viewAny', SosAlert::class)) {
            $query = SosAlert::query()
                ->with(['user:id,name,email,role'])
                ->when($request->filled('status') && array_key_exists($request->string('status')->toString(), $this->statuses()), function (Builder $q) use ($request) {
                    $q->where('status', $request->string('status'));
                })
                ->when($request->filled('search'), function (Builder $q) use ($request) {
                    $q->whereHas('user', function (Builder $sub) use ($request) {
                        $sub->where('name', 'like', '%'.$request->string('search').'%');
                    });
                })
                ->when($request->filled('from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->date('from')))
                ->when($request->filled('to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->date('to')));

            $paginator = $query
                ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'acknowledged' THEN 1 WHEN 'responding' THEN 2 WHEN 'resolved' THEN 3 ELSE 4 END")
                ->latest('created_at')
                ->paginate($this->perPage($request))
                ->withQueryString();
        } else {
            $paginator = SosAlert::query()
                ->with(['user:id,name,email,role'])
                ->where('user_id', $user->id)
                ->latest('created_at')
                ->paginate($this->perPage($request))
                ->withQueryString();
        }

        $items = SosAlertResource::collection($paginator->items())->resolve();

        return ApiResponse::paginate($items, $paginator, 'Data SOS berhasil diambil');
    }

    public function show(SosAlert $sos): JsonResponse
    {
        $this->authorize('view', $sos);

        $sos->load(['user:id,name,email,role', 'respondedBy:id,name', 'resolvedBy:id,name']);

        return ApiResponse::success(new SosAlertResource($sos));
    }

    public function store(StoreSosAlertRequest $request): JsonResponse
    {
        $sos = $this->service->create($request->user(), $request->validated());

        $sos->load(['user:id,name,email,role', 'respondedBy:id,name', 'resolvedBy:id,name']);

        return ApiResponse::success(new SosAlertResource($sos), 'SOS berhasil dikirim. Lokasi Anda telah dikirim kepada petugas.', 201);
    }

    public function acknowledge(AcknowledgeSosAlertRequest $request, SosAlert $sos): JsonResponse
    {
        $sos = $this->service->acknowledge($sos, $request->string('response_message')->toString(), $request->user());

        $sos->load(['user:id,name,email,role', 'respondedBy:id,name', 'resolvedBy:id,name']);

        return ApiResponse::success(new SosAlertResource($sos), 'SOS telah diterima.');
    }

    public function respond(RespondSosAlertRequest $request, SosAlert $sos): JsonResponse
    {
        $sos = $this->service->respond($sos, $request->string('response_message')->toString(), $request->user());

        $sos->load(['user:id,name,email,role', 'respondedBy:id,name', 'resolvedBy:id,name']);

        return ApiResponse::success(new SosAlertResource($sos), 'Status SOS diperbarui menjadi sedang menuju lokasi.');
    }

    public function resolve(ResolveSosAlertRequest $request, SosAlert $sos): JsonResponse
    {
        $sos = $this->service->resolve($sos, $request->string('response_message')->toString(), $request->user());

        $sos->load(['user:id,name,email,role', 'respondedBy:id,name', 'resolvedBy:id,name']);

        return ApiResponse::success(new SosAlertResource($sos), 'SOS telah diselesaikan.');
    }

    public function cancel(Request $request, SosAlert $sos): JsonResponse
    {
        $this->authorize('cancel', $sos);

        $sos = $this->service->cancel($sos, $request->user());

        $sos->load(['user:id,name,email,role']);

        return ApiResponse::success(new SosAlertResource($sos), 'SOS telah dibatalkan.');
    }

    /**
     * Efficient open-alert count for the notification badge.
     */
    public function activeCount(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->counts($request->user()),
            'Jumlah SOS aktif berhasil diambil',
        );
    }

    /**
     * The authenticated user's own open alert (if any), used by the mobile
     * app to track a live SOS status.
     */
    public function myOpen(Request $request): JsonResponse
    {
        $sos = SosAlert::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('status', SosAlert::openStatuses())
            ->with(['user:id,name,email,role', 'respondedBy:id,name', 'resolvedBy:id,name'])
            ->latest('created_at')
            ->first();

        return ApiResponse::success($sos ? new SosAlertResource($sos) : null);
    }

    /**
     * @return array<string, string>
     */
    private function statuses(): array
    {
        return [
            SosAlert::STATUS_ACTIVE => SosAlert::STATUS_ACTIVE,
            SosAlert::STATUS_ACKNOWLEDGED => SosAlert::STATUS_ACKNOWLEDGED,
            SosAlert::STATUS_RESPONDING => SosAlert::STATUS_RESPONDING,
            SosAlert::STATUS_RESOLVED => SosAlert::STATUS_RESOLVED,
            SosAlert::STATUS_CANCELLED => SosAlert::STATUS_CANCELLED,
        ];
    }

    private function perPage(Request $request): int
    {
        return max(1, min(100, $request->integer('per_page', 20)));
    }
}
