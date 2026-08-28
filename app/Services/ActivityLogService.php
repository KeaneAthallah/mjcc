<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Centralizes audit/activity logging and the query used by the log index.
 */
class ActivityLogService
{
    /**
     * Record an activity in the audit log.
     *
     * Never pass raw passwords here. Callers are responsible for stripping
     * sensitive attributes before building $newValues/$oldValues.
     */
    public function record(
        string $action,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ip,
            'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 500) : null,
        ]);
    }

    /**
     * Convenience for logging model mutations using the authenticated request
     * context. Sensitive attributes are stripped so they never reach the log.
     */
    public function logModel(
        string $action,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null,
    ): ActivityLog {
        return $this->record(
            action: $action,
            resourceType: class_basename($model),
            resourceId: $model->getKey(),
            oldValues: $oldValues,
            newValues: $newValues,
            user: $request?->user() ?? Auth::user(),
            ip: $request?->ip(),
            userAgent: $request?->userAgent(),
        );
    }

    /**
     * Query builder for the audit log list with filters applied.
     *
     * @return LengthAwarePaginator
     */
    public function paginate(Request $request)
    {
        return ActivityLog::query()
            ->with('user:id,name,email')
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                $q->where(function (Builder $sub) use ($request) {
                    $sub->where('resource_type', 'like', '%'.$request->search.'%')
                        ->orWhere('action', 'like', '%'.$request->search.'%');
                });
            })
            ->when($request->filled('user'), fn (Builder $q) => $q->where('user_id', $request->integer('user')))
            ->when($request->filled('action'), fn (Builder $q) => $q->where('action', $request->action))
            ->when($request->filled('resource'), fn (Builder $q) => $q->where('resource_type', $request->resource))
            ->when($request->filled('from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }
}
