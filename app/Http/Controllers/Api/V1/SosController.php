<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AcceptSosAlertRequest;
use App\Http\Requests\Api\AcknowledgeSosAlertRequest;
use App\Http\Requests\Api\ResolveSosAlertRequest;
use App\Http\Requests\Api\RespondSosAlertRequest;
use App\Http\Requests\Api\StoreSosAlertRequest;
use App\Http\Requests\Api\UpdateResponderLocationRequest;
use App\Http\Resources\ResponderLocationResource;
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
                ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'acknowledged' THEN 1 WHEN 'responding' THEN 2 WHEN 'accepted' THEN 3 WHEN 'on_the_way' THEN 4 WHEN 'arrived' THEN 5 WHEN 'resolved' THEN 6 ELSE 7 END")
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

        $sos->load(['user:id,name,email,role', 'respondedBy:id,name', 'resolvedBy:id,name', 'acceptedBy:id,name']);

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
     * Responder accepts an active SOS.
     */
    public function accept(AcceptSosAlertRequest $request, SosAlert $sos): JsonResponse
    {
        $sos = $this->service->accept($sos, $request->user());

        $sos->load(['user:id,name,email,role', 'respondedBy:id,name', 'resolvedBy:id,name', 'acceptedBy:id,name']);

        return ApiResponse::success(new SosAlertResource($sos), 'SOS berhasil diterima oleh petugas.');
    }

    /**
     * Responder marks themselves as on the way.
     */
    public function onTheWay(Request $request, SosAlert $sos): JsonResponse
    {
        $sos = $this->service->onTheWay($sos, $request->user());

        $sos->load(['user:id,name,email,role', 'respondedBy:id,name', 'resolvedBy:id,name', 'acceptedBy:id,name']);

        return ApiResponse::success(new SosAlertResource($sos), 'Status diperbarui menjadi sedang menuju lokasi.');
    }

    /**
     * Responder marks themselves as arrived.
     */
    public function arrived(Request $request, SosAlert $sos): JsonResponse
    {
        $sos = $this->service->arrived($sos, $request->user());

        $sos->load(['user:id,name,email,role', 'respondedBy:id,name', 'resolvedBy:id,name', 'acceptedBy:id,name']);

        return ApiResponse::success(new SosAlertResource($sos), 'Status diperbarui menjadi tiba di lokasi.');
    }

    /**
     * Update responder's live location.
     */
    public function updateLocation(UpdateResponderLocationRequest $request, SosAlert $sos): JsonResponse
    {
        $this->authorize('updateLocation', $sos);

        $this->service->updateResponderLocation(
            $sos->id,
            $request->user(),
            $request->validated('latitude'),
            $request->validated('longitude'),
        );

        return ApiResponse::success(null, 'Lokasi responder berhasil diperbarui.');
    }

    /**
     * Latest live location of each responder assigned to an alert, so the
     * requester can watch the petugas approach on a map.
     */
    public function responderLocations(SosAlert $sos): JsonResponse
    {
        $this->authorize('viewResponderLocations', $sos);

        $locations = $this->service->getLatestResponderLocations($sos);

        return ApiResponse::success(
            ResponderLocationResource::collection($locations)->resolve(),
            'Lokasi petugas berhasil diambil.',
        );
    }

    /**
     * Active incidents matching the responder's type.
     */
    public function activeIncidents(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isResponder() && ! $user->isAdmin()) {
            return ApiResponse::error('Hanya responder yang dapat melihat insiden aktif.', 403);
        }

        $incidents = $this->service->getActiveIncidentsForResponder($user);

        return ApiResponse::success(
            SosAlertResource::collection($incidents)->resolve(),
            'Insiden aktif berhasil diambil.',
        );
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
            SosAlert::STATUS_ACCEPTED => SosAlert::STATUS_ACCEPTED,
            SosAlert::STATUS_ON_THE_WAY => SosAlert::STATUS_ON_THE_WAY,
            SosAlert::STATUS_ARRIVED => SosAlert::STATUS_ARRIVED,
            SosAlert::STATUS_RESOLVED => SosAlert::STATUS_RESOLVED,
            SosAlert::STATUS_CANCELLED => SosAlert::STATUS_CANCELLED,
        ];
    }

    private function perPage(Request $request): int
    {
        return max(1, min(100, $request->integer('per_page', 20)));
    }
}
