<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSoftDeletes;
use App\Http\Requests\StoreMarketRequest;
use App\Http\Requests\UpdateMarketRequest;
use App\Models\Kecamatan;
use App\Models\Market;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketController extends Controller
{
    use HandlesSoftDeletes;

    protected array $softDeleteResource = [
        'class' => Market::class,
        'route' => 'security.markets',
        'label' => 'pasar',
        'searchColumn' => 'name',
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Market::class);

        $markets = Market::query()
            ->with('kecamatan:id,name')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('kecamatan'), fn ($query) => $query->where('kecamatan_id', $request->kecamatan))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('security.markets.index', [
            'markets' => $markets,
            'kecamatans' => Kecamatan::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Market::class);

        return view('security.markets.create', [
            'kecamatans' => Kecamatan::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreMarketRequest $request): RedirectResponse
    {
        $this->authorize('create', Market::class);

        Market::create($request->validated());

        return redirect()
            ->route('security.markets.index')
            ->with('success', 'Data pasar berhasil ditambahkan.');
    }

    public function show(Market $market): View
    {
        $this->authorize('view', $market);

        $market->load('kecamatan:id,name');

        return view('security.markets.show', compact('market'));
    }

    public function edit(Market $market): View
    {
        $this->authorize('update', $market);

        return view('security.markets.edit', [
            'market' => $market,
            'kecamatans' => Kecamatan::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateMarketRequest $request, Market $market): RedirectResponse
    {
        $this->authorize('update', $market);

        $market->update($request->validated());

        return redirect()
            ->route('security.markets.index')
            ->with('success', 'Data pasar berhasil diperbarui.');
    }

    public function destroy(Market $market): RedirectResponse
    {
        $this->authorize('delete', $market);

        $market->delete();

        return redirect()
            ->route('security.markets.index')
            ->with('success', 'Data pasar berhasil dihapus.');
    }
}
