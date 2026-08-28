<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

/**
 * Logs creates / updates / deletes / restores to the audit trail.
 *
 * Sensitive attributes (passwords, remember tokens) are stripped before any
 * data reaches the log. Restore / force-delete events are registered only for
 * models that actually use SoftDeletes.
 */
trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => static::logActivity('create', $model, null, $model->getAttributes()));
        static::updated(fn (Model $model) => static::logActivity('update', $model, $model->getOriginal(), $model->getAttributes()));
        static::deleted(fn (Model $model) => static::logActivity('delete', $model, $model->getOriginal(), null));

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(fn (Model $model) => static::logActivity('restore', $model, $model->getOriginal(), null));
            static::forceDeleted(fn (Model $model) => static::logActivity('force_delete', $model, $model->getOriginal(), null));
        }
    }

    protected static function logActivity(string $action, Model $model, ?array $old, ?array $new): void
    {
        /** @var ActivityLogService $service */
        $service = app(ActivityLogService::class);

        $service->logModel(
            action: match ($action) {
                'create' => ActivityLog::ACTION_CREATE,
                'update' => ActivityLog::ACTION_UPDATE,
                'delete' => ActivityLog::ACTION_DELETE,
                'restore' => ActivityLog::ACTION_RESTORE,
                'force_delete' => ActivityLog::ACTION_FORCE_DELETE,
                default => $action,
            },
            model: $model,
            oldValues: static::sanitizeForLog($old),
            newValues: static::sanitizeForLog($new),
            request: request(),
        );
    }

    /**
     * Remove sensitive attributes so passwords never reach the audit trail.
     *
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    protected static function sanitizeForLog(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return Arr::except($values, ['password', 'remember_token']);
    }
}
