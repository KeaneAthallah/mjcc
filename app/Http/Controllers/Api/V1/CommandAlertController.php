<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\CommandAlert;
use App\Services\CommandAlertSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommandAlertController extends Controller
{
    public function __construct(private readonly CommandAlertSyncService $sync) {}

    /**
     * Persisted operational alerts (title/status filters mirror the web index).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CommandAlert::class);

        $this->sync->sync();

        $query = CommandAlert::query()
            ->with('kecamatan:id,name')
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->string('severity')))
            ->when($request->filled('sector'), fn ($q) => $q->where('sector_key', $request->string('sector')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('kecamatan'), fn ($q) => $q->where('kecamatan_id', $request->integer('kecamatan')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('title', 'like', '%'.$request->string('search').'%')
                        ->orWhere('description', 'like', '%'.$request->string('search').'%');
                });
            });

        $paginator = $query->latest('opened_at')->paginate(max(1, min(100, $request->integer('per_page', 20))));

        $items = $paginator->getCollection()->map(function (CommandAlert $alert) {
            return [
                'id' => $alert->id,
                'rule' => $alert->rule,
                'severity' => $alert->severity,
                'severity_label' => $alert->severityLabel(),
                'sector' => $alert->sector,
                'sector_key' => $alert->sector_key,
                'title' => $alert->title,
                'description' => $alert->description,
                'status' => $alert->status,
                'status_label' => $alert->statusLabel(),
                'is_open' => $alert->isOpen(),
                'kecamatan_id' => $alert->kecamatan_id,
                'kecamatan_name' => $alert->kecamatan?->name,
                'latitude' => $alert->latitude !== null ? (float) $alert->latitude : null,
                'longitude' => $alert->longitude !== null ? (float) $alert->longitude : null,
                'resource_type' => $alert->resource_type,
                'resource_id' => $alert->resource_id,
                'opened_at' => $alert->opened_at?->toIso8601String(),
                'last_seen_at' => $alert->last_seen_at?->toIso8601String(),
                'resolved_at' => $alert->resolved_at?->toIso8601String(),
            ];
        })->values();

        $open = CommandAlert::query()->whereIn('status', CommandAlert::openStatuses());

        $counts = [
            'critical' => (clone $open)->where('severity', CommandAlert::SEVERITY_CRITICAL)->count(),
            'warning' => (clone $open)->where('severity', CommandAlert::SEVERITY_WARNING)->count(),
            'info' => (clone $open)->where('severity', CommandAlert::SEVERITY_INFO)->count(),
            'open' => $open->count(),
        ];

        return ApiResponse::success([
            'alerts' => $items,
            'counts' => $counts,
        ], 'Alert command center', 200, [
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
