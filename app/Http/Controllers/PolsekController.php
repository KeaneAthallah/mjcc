<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePolsekRequest;
use App\Http\Requests\UpdatePolsekRequest;
use App\Models\Kecamatan;
use App\Models\Polsek;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PolsekController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Polsek::class);

        $polseks = Polsek::query()
            ->with('kecamatan:id,name')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('kecamatan'), fn ($query) => $query->where('kecamatan_id', $request->kecamatan))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('security.polseks.index', [
            'polseks' => $polseks,
            'kecamatans' => Kecamatan::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Polsek::class);

        return view('security.polseks.create', [
            'kecamatans' => Kecamatan::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StorePolsekRequest $request): RedirectResponse
    {
        $this->authorize('create', Polsek::class);

        Polsek::create($request->validated());

        return redirect()
            ->route('security.polseks.index')
            ->with('success', 'Data polsek berhasil ditambahkan.');
    }

    public function show(Polsek $polsek): View
    {
        $this->authorize('view', $polsek);

        $polsek->load('kecamatan:id,name');

        return view('security.polseks.show', compact('polsek'));
    }

    public function edit(Polsek $polsek): View
    {
        $this->authorize('update', $polsek);

        return view('security.polseks.edit', [
            'polsek' => $polsek,
            'kecamatans' => Kecamatan::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdatePolsekRequest $request, Polsek $polsek): RedirectResponse
    {
        $this->authorize('update', $polsek);

        $polsek->update($request->validated());

        return redirect()
            ->route('security.polseks.index')
            ->with('success', 'Data polsek berhasil diperbarui.');
    }

    public function destroy(Polsek $polsek): RedirectResponse
    {
        $this->authorize('delete', $polsek);

        $polsek->delete();

        return redirect()
            ->route('security.polseks.index')
            ->with('success', 'Data polsek berhasil dihapus.');
    }
}
