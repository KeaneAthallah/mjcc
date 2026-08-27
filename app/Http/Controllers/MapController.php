<?php

namespace App\Http\Controllers;

use App\Services\MapService;
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
}
