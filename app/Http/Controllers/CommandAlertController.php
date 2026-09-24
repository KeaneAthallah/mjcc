<?php

namespace App\Http\Controllers;

use App\Events\CommandAlertChanged;
use App\Models\ActivityLog;
use App\Models\CommandAlert;
use App\Models\Kecamatan;
use App\Services\ActivityLogService;
use App\Services\CommandAlertSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommandAlertController extends Controller
{
    public function __construct(
        private readonly CommandAlertSyncService $sync,
        private readonly ActivityLogService $logs,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CommandAlert::class);

        $this->sync->sync();

        $alerts = CommandAlert::query()
            ->with('kecamatan:id,name')
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->severity))
            ->when($request->filled('sector'), fn ($q) => $q->where('sector_key', $request->sector))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('kecamatan'), fn ($q) => $q->where('kecamatan_id', $request->integer('kecamatan')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('title', 'like', '%'.$request->search.'%')
                        ->orWhere('description', 'like', '%'.$request->search.'%');
                });
            })
            ->latest('opened_at')
            ->paginate(20)
            ->withQueryString();

        return view('alerts.index', [
            'alerts' => $alerts,
            'kecamatans' => Kecamatan::orderBy('name')->get(['id', 'name']),
            'counts' => $this->alertCounts(),
        ]);
    }

    public function show(CommandAlert $alert): View
    {
        $this->authorize('view', $alert);

        $alert->load(['kecamatan:id,name', 'resource']);

        return view('alerts.show', ['alert' => $alert]);
    }

    public function updateStatus(Request $request, CommandAlert $alert): RedirectResponse
    {
        $this->authorize('update', $alert);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', CommandAlert::statuses())],
        ]);

        $alert->update([
            'status' => $data['status'],
            'resolved_at' => $data['status'] === CommandAlert::STATUS_SELESAI ? now() : null,
            'resolved_by' => $data['status'] === CommandAlert::STATUS_SELESAI ? $request->user()?->id : null,
        ]);

        $this->logs->logModel(
            ActivityLog::ACTION_ALERT_STATUS,
            $alert,
            ['status' => $alert->getOriginal('status')],
            ['status' => $request->status],
            $request,
        );

        CommandAlertChanged::dispatch($alert->id, $alert->status);

        return redirect()->back()
            ->with('success', "Status alert diperbarui menjadi {$alert->statusLabel()}.");
    }

    public function summary(): JsonResponse
    {
        $this->authorize('viewAny', CommandAlert::class);

        $this->sync->sync();

        $open = CommandAlert::query()->whereIn('status', CommandAlert::openStatuses());

        $counts = [
            'unread' => (clone $open)->where('status', CommandAlert::STATUS_BARU)->count(),
            'critical' => (clone $open)->where('severity', CommandAlert::SEVERITY_CRITICAL)->count(),
            'warning' => (clone $open)->where('severity', CommandAlert::SEVERITY_WARNING)->count(),
            'info' => (clone $open)->where('severity', CommandAlert::SEVERITY_INFO)->count(),
            'total_open' => $open->count(),
        ];

        $recent = (clone $open)
            ->latest('opened_at')
            ->limit(6)
            ->get(['id', 'title', 'severity', 'sector', 'sector_key', 'status', 'kecamatan_id'])
            ->load('kecamatan:id,name')
            ->map(fn (CommandAlert $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'severity' => $a->severity,
                'sector' => $a->sector,
                'sector_key' => $a->sector_key,
                'status' => $a->status,
                'status_label' => $a->statusLabel(),
                'kecamatan' => $a->kecamatan?->name,
                'url' => route('alerts.show', $a),
                'map_url' => $a->mapUrl(),
            ]);

        return response()->json([
            'counts' => $counts,
            'recent' => $recent,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function alertCounts(): array
    {
        $base = CommandAlert::query()->whereIn('status', CommandAlert::openStatuses());

        return [
            'critical' => (clone $base)->where('severity', CommandAlert::SEVERITY_CRITICAL)->count(),
            'warning' => (clone $base)->where('severity', CommandAlert::SEVERITY_WARNING)->count(),
            'info' => (clone $base)->where('severity', CommandAlert::SEVERITY_INFO)->count(),
            'total' => $base->count(),
        ];
    }
}
