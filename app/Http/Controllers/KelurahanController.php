<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSoftDeletes;
use App\Http\Requests\StoreKelurahanRequest;
use App\Http\Requests\UpdateKelurahanRequest;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelurahanController extends Controller
{
    use HandlesSoftDeletes;

    protected array $softDeleteResource = [
        'class' => Kelurahan::class,
        'route' => 'master.kelurahans',
        'label' => 'kelurahan',
        'searchColumn' => 'name',
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Kelurahan::class);

        $kelurahans = Kelurahan::query()
            ->with('kecamatan:id,name')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('kecamatan'), fn ($query) => $query->where('kecamatan_id', $request->kecamatan))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $kecamatans = Kecamatan::orderBy('name')->get(['id', 'name']);

        return view('master.kelurahans.index', compact('kelurahans', 'kecamatans'));
    }

    public function create(): View
    {
        $this->authorize('create', Kelurahan::class);

        return view('master.kelurahans.create', [
            'kecamatans' => Kecamatan::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreKelurahanRequest $request): RedirectResponse
    {
        $this->authorize('create', Kelurahan::class);

        Kelurahan::create($request->validated());

        return redirect()
            ->route('master.kelurahans.index')
            ->with('success', 'Data kelurahan/desa berhasil ditambahkan.');
    }

    public function show(Kelurahan $kelurahan): View
    {
        $this->authorize('view', $kelurahan);

        $kelurahan->load('kecamatan:id,name');

        return view('master.kelurahans.show', compact('kelurahan'));
    }

    public function edit(Kelurahan $kelurahan): View
    {
        $this->authorize('update', $kelurahan);

        return view('master.kelurahans.edit', [
            'kelurahan' => $kelurahan,
            'kecamatans' => Kecamatan::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateKelurahanRequest $request, Kelurahan $kelurahan): RedirectResponse
    {
        $this->authorize('update', $kelurahan);

        $kelurahan->update($request->validated());

        return redirect()
            ->route('master.kelurahans.index')
            ->with('success', 'Data kelurahan/desa berhasil diperbarui.');
    }

    public function destroy(Kelurahan $kelurahan): RedirectResponse
    {
        $this->authorize('delete', $kelurahan);

        $kelurahan->delete();

        return redirect()
            ->route('master.kelurahans.index')
            ->with('success', 'Data kelurahan/desa berhasil dihapus.');
    }

    public function byKecamatan(Request $request): JsonResponse
    {
        $kelurahans = Kelurahan::where('kecamatan_id', $request->integer('kecamatan_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($kelurahans);
    }
}
