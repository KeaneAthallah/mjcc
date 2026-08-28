<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Http\Responses\ApiResponse;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ActivityLog::class);

        $query = ActivityLog::query()
            ->with('user:id,name,email')
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                $q->where(function (Builder $sub) use ($request) {
                    $sub->where('resource_type', 'like', '%'.$request->string('search').'%')
                        ->orWhere('action', 'like', '%'.$request->string('search').'%');
                });
            })
            ->when($request->filled('user'), fn (Builder $q) => $q->where('user_id', $request->integer('user')))
            ->when($request->filled('action'), fn (Builder $q) => $q->where('action', $request->string('action')))
            ->when($request->filled('resource'), fn (Builder $q) => $q->where('resource_type', $request->string('resource')))
            ->when($request->filled('from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->date('to')));

        $perPage = max(1, min(100, $request->integer('per_page', 20)));

        $paginator = $query->latest()->paginate($perPage)->withQueryString();

        $items = ActivityLogResource::collection($paginator->items())->resolve();

        return ApiResponse::paginate($items, $paginator, 'Data aktivitas berhasil diambil');
    }
}
