<?php

namespace App\Observers;

use App\Events\MasterDataChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Batches every Eloquent write on the observable models and dispatches a single
 * `MasterDataChanged` event — plus the cache invalidation it cascades into —
 * once per request/command via the application "terminating" callback.
 *
 * Batching matters: bulk imports (public-data sync, data:sync) touch thousands
 * of rows; flushing caches or broadcasting once per row would be wasteful.
 * The buffer simply accumulates and one broadcast + one flush happens at the
 * end of the request lifecycle.
 */
class DataChangeObserver
{
    /**
     * @var array<string, array<string, int>>
     */
    protected static array $pending = [];

    public function created(Model $model): void
    {
        self::record($model, 'created');
    }

    public function updated(Model $model): void
    {
        self::record($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        self::record($model, 'deleted');
    }

    public function restored(Model $model): void
    {
        self::record($model, 'restored');
    }

    public function forceDeleted(Model $model): void
    {
        self::record($model, 'force-deleted');
    }

    /**
     * Record one mutation against its resource key.
     */
    protected static function record(Model $model, string $action): void
    {
        $resource = Str::snake(Str::pluralStudly(class_basename($model)));

        self::$pending[$resource][$action] = (self::$pending[$resource][$action] ?? 0) + 1;
    }

    /**
     * Resources mutated since the last flush, keyed by resource.
     *
     * @return array<string, array<string, int>>
     */
    public static function pendingChanges(): array
    {
        return self::$pending;
    }

    public static function hasPendingChanges(): bool
    {
        return self::$pending !== [];
    }

    /**
     * Dispatch a single batched event, then reset the buffer. Idempotent when
     * called more than once (e.g. no-op when already flushed).
     */
    public static function flush(?int $triggerUserId = null): void
    {
        if (self::$pending === []) {
            return;
        }

        $pending = self::$pending;
        self::$pending = [];

        MasterDataChanged::dispatch($pending, $triggerUserId);
    }
}
