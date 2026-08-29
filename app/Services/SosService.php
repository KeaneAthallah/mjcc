<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\SosAlert;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Centralizes the SOS lifecycle:
 *
 *     active -> acknowledged -> responding -> resolved
 *     active -> cancelled
 *
 * Also enforces the anti-spam rule (one open SOS per user) and writes the
 * audit trail for every security-relevant SOS action.
 */
class SosService
{
    /**
     * Allowed target statuses for each source status.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        SosAlert::STATUS_ACTIVE => [
            SosAlert::STATUS_ACKNOWLEDGED,
            SosAlert::STATUS_RESPONDING,
            SosAlert::STATUS_CANCELLED,
        ],
        SosAlert::STATUS_ACKNOWLEDGED => [
            SosAlert::STATUS_RESPONDING,
            SosAlert::STATUS_RESOLVED,
        ],
        SosAlert::STATUS_RESPONDING => [
            SosAlert::STATUS_RESOLVED,
        ],
        SosAlert::STATUS_RESOLVED => [],
        SosAlert::STATUS_CANCELLED => [],
    ];

    public function __construct(private readonly ActivityLogService $logs) {}

    /**
     * Whether the alert may move to the given status.
     */
    public function canTransition(SosAlert $sos, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$sos->status] ?? [], true);
    }

    /**
     * Rejects an invalid status transition with a standard validation error.
     */
    public function assertCanTransition(SosAlert $sos, string $to): void
    {
        if (! $this->canTransition($sos, $to)) {
            throw ValidationException::withMessages([
                'status' => ["Status SOS tidak dapat berubah dari \"{$sos->status}\" menjadi \"{$to}\"."],
            ]);
        }
    }

    /**
     * Whether the user already has an open (unresolved) SOS.
     */
    public function userHasOpenAlert(User $user): bool
    {
        return SosAlert::query()
            ->where('user_id', $user->id)
            ->whereIn('status', SosAlert::openStatuses())
            ->exists();
    }

    /**
     * Creates a new active SOS for the authenticated user (never trusts a
     * client-supplied user id). Rejects duplicates while an open alert exists.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): SosAlert
    {
        if ($this->userHasOpenAlert($user)) {
            throw ValidationException::withMessages([
                'sos' => ['Anda masih memiliki SOS yang sedang aktif. Tunggu hingga selesai sebelum mengirim yang baru.'],
            ]);
        }

        $sos = DB::transaction(function () use ($user, $data): SosAlert {
            $sos = SosAlert::create([
                'user_id' => $user->id,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'accuracy' => $data['accuracy'] ?? null,
                'status' => SosAlert::STATUS_ACTIVE,
                'message' => $data['message'] ?? null,
            ]);

            $this->log($sos, ActivityLog::ACTION_SOS_CREATED, $user, $sos->getAttributes());

            return $sos;
        });

        return $sos->fresh(['user:id,name,email,role']);
    }

    /**
     * "Terima SOS": active -> acknowledged.
     */
    public function acknowledge(SosAlert $sos, ?string $message, User $actor): SosAlert
    {
        $this->assertCanTransition($sos, SosAlert::STATUS_ACKNOWLEDGED);

        return $this->applyStatus($sos, SosAlert::STATUS_ACKNOWLEDGED, $message, $actor, ActivityLog::ACTION_SOS_ACKNOWLEDGED, 'Permintaan SOS telah diterima.');
    }

    /**
     * "Sedang Menuju Lokasi": acknowledged/active -> responding.
     */
    public function respond(SosAlert $sos, ?string $message, User $actor): SosAlert
    {
        $this->assertCanTransition($sos, SosAlert::STATUS_RESPONDING);

        return $this->applyStatus($sos, SosAlert::STATUS_RESPONDING, $message, $actor, ActivityLog::ACTION_SOS_RESPONDING, 'Petugas sedang menuju lokasi Anda.');
    }

    /**
     * "Selesaikan SOS": acknowledged/responding -> resolved.
     */
    public function resolve(SosAlert $sos, ?string $message, User $actor): SosAlert
    {
        $this->assertCanTransition($sos, SosAlert::STATUS_RESOLVED);

        return $this->applyStatus($sos, SosAlert::STATUS_RESOLVED, $message, $actor, ActivityLog::ACTION_SOS_RESOLVED, 'SOS telah diselesaikan.', resolved: true);
    }

    /**
     * "Batalkan": active -> cancelled (by the owner or an operator/admin).
     */
    public function cancel(SosAlert $sos, User $actor): SosAlert
    {
        $this->assertCanTransition($sos, SosAlert::STATUS_CANCELLED);

        return DB::transaction(function () use ($sos, $actor): SosAlert {
            $sos->update([
                'status' => SosAlert::STATUS_CANCELLED,
            ]);

            $this->log($sos, ActivityLog::ACTION_SOS_CANCELLED, $actor, ['status' => $sos->status]);

            return $sos->fresh(['user:id,name,email,role']);
        });
    }

    /**
     * Counts of open alerts for the notification badge.
     *
     * - Operators/admins see every open alert.
     * - Regular users see only their own open alerts.
     *
     * @return array{open: int, active: int, acknowledged: int, responding: int}
     */
    public function counts(?User $user = null): array
    {
        $query = SosAlert::query();

        if ($user !== null && ! $user->canManageData()) {
            $query->where('user_id', $user->id);
        }

        $open = $query->whereIn('status', SosAlert::openStatuses());

        return [
            'open' => (clone $open)->count(),
            'active' => (clone $open)->where('status', SosAlert::STATUS_ACTIVE)->count(),
            'acknowledged' => (clone $open)->where('status', SosAlert::STATUS_ACKNOWLEDGED)->count(),
            'responding' => (clone $open)->where('status', SosAlert::STATUS_RESPONDING)->count(),
        ];
    }

    private function applyStatus(
        SosAlert $sos,
        string $status,
        ?string $message,
        User $actor,
        string $action,
        string $defaultMessage,
        bool $resolved = false,
    ): SosAlert {
        return DB::transaction(function () use ($sos, $status, $message, $actor, $action, $defaultMessage, $resolved): SosAlert {
            $sos->update([
                'status' => $status,
                'responded_by' => $actor->id,
                'response_message' => $message !== null && trim($message) !== '' ? $message : $defaultMessage,
                'responded_at' => $sos->responded_at ?? now(),
                'resolved_by' => $resolved ? $actor->id : $sos->resolved_by,
                'resolved_at' => $resolved ? now() : $sos->resolved_at,
            ]);

            $this->log($sos, $action, $actor, [
                'status' => $sos->status,
                'response_message' => $sos->response_message,
            ]);

            return $sos->fresh(['user:id,name,email,role']);
        });
    }

    private function log(SosAlert $sos, string $action, User $actor, array $newValues): void
    {
        $this->logs->record(
            action: $action,
            resourceType: 'SosAlert',
            resourceId: $sos->id,
            newValues: $newValues,
            user: $actor,
            ip: request()->ip(),
            userAgent: request()->userAgent(),
        );
    }
}
