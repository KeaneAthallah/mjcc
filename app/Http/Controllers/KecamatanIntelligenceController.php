<?php

namespace App\Http\Controllers;

use App\Models\CommandAlert;
use App\Models\Kecamatan;
use App\Services\KecamatanIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KecamatanIntelligenceController extends Controller
{
    public function __construct(private readonly KecamatanIntelligenceService $intelligence) {}

    public function index(Request $request): View
    {
        $force = $request->boolean('refresh');

        $rows = $this->intelligence->all($force);

        $ranked = $rows->values()->map(function (array $row, int $index) {
            $row['rank'] = $index + 1;
            $row['kecamatan']['open_alerts'] = $row['open_alerts']->count();
            $row['kecamatan']['score'] = $row['status']['score'];
            $row['kecamatan']['status'] = $row['status']['status'];

            return $row;
        });

        return view('kecamatan.index', [
            'rows' => $ranked,
        ]);
    }

    public function show(Kecamatan $kecamatan, Request $request): View
    {
        $row = $this->intelligence->forKecamatan($kecamatan, $request->boolean('refresh'));

        return view('kecamatan.show', [
            'row' => $row,
            'openStatuses' => CommandAlert::openStatuses(),
        ]);
    }
}
