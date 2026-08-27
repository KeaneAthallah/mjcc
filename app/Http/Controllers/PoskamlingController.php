<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePoskamlingRequest;
use App\Http\Requests\UpdatePoskamlingRequest;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Poskamling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PoskamlingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Poskamling::class);

        $poskamlings = Poskamling::query()
            ->with(['kecamatan:id,name', 'kelurahan:id,name'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('kecamatan'), fn ($query) => $query->where('kecamatan_id', $request->kecamatan))
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->status === 'aktif');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('security.poskamlings.index', [
            'poskamlings' => $poskamlings,
            'kecamatans' => Kecamatan::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Poskamling::class);

        return view('security.poskamlings.create', [
            'kecamatans' => Kecamatan::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'kelurahans' => collect(),
        ]);
    }

    public function store(StorePoskamlingRequest $request): RedirectResponse
    {
        $this->authorize('create', Poskamling::class);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        Poskamling::create($data);

        return redirect()
            ->route('security.poskamlings.index')
            ->with('success', 'Data poskamling berhasil ditambahkan.');
    }

    public function show(Poskamling $poskamling): View
    {
        $this->authorize('view', $poskamling);

        $poskamling->load('kecamatan:id,name', 'kelurahan:id,name');

        return view('security.poskamlings.show', compact('poskamling'));
    }

    public function edit(Poskamling $poskamling): View
    {
        $this->authorize('update', $poskamling);

        return view('security.poskamlings.edit', [
            'poskamling' => $poskamling,
            'kecamatans' => Kecamatan::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'kelurahans' => Kelurahan::where('kecamatan_id', $poskamling->kecamatan_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdatePoskamlingRequest $request, Poskamling $poskamling): RedirectResponse
    {
        $this->authorize('update', $poskamling);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $poskamling->update($data);

        return redirect()
            ->route('security.poskamlings.index')
            ->with('success', 'Data poskamling berhasil diperbarui.');
    }

    public function destroy(Poskamling $poskamling): RedirectResponse
    {
        $this->authorize('delete', $poskamling);

        $poskamling->delete();

        return redirect()
            ->route('security.poskamlings.index')
            ->with('success', 'Data poskamling berhasil dihapus.');
    }
}
