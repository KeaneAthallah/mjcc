<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\AcknowledgeSosAlertRequest;
use App\Http\Requests\Api\ResolveSosAlertRequest;
use App\Http\Requests\Api\RespondSosAlertRequest;
use App\Models\ResponderLocation;
use App\Models\SosAlert;
use App\Models\User;
use App\Services\SosService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SosController extends Controller
{
    public function __construct(private readonly SosService $service) {}

    /**
     * Command-center SOS inbox: stat cards, map of open alerts, filterable
     * list with open alerts first.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SosAlert::class);

        $data = $this->indexData($request);

        return view('sos.index', [
            'alerts' => $data['alerts'],
            'markers' => $data['markers'],
            'counts' => $this->service->counts($request->user()),
            'statuses' => $this->statuses(),
            'statusLabels' => $this->statuses(),
            'statusColors' => $this->statusColors(),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * AJAX feed backing the live command-center page: stat counts, open-alert
     * markers for the map, and the freshly rendered list fragment so the page
     * updates without a reload.
     */
    public function live(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SosAlert::class);

        $data = $this->indexData($request);

        return response()->json([
            'counts' => $this->service->counts($request->user()),
            'markers' => $data['markers'],
            'listHtml' => view('sos.partials._list', [
                'alerts' => $data['alerts'],
                'statusLabels' => $this->statuses(),
                'statusColors' => $this->statusColors(),
            ])->render(),
        ]);
    }

    /**
     * AJAX feed backing the live detail page: status card, timeline, and
     * management buttons rendered as fragments updated in place.
     */
    public function liveShow(SosAlert $sos): JsonResponse
    {
        $this->authorize('view', $sos);

        $sos->load(['user:id,name,email,role', 'respondedBy:id,name', 'resolvedBy:id,name']);

        return response()->json([
            'id' => $sos->id,
            'status' => $sos->status,
            'responders' => $this->responderMarkers($sos),
            'statusCardHtml' => view('sos.partials._status_card', ['sos' => $sos])->render(),
            'timelineHtml' => view('sos.partials._timeline', ['sos' => $sos])->render(),
            'actionsHtml' => view('sos.partials._actions', ['sos' => $sos])->render(),
        ]);
    }

    /**
     * Shared query for the inbox and its live feed: respects the active
     * filters and keeps open alerts at the top.
     *
     * @return array{alerts: LengthAwarePaginator<SosAlert>, markers: Collection<int, array<string, mixed>>}
     */
    private function indexData(Request $request): array
    {
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

        $alerts = $query
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'acknowledged' THEN 1 WHEN 'responding' THEN 2 WHEN 'resolved' THEN 3 ELSE 4 END")
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $markers = SosAlert::query()
            ->whereIn('status', SosAlert::openStatuses())
            ->with('user:id,name')
            ->get()
            ->map(function (SosAlert $sos): array {
                return [
                    'id' => $sos->id,
                    'name' => $sos->user?->name ?? 'Pengguna',
                    'category' => 'sos',
                    'latitude' => (float) $sos->latitude,
                    'longitude' => (float) $sos->longitude,
                    'details' => [
                        'Status' => $sos->status,
                        'Waktu' => $sos->created_at?->format('H:i'),
                    ],
                ];
            })
            ->concat($this->responderMarkersForOpenAlerts())
            ->values();

        return ['alerts' => $alerts, 'markers' => $markers];
    }

    public function show(SosAlert $sos): View
    {
        $this->authorize('view', $sos);

        $sos->load(['user:id,name,email,role', 'respondedBy:id,name', 'resolvedBy:id,name']);

        return view('sos.show', [
            'sos' => $sos,
            'responders' => $this->responderMarkers($sos),
            'marker' => [
                'name' => ($sos->user?->name ?? 'Pengguna').' · SOS #'.$sos->id,
                'category' => 'sos',
                'latitude' => (float) $sos->latitude,
                'longitude' => (float) $sos->longitude,
                'details' => [
                    'Status' => $sos->status,
                    'Waktu' => $sos->created_at?->format('H:i'),
                ],
            ],
        ]);
    }

    public function activeCount(Request $request): JsonResponse
    {
        return response()->json($this->service->counts($request->user()));
    }

    public function acknowledge(AcknowledgeSosAlertRequest $request, SosAlert $sos): RedirectResponse
    {
        $this->service->acknowledge($sos, $request->string('response_message')->toString(), $request->user());

        return back()->with('success', 'SOS telah diterima.');
    }

    public function respond(RespondSosAlertRequest $request, SosAlert $sos): RedirectResponse
    {
        $this->service->respond($sos, $request->string('response_message')->toString(), $request->user());

        return back()->with('success', 'Status SOS diperbarui menjadi sedang menuju lokasi.');
    }

    public function resolve(ResolveSosAlertRequest $request, SosAlert $sos): RedirectResponse
    {
        $this->service->resolve($sos, $request->string('response_message')->toString(), $request->user());

        return back()->with('success', 'SOS telah diselesaikan.');
    }

    public function cancel(Request $request, SosAlert $sos): RedirectResponse
    {
        $this->authorize('cancel', $sos);

        $this->service->cancel($sos, $request->user());

        return back()->with('success', 'SOS telah dibatalkan.');
    }

    /**
     * Latest live location of each responder on an alert, shaped as map
     * markers so the web map can place both the sender and the petugas.
     *
     * @return array<int, array<string, mixed>>
     */
    private function responderMarkers(SosAlert $sos): array
    {
        return $this->service
            ->getLatestResponderLocations($sos)
            ->map(fn (ResponderLocation $location): array => [
                'id' => $location->id,
                'name' => $location->user?->name ?? 'Petugas',
                'category' => 'responder',
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'lastSeen' => $location->created_at?->diffForHumans(),
                'details' => [
                    'Peran' => $location->user?->responder_type_label ?? 'Petugas',
                    'Posisi' => $location->created_at?->format('H:i'),
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * Latest petugas position for every open alert, merged into the command
     * center map so operators see both sender and petugas markers live.
     *
     * @return array<int, array<string, mixed>>
     */
    private function responderMarkersForOpenAlerts(): array
    {
        $latestIds = ResponderLocation::query()
            ->whereIn('sos_alert_id', SosAlert::query()->whereIn('status', SosAlert::openStatuses())->select('id'))
            ->selectRaw('MAX(id) as id')
            ->groupBy(['sos_alert_id', 'user_id'])
            ->pluck('id');

        if ($latestIds->isEmpty()) {
            return [];
        }

        return ResponderLocation::query()
            ->whereIn('id', $latestIds)
            ->with('user:id,name,responder_type')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ResponderLocation $location): array => [
                'id' => $location->id,
                'name' => $location->user?->name ?? 'Petugas',
                'category' => 'responder',
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'lastSeen' => $location->created_at?->diffForHumans(),
                'detailUrl' => route('sos.show', $location->sos_alert_id),
                'details' => [
                    'Peran' => $location->user?->responder_type_label ?? 'Petugas',
                    'Posisi' => $location->created_at?->format('H:i'),
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function statuses(): array
    {
        return [
            SosAlert::STATUS_ACTIVE => 'Aktif',
            SosAlert::STATUS_ACKNOWLEDGED => 'Diterima',
            SosAlert::STATUS_RESPONDING => 'Menuju Lokasi',
            SosAlert::STATUS_RESOLVED => 'Selesai',
            SosAlert::STATUS_CANCELLED => 'Dibatalkan',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function statusColors(): array
    {
        return [
            SosAlert::STATUS_ACTIVE => 'red',
            SosAlert::STATUS_ACKNOWLEDGED => 'amber',
            SosAlert::STATUS_RESPONDING => 'blue',
            SosAlert::STATUS_RESOLVED => 'green',
            SosAlert::STATUS_CANCELLED => 'gray',
        ];
    }
}
