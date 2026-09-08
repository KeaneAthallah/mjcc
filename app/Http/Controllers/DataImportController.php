<?php

namespace App\Http\Controllers;

use App\Models\DataImportLog;
use App\Models\School;
use App\Services\DataImport\DataImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Synchronization page: history of `data:sync` import runs and a manual
 * "re-import from latest scrape" action. Restricted to users who may mutate
 * master data (admin + operator) via the School policy.
 */
class DataImportController extends Controller
{
    public function index(): View
    {
        $this->authorize('create', School::class);

        $sectors = [];

        foreach (DataImportLog::SECTORS as $sector) {
            $sectors[$sector] = [
                'key' => $sector,
                'label' => (string) (config('public_data.sectors.'.$sector.'.label', $sector)),
                'latest' => DataImportLog::where('sector', $sector)->latest('id')->first(),
            ];
        }

        $logs = DataImportLog::query()->latest('id')->paginate(15)->withQueryString();

        return view('data-import.index', [
            'sectors' => $sectors,
            'logs' => $logs,
        ]);
    }

    public function run(): RedirectResponse
    {
        $this->authorize('create', School::class);

        $results = app(DataImportService::class)->run();

        $failed = collect($results)->contains(fn (array $result) => $result['status'] === DataImportLog::STATUS_FAILED);

        return redirect()
            ->route('data-import.index')
            ->with($failed ? 'error' : 'success', $failed
                ? 'Sinkronisasi gagal pada satu atau lebih sektor. Periksa log untuk detail.'
                : 'Sinkronisasi berhasil: data statistik diimpor ke data master.');
    }
}
