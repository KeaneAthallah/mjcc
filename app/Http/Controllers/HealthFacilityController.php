<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSoftDeletes;
use App\Http\Requests\StoreHealthFacilityRequest;
use App\Http\Requests\UpdateHealthFacilityRequest;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HealthFacilityController extends Controller
{
    use HandlesSoftDeletes;

    protected array $softDeleteResource = [
        'class' => HealthFacility::class,
        'route' => 'health.facilities',
        'label' => 'fasilitas kesehatan',
        'searchColumn' => 'name',
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', HealthFacility::class);

        $facilities = HealthFacility::query()
            ->with('kecamatan:id,name')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('kecamatan'), fn ($query) => $query->where('kecamatan_id', $request->kecamatan))
            ->when($request->filled('type'), fn ($query) => $query->where('facility_type', $request->type))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('health.facilities.index', [
            'facilities' => $facilities,
            'kecamatans' => Kecamatan::orderBy('name')->get(['id', 'name']),
            'types' => [
                HealthFacility::TYPE_PUSKESMAS,
                HealthFacility::TYPE_PUSTU,
                HealthFacility::TYPE_RS,
                HealthFacility::TYPE_POSYANDU,
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', HealthFacility::class);

        return view('health.facilities.create', [
            'kecamatans' => Kecamatan::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'types' => [
                HealthFacility::TYPE_PUSKESMAS,
                HealthFacility::TYPE_PUSTU,
                HealthFacility::TYPE_RS,
                HealthFacility::TYPE_POSYANDU,
            ],
        ]);
    }

    public function store(StoreHealthFacilityRequest $request): RedirectResponse
    {
        $this->authorize('create', HealthFacility::class);

        HealthFacility::create($request->validated());

        return redirect()
            ->route('health.facilities.index')
            ->with('success', 'Data fasilitas kesehatan berhasil ditambahkan.');
    }

    public function show(HealthFacility $health_facility): View
    {
        $this->authorize('view', $health_facility);

        $health_facility->load('kecamatan:id,name');

        return view('health.facilities.show', ['facility' => $health_facility]);
    }

    public function edit(HealthFacility $health_facility): View
    {
        $this->authorize('update', $health_facility);

        return view('health.facilities.edit', [
            'facility' => $health_facility,
            'kecamatans' => Kecamatan::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'types' => [
                HealthFacility::TYPE_PUSKESMAS,
                HealthFacility::TYPE_PUSTU,
                HealthFacility::TYPE_RS,
                HealthFacility::TYPE_POSYANDU,
            ],
        ]);
    }

    public function update(UpdateHealthFacilityRequest $request, HealthFacility $health_facility): RedirectResponse
    {
        $this->authorize('update', $health_facility);

        $health_facility->update($request->validated());

        return redirect()
            ->route('health.facilities.index')
            ->with('success', 'Data fasilitas kesehatan berhasil diperbarui.');
    }

    public function destroy(HealthFacility $health_facility): RedirectResponse
    {
        $this->authorize('delete', $health_facility);

        $health_facility->delete();

        return redirect()
            ->route('health.facilities.index')
            ->with('success', 'Data fasilitas kesehatan berhasil dihapus.');
    }
}
