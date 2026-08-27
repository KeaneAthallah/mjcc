<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTipkamtikmasRequest;
use App\Http\Requests\UpdateTipkamtikmasRequest;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Tipkamtikmas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TipkamtikmasController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Tipkamtikmas::class);

        $tipkamtikmas = Tipkamtikmas::query()
            ->with(['kecamatan:id,name', 'kelurahan:id,name'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('title', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('kecamatan'), fn ($query) => $query->where('kecamatan_id', $request->kecamatan))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('security.tipkamtikmas.index', [
            'tipkamtikmas' => $tipkamtikmas,
            'kecamatans' => Kecamatan::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Tipkamtikmas::class);

        return view('security.tipkamtikmas.create', [
            'kecamatans' => Kecamatan::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'kelurahans' => collect(),
        ]);
    }

    public function store(StoreTipkamtikmasRequest $request): RedirectResponse
    {
        $this->authorize('create', Tipkamtikmas::class);

        Tipkamtikmas::create($request->validated());

        return redirect()
            ->route('security.tipkamtikmas.index')
            ->with('success', 'Data Tipkamtikmas berhasil ditambahkan.');
    }

    public function show(Tipkamtikmas $tipkamtikma): View
    {
        $this->authorize('view', $tipkamtikma);

        $tipkamtikma->load('kecamatan:id,name', 'kelurahan:id,name');

        return view('security.tipkamtikmas.show', ['tipkamtikma' => $tipkamtikma]);
    }

    public function edit(Tipkamtikmas $tipkamtikma): View
    {
        $this->authorize('update', $tipkamtikma);

        return view('security.tipkamtikmas.edit', [
            'tipkamtikma' => $tipkamtikma,
            'kecamatans' => Kecamatan::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'kelurahans' => Kelurahan::where('kecamatan_id', $tipkamtikma->kecamatan_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateTipkamtikmasRequest $request, Tipkamtikmas $tipkamtikma): RedirectResponse
    {
        $this->authorize('update', $tipkamtikma);

        $tipkamtikma->update($request->validated());

        return redirect()
            ->route('security.tipkamtikmas.index')
            ->with('success', 'Data Tipkamtikmas berhasil diperbarui.');
    }

    public function destroy(Tipkamtikmas $tipkamtikma): RedirectResponse
    {
        $this->authorize('delete', $tipkamtikma);

        $tipkamtikma->delete();

        return redirect()
            ->route('security.tipkamtikmas.index')
            ->with('success', 'Data Tipkamtikmas berhasil dihapus.');
    }
}
