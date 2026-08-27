<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKecamatanRequest;
use App\Http\Requests\UpdateKecamatanRequest;
use App\Models\Kecamatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KecamatanController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Kecamatan::class);

        $kecamatans = Kecamatan::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->status === 'aktif');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.kecamatans.index', ['kecamatans' => $kecamatans]);
    }

    public function create(): View
    {
        $this->authorize('create', Kecamatan::class);

        return view('master.kecamatans.create');
    }

    public function store(StoreKecamatanRequest $request): RedirectResponse
    {
        $this->authorize('create', Kecamatan::class);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        Kecamatan::create($data);

        return redirect()
            ->route('master.kecamatans.index')
            ->with('success', 'Data kecamatan berhasil ditambahkan.');
    }

    public function show(Kecamatan $kecamatan): View
    {
        $this->authorize('view', $kecamatan);

        $kecamatan->loadCount([
            'kelurahans',
            'schools',
            'polseks',
            'tipkamtikmas',
            'poskamlings',
            'markets',
            'healthFacilities',
        ]);

        return view('master.kecamatans.show', compact('kecamatan'));
    }

    public function edit(Kecamatan $kecamatan): View
    {
        $this->authorize('update', $kecamatan);

        return view('master.kecamatans.edit', compact('kecamatan'));
    }

    public function update(UpdateKecamatanRequest $request, Kecamatan $kecamatan): RedirectResponse
    {
        $this->authorize('update', $kecamatan);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $kecamatan->update($data);

        return redirect()
            ->route('master.kecamatans.index')
            ->with('success', 'Data kecamatan berhasil diperbarui.');
    }

    public function destroy(Kecamatan $kecamatan): RedirectResponse
    {
        $this->authorize('delete', $kecamatan);

        $kecamatan->delete();

        return redirect()
            ->route('master.kecamatans.index')
            ->with('success', 'Data kecamatan berhasil dihapus.');
    }
}
