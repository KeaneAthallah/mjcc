<?php

namespace App\Http\Controllers;

use App\Services\MapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapController extends Controller
{
    public function __construct(private readonly MapService $service) {}

    public function index(): View
    {
        $data = $this->service->combined();

        return view('maps.index', [
            'markers' => $data['markers'],
            'kecamatans' => $data['kecamatans'],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $kecamatanId = $request->integer('kecamatan');

        return response()->json($this->service->combined(
            $kecamatanId ?: null,
            $request->boolean('refresh'),
        ));
    }
}
