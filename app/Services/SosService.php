<?php

namespace App\Services;

use App\Events\ResponderLocationUpdated;
use App\Events\SosCreated;
use App\Events\SosUpdated;
use App\Models\ActivityLog;
use App\Models\ResponderLocation;
use App\Models\SosAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Centralizes the SOS lifecycle:
 *
 *     active -> acknowledged -> responding -> resolved
 *     active -> accepted -> on_the_way -> arrived -> resolved
 *     active -> cancelled
 *     accepted -> on_the_way | couldn't reach (constrained)
 *     constrained -> on_the_way | arrived | cancelled
 *     arrived -> resolved
 *
 * The petugas cannot resolve an assigned SOS until they have arrived. If they
 * cannot reach the location or are delayed, they report a "constraint" (type +
 * reason) and may later continue on_the_way or mark arrived.
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
            SosAlert::STATUS_ACCEPTED,
        ],
        SosAlert::STATUS_ACKNOWLEDGED => [
            SosAlert::STATUS_RESPONDING,
            SosAlert::STATUS_RESOLVED,
        ],
        SosAlert::STATUS_RESPONDING => [
            SosAlert::STATUS_RESOLVED,
        ],
        SosAlert::STATUS_ACCEPTED => [
            SosAlert::STATUS_ON_THE_WAY,
            SosAlert::STATUS_RESOLVED,
            SosAlert::STATUS_CANCELLED,
            SosAlert::STATUS_CONSTRAINED,
        ],
        SosAlert::STATUS_ON_THE_WAY => [
            SosAlert::STATUS_ARRIVED,
            SosAlert::STATUS_RESOLVED,
            SosAlert::STATUS_CANCELLED,
            SosAlert::STATUS_CONSTRAINED,
        ],
        SosAlert::STATUS_CONSTRAINED => [
            SosAlert::STATUS_ON_THE_WAY,
            SosAlert::STATUS_ARRIVED,
            SosAlert::STATUS_CANCELLED,
        ],
        SosAlert::STATUS_ARRIVED => [
            SosAlert::STATUS_RESOLVED,
            SosAlert::STATUS_CANCELLED,
        ],
        SosAlert::STATUS_RESOLVED => [],
        SosAlert::STATUS_CANCELLED => [],
    ];

    /**
     * Statuses in which the petugas may (re)report a constraint: only before
     * they mark themselves as arrived.
     *
     * @var array<int, string>
     */
    private const REPORTABLE_CONSTRAINT_STATUSES = [
        SosAlert::STATUS_ACCEPTED,
        SosAlert::STATUS_ON_THE_WAY,
        SosAlert::STATUS_CONSTRAINED,
    ];

    public function __construct(
        private readonly ActivityLogService $logs,
        private readonly NotificationService $notifications,
    ) {}

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
                'category' => $data['category'] ?? SosAlert::CATEGORY_GENERAL,
                'message' => $data['message'] ?? null,
            ]);

            $this->log($sos, ActivityLog::ACTION_SOS_CREATED, $user, $sos->getAttributes());

            return $sos;
        });

        $sos->load(['user:id,name,email,role']);

        $this->notifications->notifyRelevantResponders($sos);

        SosCreated::dispatch($sos, $user->id);

        return $sos;
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
     * "Selesaikan SOS": acknowledged/responding -> resolved (operator/admin),
     * arrived -> resolved (petugas). The petugas may only resolve an SOS they
     * accepted after they have physically arrived at the location.
     */
    public function resolve(SosAlert $sos, ?string $message, User $actor): SosAlert
    {
        if ($sos->accepted_by === $actor->id &&
            $sos->status !== SosAlert::STATUS_ARRIVED &&
            $this->canTransition($sos, SosAlert::STATUS_RESOLVED)) {
            throw ValidationException::withMessages([
                'status' => ['Petugas hanya dapat menyelesaikan SOS setelah tiba di lokasi. Anda dapat melaporkan kendala jika belum bisa tiba.'],
            ]);
        }

        $this->assertCanTransition($sos, SosAlert::STATUS_RESOLVED);

        return $this->applyStatus($sos, SosAlert::STATUS_RESOLVED, $message, $actor, ActivityLog::ACTION_SOS_RESOLVED, 'SOS telah diselesaikan.', resolved: true);
    }

    /**
     * "Petugas Terkendala": the accepted petugas reports they cannot reach the
     * location (or are delayed) with a required reason. Notifies the requester
     * so they know help may be delayed. May be re-reported to update the reason.
     */
    public function constrain(SosAlert $sos, string $type, string $reason, User $actor): SosAlert
    {
        if ($sos->accepted_by !== $actor->id) {
            throw ValidationException::withMessages([
                'responder' => ['Hanya petugas yang menerima SOS yang dapat melaporkan kendala.'],
            ]);
        }

        if (! in_array($sos->status, self::REPORTABLE_CONSTRAINT_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => ['Kendala hanya dapat dilaporkan sebelum petugas tiba di lokasi (status saat ini: "'.$sos->status.'").'],
            ]);
        }

        $sos = DB::transaction(function () use ($sos, $type, $reason, $actor): SosAlert {
            $sos->update([
                'status' => SosAlert::STATUS_CONSTRAINED,
                'constraint_type' => $type,
                'constraint_reason' => $reason,
                'constrained_by' => $actor->id,
                'constrained_at' => now(),
            ]);

            $this->log($sos, ActivityLog::ACTION_SOS_CONSTRAINED, $actor, [
                'status' => $sos->status,
                'constraint_type' => $type,
                'constraint_reason' => $reason,
            ]);

            $result = $sos->fresh(['user:id,name,email,role', 'acceptedBy:id,name']);

            SosUpdated::dispatch($result, $actor->id, SosAlert::STATUS_ON_THE_WAY);

            return $result;
        });

        $this->notifications->notifyUser(
            $sos->user,
            'Petugas Terkendala',
            'Petugas melaporkan kendala: '.$reason.'. Mohon menunggu pembaruan.',
            'sos',
            ['sos_alert_id' => $sos->id, 'status' => SosAlert::STATUS_CONSTRAINED],
        );

        return $sos;
    }

    /**
     * "Batalkan": active -> cancelled (by the owner or an operator/admin).
     */
    public function cancel(SosAlert $sos, User $actor): SosAlert
    {
        $this->assertCanTransition($sos, SosAlert::STATUS_CANCELLED);

        return DB::transaction(function () use ($sos, $actor): SosAlert {
            $previousStatus = $sos->status;

            $sos->update([
                'status' => SosAlert::STATUS_CANCELLED,
            ]);

            $this->log($sos, ActivityLog::ACTION_SOS_CANCELLED, $actor, ['status' => $sos->status]);

            $fresh = $sos->fresh(['user:id,name,email,role']);

            SosUpdated::dispatch($fresh, $actor->id, $previousStatus);

            return $fresh;
        });
    }

    /**
     * A responder accepts an active SOS. Validates responder_type matches category (or admin).
     */
    public function accept(SosAlert $sos, User $responder): SosAlert
    {
        if (! $responder->responderForCategory($sos->category)) {
            throw ValidationException::withMessages([
                'responder' => ['Tipe responder Anda tidak sesuai dengan kategori SOS ini.'],
            ]);
        }

        $this->assertCanTransition($sos, SosAlert::STATUS_ACCEPTED);

        return DB::transaction(function () use ($sos, $responder): SosAlert {
            $fresh = SosAlert::where('id', $sos->id)->lockForUpdate()->first();

            if ($fresh->accepted_by !== null) {
                throw ValidationException::withMessages([
                    'sos' => ['SOS ini sudah diterima oleh petugas lain.'],
                ]);
            }

            $fresh->update([
                'status' => SosAlert::STATUS_ACCEPTED,
                'accepted_by' => $responder->id,
                'accepted_at' => now(),
            ]);

            $this->log($fresh, ActivityLog::ACTION_SOS_ACCEPTED, $responder, [
                'status' => $fresh->status,
                'accepted_by' => $responder->id,
            ]);

            $result = $fresh->fresh(['user:id,name,email,role', 'acceptedBy:id,name']);

            SosUpdated::dispatch($result, $responder->id, SosAlert::STATUS_ACTIVE);

            return $result;
        });
    }

    /**
     * Responder marks themselves as on the way: accepted -> on_the_way.
     */
    public function onTheWay(SosAlert $sos, User $responder): SosAlert
    {
        if ($sos->accepted_by !== $responder->id) {
            throw ValidationException::withMessages([
                'responder' => ['Hanya petugas yang menerima SOS yang dapat memperbarui status ini.'],
            ]);
        }

        $this->assertCanTransition($sos, SosAlert::STATUS_ON_THE_WAY);

        return DB::transaction(function () use ($sos, $responder): SosAlert {
            $previousStatus = $sos->status;

            $sos->update([
                'status' => SosAlert::STATUS_ON_THE_WAY,
            ]);

            $this->log($sos, ActivityLog::ACTION_SOS_ON_THE_WAY, $responder, [
                'status' => $sos->status,
            ]);

            $fresh = $sos->fresh(['user:id,name,email,role', 'acceptedBy:id,name']);

            SosUpdated::dispatch($fresh, $responder->id, $previousStatus);

            return $fresh;
        });
    }

    /**
     * Responder marks themselves as arrived: on_the_way -> arrived.
     */
    public function arrived(SosAlert $sos, User $responder): SosAlert
    {
        if ($sos->accepted_by !== $responder->id) {
            throw ValidationException::withMessages([
                'responder' => ['Hanya petugas yang menerima SOS yang dapat memperbarui status ini.'],
            ]);
        }

        $this->assertCanTransition($sos, SosAlert::STATUS_ARRIVED);

        return DB::transaction(function () use ($sos, $responder): SosAlert {
            $previousStatus = $sos->status;

            $sos->update([
                'status' => SosAlert::STATUS_ARRIVED,
            ]);

            $this->log($sos, ActivityLog::ACTION_SOS_ARRIVED, $responder, [
                'status' => $sos->status,
            ]);

            $fresh = $sos->fresh(['user:id,name,email,role', 'acceptedBy:id,name']);

            SosUpdated::dispatch($fresh, $responder->id, $previousStatus);

            return $fresh;
        });
    }

    /**
     * Update responder's live location for an SOS.
     */
    public function updateResponderLocation(int $sosId, User $responder, float $lat, float $lng): void
    {
        $location = ResponderLocation::create([
            'sos_alert_id' => $sosId,
            'user_id' => $responder->id,
            'latitude' => $lat,
            'longitude' => $lng,
        ]);

        $location->load('user:id,name,responder_type');

        ResponderLocationUpdated::dispatch($location, $sosId);
    }

    /**
     * Latest recorded location per responder for an alert. Returns the newest
     * `sos_responder_locations` row for each distinct responder, eager-loaded
     * with the user identity so the requester can see who is coming.
     *
     * @return Collection<int, ResponderLocation>
     */
    public function getLatestResponderLocations(SosAlert $sos): Collection
    {
        $latestIds = ResponderLocation::query()
            ->selectRaw('MAX(id) as id')
            ->where('sos_alert_id', $sos->id)
            ->groupBy('user_id')
            ->pluck('id');

        if ($latestIds->isEmpty()) {
            return new Collection;
        }

        return ResponderLocation::query()
            ->whereIn('id', $latestIds)
            ->with('user:id,name,responder_type')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get active incidents that match a responder's type.
     */
    public function getActiveIncidentsForResponder(User $responder): Collection
    {
        return SosAlert::query()
            ->whereIn('status', SosAlert::isOpenStatuses())
            ->where(function (Builder $q) use ($responder) {
                if ($responder->isAdmin()) {
                    return;
                }
                $q->where('category', $responder->responder_type);
            })
            ->with(['user:id,name,email,role', 'acceptedBy:id,name'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'acknowledged' THEN 1 WHEN 'responding' THEN 2 WHEN 'accepted' THEN 3 WHEN 'on_the_way' THEN 4 WHEN 'arrived' THEN 5 WHEN 'constrained' THEN 6 ELSE 7 END")
            ->latest('created_at')
            ->get();
    }

    /**
     * Counts of open alerts for the notification badge.
     *
     * @return array{open: int, active: int, acknowledged: int, responding: int, accepted: int, on_the_way: int, arrived: int, constrained: int}
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
            'accepted' => (clone $open)->where('status', SosAlert::STATUS_ACCEPTED)->count(),
            'on_the_way' => (clone $open)->where('status', SosAlert::STATUS_ON_THE_WAY)->count(),
            'arrived' => (clone $open)->where('status', SosAlert::STATUS_ARRIVED)->count(),
            'constrained' => (clone $open)->where('status', SosAlert::STATUS_CONSTRAINED)->count(),
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
            $previousStatus = $sos->status;

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

            $fresh = $sos->fresh(['user:id,name,email,role']);

            SosUpdated::dispatch($fresh, $actor->id, $previousStatus);

            return $fresh;
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
